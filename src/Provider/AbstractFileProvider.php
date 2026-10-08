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

namespace Instride\Bundle\DataDefinitionsBundle\Provider;

use Instride\Bundle\DataDefinitionsBundle\Model\DataDefinitionInterface;
use Instride\Bundle\DataDefinitionsBundle\Model\ImportDefinitionInterface;
use Instride\Bundle\DataDefinitionsBundle\Run\ParamsSchema\ParamField;
use Instride\Bundle\DataDefinitionsBundle\Run\ParamsSchema\ParamsSchemaProviderInterface;
use Instride\Bundle\DataDefinitionsBundle\Service\StorageLocator;
use Pimcore\File;
use Pimcore\Helper\LongRunningHelper;
use Pimcore\Model\Asset;

abstract class AbstractFileProvider implements ParamsSchemaProviderInterface
{
    public function __construct(
        protected StorageLocator $storageLocator,
        protected LongRunningHelper $longRunningHelper,
    ) {
    }

    public function getParamsSchema(DataDefinitionInterface $definition): array
    {
        if (!$definition instanceof ImportDefinitionInterface) {
            return [];
        }

        return [
            new ParamField('asset', 'asset', 'Asset', description: 'Asset path of the file to import, or upload a file', group: 'source'),
            new ParamField('storage', 'text', 'Storage', description: 'Flysystem storage name, used together with "file"', group: 'source'),
            new ParamField('file', 'text', 'File', description: 'Path of the file (inside the storage, or on the server when no storage is given)', group: 'source'),
        ];
    }

    protected function getFile(array $params): string
    {
//        if (!str_starts_with($file, '/')) {
//            $file = sprintf('%s/%s', PIMCORE_PROJECT_ROOT, $file);
//        }

        if (isset($params['asset'])) {
            $asset = Asset::getByPath($params['asset']);

            if (!$asset) {
                throw new \RuntimeException(sprintf('Asset "%s" not found', $params['asset']));
            }

            return $this->createTemporaryFileFromStream($asset->getStream());
        }

        if (isset($params['storage'], $params['file'])) {
            $storage = $this->storageLocator->getStorage($params['storage']);

            if (!$storage->fileExists($params['file'])) {
                throw new \RuntimeException(sprintf('File "%s" in Storage "%s" not found', $params['file'], $params['storage']));
            }

            return $this->createTemporaryFileFromStream($storage->readStream($params['file']));
        }

        if (isset($params['file'])) {
            return $params['file'];
        }

        throw new \RuntimeException('No file or asset given');
    }

    protected function createTemporaryFileFromStream($stream)
    {
        if (is_string($stream)) {
            $src = fopen($stream, 'rb');
            $fileExtension = pathinfo($stream, \PATHINFO_EXTENSION);
        } else {
            $src = $stream;
            $streamMeta = stream_get_meta_data($src);
            $fileExtension = pathinfo($streamMeta['uri'], \PATHINFO_EXTENSION);
        }

        $tmpFilePath = File::getLocalTempFilePath($fileExtension);

        $dest = fopen($tmpFilePath, 'wb', false, File::getContext());
        if (!$dest) {
            throw new \Exception(sprintf('Unable to create temporary file in %s', $tmpFilePath));
        }

        stream_copy_to_stream($src, $dest);
        fclose($dest);

        $this->longRunningHelper->addTmpFilePath($tmpFilePath);
        register_shutdown_function(static function () use ($tmpFilePath) {
            @unlink($tmpFilePath);
        });

        return $tmpFilePath;
    }
}
