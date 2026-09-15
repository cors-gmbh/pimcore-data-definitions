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
use Instride\Bundle\DataDefinitionsBundle\Model\ExportDefinitionInterface;
use Instride\Bundle\DataDefinitionsBundle\Model\ImportDefinitionInterface;
use Pimcore\Model\DataObject\Data\ExternalImage;

final class ExternalImageInterpreter implements InterpreterInterface
{
    #[\Override]
    public function interpret(InterpreterContextInterface $context): mixed
    {
        if (($context->getDefinition() instanceof ExportDefinitionInterface) && $context->getValue(
        ) instanceof ExternalImage) {
            return $context->getValue()->getUrl();
        }

        if (($context->getDefinition() instanceof ImportDefinitionInterface) && filter_var(
            $context->getValue(),
            \FILTER_VALIDATE_URL,
        )) {
            return new ExternalImage($context->getValue());
        }

        return null;
    }
}
