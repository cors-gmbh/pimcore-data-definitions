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

namespace Instride\Bundle\DataDefinitionsBundle\Interpreter;

use Instride\Bundle\DataDefinitionsBundle\Context\InterpreterContextInterface;
use Pimcore\Model\DataObject;

final class SpecificObjectInterpreter implements InterpreterInterface
{
    #[\Override]
    public function interpret(InterpreterContextInterface $context): mixed
    {
        $objectId = $context->getConfiguration()['objectId'];

        return DataObject::getById($objectId);
    }
}
