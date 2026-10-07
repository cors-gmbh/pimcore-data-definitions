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

namespace Instride\Bundle\DataDefinitionsBundle\Controller;

use Instride\Bundle\DataDefinitionsBundle\Entity\Run;
use Instride\Bundle\DataDefinitionsBundle\Run\Notifier\RunNotifierInterface;
use Instride\Bundle\DataDefinitionsBundle\Run\ParamsSchema\ParamsSchemaBuilder;
use Instride\Bundle\DataDefinitionsBundle\Run\RunManager;
use Instride\Bundle\DataDefinitionsBundle\Run\RunNormalizer;
use Instride\Bundle\DataDefinitionsBundle\Run\RunRepository;
use Instride\Bundle\DataDefinitionsBundle\Run\RunTrigger;
use InvalidArgumentException;
use Pimcore\Model\Asset;
use Pimcore\Model\Element\Service as ElementService;
use Pimcore\Model\User;
use Pimcore\Security\User\User as SecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Studio API for starting import/export runs and browsing their history and logs.
 */
final class RunController extends AbstractController
{
    private const PERMISSIONS = [
        Run::TYPE_IMPORT => 'data_definitions_permission_data_definitions_import',
        Run::TYPE_EXPORT => 'data_definitions_permission_data_definitions_export',
    ];

    public function __construct(
        private RunManager $runManager,
        private RunRepository $runRepository,
        private ParamsSchemaBuilder $paramsSchemaBuilder,
        private RunNormalizer $runNormalizer,
        private RunNotifierInterface $runNotifier,
        #[Autowire(param: 'data_definitions.runs.upload_folder')]
        private string $uploadFolder,
    ) {
    }

    public function paramsSchemaAction(Request $request): JsonResponse
    {
        $type = $this->getType($request->query->get('type'));
        $this->denyUnlessAllowed($type);

        $definition = $this->getDefinition($type, $request->query->get('definition'));

        return $this->json([
            'success' => true,
            'fields' => $this->paramsSchemaBuilder->build($definition),
        ]);
    }

    public function startAction(Request $request): JsonResponse
    {
        $payload = $this->getPayload($request);
        $type = $this->getType($payload['type'] ?? null);
        $this->denyUnlessAllowed($type);

        $definition = $this->getDefinition($type, $payload['definition'] ?? null);

        $params = $payload['params'] ?? [];
        if (!\is_array($params) || ($params !== [] && array_is_list($params))) {
            throw new BadRequestHttpException('"params" has to be a JSON object');
        }

        $run = $this->runManager->start(
            $type,
            $definition,
            $params,
            RunTrigger::GUI,
            $this->getPimcoreUser()->getId(),
            (bool) ($payload['async'] ?? true),
        );

        return $this->json(['success' => true, 'run' => $this->serializeRun($run)]);
    }

    /**
     * Stores an uploaded data file as asset, so a worker on any host can read it via the "asset" param.
     */
    public function uploadAction(Request $request): JsonResponse
    {
        $type = $this->getType($request->request->get('type', Run::TYPE_IMPORT));
        $this->denyUnlessAllowed($type);

        $file = $request->files->get('file');

        if (!$file instanceof UploadedFile || !$file->isValid()) {
            throw new BadRequestHttpException('No valid file uploaded');
        }

        $folder = Asset\Service::createFolderByPath(rtrim($this->uploadFolder, '/') . '/' . date('Y-m-d'));

        if (!$folder instanceof Asset\Folder) {
            throw new \RuntimeException(sprintf('Could not create upload folder "%s"', $this->uploadFolder));
        }

        $filename = ElementService::getValidKey(
            sprintf('%s_%s', date('His'), $file->getClientOriginalName()),
            'asset',
        );

        $asset = Asset::create($folder->getId(), [
            'filename' => $filename,
            'sourcePath' => $file->getPathname(),
            'userOwner' => $this->getPimcoreUser()->getId(),
            'userModification' => $this->getPimcoreUser()->getId(),
        ]);

        return $this->json([
            'success' => true,
            'asset' => $asset->getRealFullPath(),
            'id' => $asset->getId(),
        ]);
    }

