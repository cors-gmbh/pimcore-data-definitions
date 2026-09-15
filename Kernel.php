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

use Instride\Bundle\DataDefinitionsBundle\DataDefinitionsBundle;
use Pimcore\HttpKernel\BundleCollection\BundleCollection;
use Pimcore\Kernel as PimcoreKernel;

/**
 * Dev-harness kernel. Not part of the distributed composer package (see .gitattributes);
 * only autoloaded through autoload-dev. Behat boots the same class (behat.yml.dist).
 */
class Kernel extends PimcoreKernel
{
    public function registerBundlesToCollection(BundleCollection $collection): void
    {
        // The bundle under development is always on; everything else (Studio,
        // generic data index, Behat extension, …) is registered through config/bundles.php.
        $collection->addBundle(new DataDefinitionsBundle());
    }

    public function boot(): void
    {
        parent::boot();

        // FriendsOfBehat creates the kernel itself, so the Behat contexts only see
        // \Pimcore::getContainer() when the kernel registers itself here.
        \Pimcore::setKernel($this);
    }
}
