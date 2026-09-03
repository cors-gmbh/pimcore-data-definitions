<?php
/**
 * Import Definitions.
 *
 * LICENSE
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) CORS GmbH (https://www.cors.gmbh)
 * @license    DDCL
 */

namespace Instride\Bundle\DataDefinitionsBundle\Behat\Context\Transform;

use Behat\Behat\Context\Context;
use Instride\Bundle\DataDefinitionsBundle\Behat\Service\SharedStorageInterface;
use Pimcore\Model\Asset;

final class PimcoreAssetContext implements Context
{
    public function __construct(
        private readonly SharedStorageInterface $sharedStorage
    ) {

    }

    /**
     * @Transform /^asset "([^"]+)"$/
     */
    public function objectInstanceWithKey(string $path): Asset
    {
        return Asset::getByPath($path);
    }
}
