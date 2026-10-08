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

namespace Instride\Bundle\DataDefinitionsBundle\Command;

use Instride\Bundle\DataDefinitionsBundle\Model\DataDefinitionInterface;
use Instride\Bundle\DataDefinitionsBundle\Run\RunManager;
use Instride\Bundle\DataDefinitionsBundle\Run\RunStatus;
use Instride\Bundle\DataDefinitionsBundle\Run\RunTrigger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Shared "run" handling of the import and export command: every execution is recorded in the run history.
 */
trait RunCommandTrait
{
    private function addRunOptions(): void
    {
        $this
            ->addOption(
                'async',
                null,
                InputOption::VALUE_NONE,
                'Only queue the run, a worker executes it (bin/console messenger:consume data_definitions_run)',
            )
            ->addOption(
                'no-history',
                null,
                InputOption::VALUE_NONE,
                'Execute directly without recording a run (no run history and run log)',
            )
        ;
    }

    /**
     * @param callable(array $params): void $direct executes the definition without a run
     */
    private function executeAsRun(
        RunManager $runManager,
        string $type,
        DataDefinitionInterface $definition,
        array $params,
        InputInterface $input,
        OutputInterface $output,
        callable $direct,
    ): int {
        if ($input->getOption('no-history')) {
            $direct($params);

            return Command::SUCCESS;
        }

        $userId = isset($params['userId']) && is_numeric($params['userId']) ? (int) $params['userId'] : null;

        if ($input->getOption('async')) {
            $run = $runManager->start($type, $definition, $params, RunTrigger::CLI, $userId, true);
            $output->writeln(sprintf('<info>Run #%d queued.</info>', $run->getId()));

            return Command::SUCCESS;
        }

        $run = $runManager->create($type, $definition, $params, RunTrigger::CLI, $userId);
        $run = $runManager->execute((int) $run->getId()) ?? $run;

        $output->writeln(sprintf(
            'Run #%d: <comment>%s</comment> (processed %d, created %d, updated %d, skipped %d, errors %d)',
            $run->getId(),
            $run->getStatus(),
            $run->getProcessed(),
            $run->getCreatedCount(),
            $run->getUpdatedCount(),
            $run->getSkippedCount(),
            $run->getErrorCount(),
        ));

        if (RunStatus::FAILED === $run->getStatus()) {
            $output->writeln(sprintf('<error>%s</error>', $run->getMessage()));

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
