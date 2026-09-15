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
use Instride\Bundle\DataDefinitionsBundle\Exception\DoNotSetException;

final class DoNotSetOnEmptyInterpreter implements InterpreterInterface
{
    #[\Override]
    public function interpret(InterpreterContextInterface $context): mixed
    {
        if ($context->getValue() === '' || $context->getValue() === null) {
            throw new DoNotSetException();
        }

        if (is_array($context->getValue()) && count($context->getValue()) === 0) {
            throw new DoNotSetException();
        }

        return $context->getValue();
    }
}
