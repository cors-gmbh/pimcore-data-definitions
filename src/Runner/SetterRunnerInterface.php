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

namespace Instride\Bundle\DataDefinitionsBundle\Runner;

use Instride\Bundle\DataDefinitionsBundle\Model\DataDefinitionInterface;
use Instride\Bundle\DataDefinitionsBundle\Model\MappingInterface;
use Pimcore\Model\DataObject\Concrete;

interface SetterRunnerInterface extends RunnerInterface
{
    public function shouldSetField(
        Concrete $object,
        MappingInterface $map,
        $value,
        array $data,
        DataDefinitionInterface $definition,
        array $params,
    );
}
