<?php

declare(strict_types=1);

/*
 * This source file is available under two different licenses:
 *  - Data Definitions Commercial License (DDCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) CORS GmbH (https://www.cors.gmbh) in combination with instride AG (https://instride.ch)
 * @license    DDCL
 */

namespace Instride\Bundle\DataDefinitionsBundle\Interpreter;

use Instride\Bundle\DataDefinitionsBundle\Context\InterpreterContextInterface;

final class CheckboxInterpreter implements InterpreterInterface
{
    #[\Override]
    public function interpret(InterpreterContextInterface $context): ?bool
    {
        if (is_string($context->getValue())) {
            if ($context->getValue() === '') {
                return null;
            }

            return filter_var(strtolower($context->getValue()), \FILTER_VALIDATE_BOOLEAN, \FILTER_NULL_ON_FAILURE);
        }

        return (bool) $context->getValue();
    }
}
