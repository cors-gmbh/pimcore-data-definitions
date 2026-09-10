<?php

declare(strict_types=1);

/*
 * This source file is available under the Data Definitions Commercial License (DDCL).
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) CORS GmbH (https://www.cors.gmbh) in combination with instride AG (https://instride.ch)
 * @license    DDCL
 */

namespace Instride\Bundle\DataDefinitionsBundle\Studio;

use Pimcore\Bundle\StudioUiBundle\Build\BuildArchive;
use Pimcore\Bundle\StudioUiBundle\Build\BuildArchiveExtractionTrait;
use Pimcore\Bundle\StudioUiBundle\Build\BuildArchiveProviderInterface;

final class WebpackEntryPointProvider implements BuildArchiveProviderInterface
{
    use BuildArchiveExtractionTrait;

    /**
     * The build is shipped as Resources/build-dist/build-<id>.zip and unpacked into
     * Resources/public/studio by Pimcore's BuildArchiveExtractor at cache warmup
     * (pimcore/studio-ui-bundle#3779). The extractor is injected through the trait's
     * #[Required] setter, which the autowired service definition satisfies.
     */
    protected function buildArchive(): BuildArchive
    {
        return new BuildArchive(
            archiveGlob: __DIR__ . '/../Resources/build-dist/build*.zip',
            targetDir: __DIR__ . '/../Resources/public/studio',
        );
    }

    public function getEntryPoints(): array
    {
        return ['exposeRemote'];
    }

    public function getOptionalEntryPoints(): array
    {
        return [];
    }
}
