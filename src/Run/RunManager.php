<?php

declare(strict_types=1);

/*
 * This source file is available under two different licenses:
 *  - Data Definitions Commercial License (DDCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) CORS GmbH (https://www.cors.gmbh)
 * @license    DDCL
 */

namespace Instride\Bundle\DataDefinitionsBundle\Run;

use Instride\Bundle\DataDefinitionsBundle\Entity\Run;
use Instride\Bundle\DataDefinitionsBundle\Exporter\ExporterInterface;
use Instride\Bundle\DataDefinitionsBundle\Importer\ImporterInterface;
use Instride\Bundle\DataDefinitionsBundle\Messenger\ExecuteRunMessage;
use Instride\Bundle\DataDefinitionsBundle\Model\DataDefinitionInterface;
use Instride\Bundle\DataDefinitionsBundle\Model\ExportDefinitionInterface;
use Instride\Bundle\DataDefinitionsBundle\Model\ImportDefinitionInterface;
use Instride\Bundle\DataDefinitionsBundle\Repository\DefinitionRepository;
use Instride\Bundle\DataDefinitionsBundle\Run\Notifier\RunNotifierInterface;
use InvalidArgumentException;
use Psr\Log\LogLevel;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

/**
 * Creates, dispatches and executes import/export runs.
 *
 * A run is executed synchronously in the process that calls execute(): the CLI, the request (sync mode)
 * or a messenger worker consuming the data_definitions_run transport.
 */
final class RunManager
{
    public function __construct(
        private RunRepository $repository,
        private RunTracker $tracker,
        private DefinitionRepository $importDefinitionRepository,
        private DefinitionRepository $exportDefinitionRepository,
        private ImporterInterface $importer,
        private ExporterInterface $exporter,
        private MessageBusInterface $bus,
        private RunNotifierInterface $notifier,
    ) {
    }

    public function create(string $type, DataDefinitionInterface $definition, array $params, string $trigger, ?int $userId = null): Run
    {
        $this->assertType($type);

        $run = new Run($type, (int) $definition->getId(), $trigger, $params, $userId);
        $this->repository->add($run);
        $this->notifier->runChanged($run);

        return $run;
    }

    public function dispatch(Run $run): void
    {
        $this->bus->dispatch(new ExecuteRunMessage((int) $run->getId()));
    }

    /**
     * Executes a queued run in this process. Returns the run in its final state.
     */
    public function execute(int $runId): ?Run
    {
        $run = $this->repository->find($runId);

        if (null === $run) {
            return null;
        }

        // claim the run, a redelivered message or a cancelled run must not execute (again)
        $claimed = $this->repository->transition($runId, [RunStatus::QUEUED], RunStatus::RUNNING, [
            'started_at' => time(),
            'hostname' => gethostname() ?: null,
            'pid' => getmypid() ?: null,
            'message' => null,
        ]);

        if (!$claimed) {
            return $this->repository->find($runId);
        }

        $this->notifyChanged($runId);
        $this->tracker->begin($run);

        $error = null;

        try {
            $definition = $this->getDefinition($run->getType(), $run->getDefinition());
            $params = $run->getParams();
            $params['runId'] = $runId;
            $params['userId'] ??= $run->getUserId() ?? 0;

            if ($definition instanceof ImportDefinitionInterface) {
                $this->importer->doImport($definition, $params);
            } elseif ($definition instanceof ExportDefinitionInterface) {
                $this->exporter->doExport($definition, $params);
            }
        } catch (Throwable $ex) {
            $error = $ex;
            $this->tracker->addLog(
                LogLevel::CRITICAL,
                sprintf('Run failed: %s', $ex->getMessage()),
                ['exception' => $ex],
            );
        }

        $state = $this->tracker->end();

        if (null !== $error) {
            $status = RunStatus::FAILED;
        } elseif (null !== $state && $state->stopRequested) {
            $status = RunStatus::CANCELLED;
        } elseif (null !== $state && ($state->errors > 0 || $state->hasLoggedErrors)) {
            $status = RunStatus::FINISHED_WITH_ERRORS;
        } else {
            $status = RunStatus::FINISHED;
        }

        $message = match ($status) {
            RunStatus::FAILED => $error?->getMessage(),
            RunStatus::CANCELLED => 'Run has been stopped',
            default => $state && $state->droppedLogs > 0 ? sprintf('%d log records dropped (limit reached)', $state->droppedLogs) : null,
        };

        $this->repository->transition($runId, [RunStatus::RUNNING, RunStatus::STOPPING], $status, [
            'finished_at' => time(),
            'message' => $message ?? $state?->message,
        ]);

        return $this->notifyChanged($runId) ?? $run;
    }

    /**
     * Creates a run and either dispatches it to the worker or executes it right away.
     */
    public function start(
        string $type,
        DataDefinitionInterface $definition,
        array $params,
        string $trigger,
        ?int $userId = null,
        bool $async = true,
    ): Run {
        $run = $this->create($type, $definition, $params, $trigger, $userId);

        if ($async) {
            $this->dispatch($run);

            return $run;
        }

        return $this->execute((int) $run->getId()) ?? $run;
    }

    public function rerun(Run $run, string $trigger = RunTrigger::RERUN, ?int $userId = null, bool $async = true): Run
    {
        $definition = $this->getDefinition($run->getType(), $run->getDefinition());

        return $this->start($run->getType(), $definition, $run->getParams(), $trigger, $userId ?? $run->getUserId(), $async);
    }

    /**
     * A queued run is cancelled right away, a running one is asked to stop after the current row.
     */
    public function requestStop(Run $run): void
    {
        $id = (int) $run->getId();

        if (!$this->repository->transition($id, [RunStatus::QUEUED], RunStatus::CANCELLED, ['finished_at' => time(), 'message' => 'Cancelled before start'])) {
            $this->repository->transition($id, [RunStatus::RUNNING], RunStatus::STOPPING, ['message' => 'Stop requested']);
        }

        $this->notifyChanged($id);
    }

    /**
     * Reloads the run and pushes it to clients.
     */
    private function notifyChanged(int $runId): ?Run
    {
        try {
            $run = $this->repository->find($runId);
        } catch (Throwable) {
            // e.g. the entity manager was closed by an exception during the import
            return null;
        }

        if (null !== $run) {
            $this->notifier->runChanged($run);
        }

        return $run;
    }

    public function getDefinition(string $type, int|string $idOrName): DataDefinitionInterface
    {
        $this->assertType($type);

        $repository = Run::TYPE_IMPORT === $type ? $this->importDefinitionRepository : $this->exportDefinitionRepository;
        $definition = null;

        try {
            $definition = filter_var($idOrName, \FILTER_VALIDATE_INT) ? $repository->find($idOrName) : $repository->findByName((string) $idOrName);
        } catch (Throwable) {
        }

        $expected = Run::TYPE_IMPORT === $type ? ImportDefinitionInterface::class : ExportDefinitionInterface::class;

        if (!$definition instanceof $expected) {
            throw new InvalidArgumentException(sprintf('%s Definition with ID/Name "%s" not found', ucfirst($type), $idOrName));
        }

        return $definition;
    }

    private function assertType(string $type): void
    {
        if (!\in_array($type, [Run::TYPE_IMPORT, Run::TYPE_EXPORT], true)) {
            throw new InvalidArgumentException(sprintf('Invalid run type "%s"', $type));
        }
    }
}