    public function listAction(Request $request): JsonResponse
    {
        $type = $request->query->get('type');
        $type = null !== $type && '' !== $type ? $this->getType($type) : null;

        $allowedTypes = array_values(array_filter(
            array_keys(self::PERMISSIONS),
            fn (string $candidate): bool => $this->isAllowed($candidate),
        ));

        if (null !== $type) {
            $this->denyUnlessAllowed($type);
        } elseif (\count($allowedTypes) === 1) {
            $type = $allowedTypes[0];
        } elseif ($allowedTypes === []) {
            throw new AccessDeniedHttpException();
        }

        $definition = $request->query->get('definition');
        $statuses = array_filter(explode(',', (string) $request->query->get('status', '')));

        $result = $this->runRepository->findByFilter(
            $type,
            null !== $definition && '' !== $definition ? (int) $definition : null,
            $statuses,
            min(200, max(1, $request->query->getInt('limit', 25))),
            max(0, $request->query->getInt('offset', 0)),
        );

        return $this->json([
            'success' => true,
            'total' => $result['total'],
            'data' => array_map(fn (Run $run): array => $this->serializeRun($run), $result['items']),
        ]);
    }

    public function getAction(int $id): JsonResponse
    {
        $run = $this->getRun($id);

        return $this->json(['success' => true, 'run' => $this->serializeRun($run)]);
    }

    public function logsAction(Request $request, int $id): JsonResponse
    {
        $run = $this->getRun($id);

        $result = $this->runRepository->findLogs(
            (int) $run->getId(),
            array_filter(explode(',', (string) $request->query->get('level', ''))),
            $request->query->get('search'),
            min(1000, max(1, $request->query->getInt('limit', 100))),
            max(0, $request->query->getInt('offset', 0)),
        );

        return $this->json([
            'success' => true,
            'total' => $result['total'],
            'data' => $result['items'],
        ]);
    }

    public function stopAction(int $id): JsonResponse
    {
        $run = $this->getRun($id);
        $this->runManager->requestStop($run);

        return $this->json(['success' => true, 'run' => $this->serializeRun($this->getRun($id))]);
    }

    public function rerunAction(Request $request, int $id): JsonResponse
    {
        $run = $this->getRun($id);
        $payload = $this->getPayload($request);

        $newRun = $this->runManager->rerun(
            $run,
            RunTrigger::RERUN,
            $this->getPimcoreUser()->getId(),
            (bool) ($payload['async'] ?? true),
        );

        return $this->json(['success' => true, 'run' => $this->serializeRun($newRun)]);
    }

    public function deleteAction(int $id): JsonResponse
    {
        $run = $this->getRun($id);

        if ($run->isActive()) {
            throw new BadRequestHttpException('An active run cannot be deleted, stop it first');
        }

        $this->runRepository->remove($run);
        $this->runNotifier->runDeleted($run);

        return $this->json(['success' => true]);
    }

    private function serializeRun(Run $run): array
    {
        return $this->runNormalizer->normalize($run);
    }

    private function getRun(int $id): Run
    {
        $run = $this->runRepository->find($id);

        if (null === $run) {
            throw new NotFoundHttpException(sprintf('Run %d not found', $id));
        }

        $this->denyUnlessAllowed($run->getType());

        return $run;
    }

    private function getDefinition(string $type, mixed $idOrName): \Instride\Bundle\DataDefinitionsBundle\Model\DataDefinitionInterface
    {
        if (!\is_scalar($idOrName) || '' === (string) $idOrName) {
            throw new BadRequestHttpException('"definition" is required');
        }

        try {
            return $this->runManager->getDefinition($type, (string) $idOrName);
        } catch (InvalidArgumentException $exception) {
            throw new NotFoundHttpException($exception->getMessage(), $exception);
        }
    }

    private function getType(mixed $type): string
    {
        if (!\is_string($type) || !isset(self::PERMISSIONS[$type])) {
            throw new BadRequestHttpException('"type" has to be "import" or "export"');
        }

        return $type;
    }

    private function getPayload(Request $request): array
    {
        if ('' === $request->getContent()) {
            return $request->request->all();
        }

        try {
            $payload = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new BadRequestHttpException('Invalid JSON: ' . $exception->getMessage(), $exception);
        }

        return \is_array($payload) ? $payload : [];
    }

    private function getPimcoreUser(): User
    {
        $user = $this->getUser();

        if (!$user instanceof SecurityUser) {
            throw new AccessDeniedHttpException();
        }

        return $user->getUser();
    }

    private function isAllowed(string $type): bool
    {
        return $this->getPimcoreUser()->isAllowed(self::PERMISSIONS[$type]);
    }

    private function denyUnlessAllowed(string $type): void
    {
        if (!$this->isAllowed($type)) {
            throw new AccessDeniedHttpException();
        }
    }
}
