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
use Twig\Environment;

final class TwigInterpreter implements InterpreterInterface
{
    private Environment $twig;

    public function __construct(
        Environment $twig,
    ) {
        $this->twig = $twig;
    }

    #[\Override]
    public function interpret(InterpreterContextInterface $context): mixed
    {
        return $this->twig->createTemplate($context->getConfiguration()['template'])->render([
            'value' => $context->getValue(),
            'object' => $context->getObject(),
            'map' => $context->getMapping(),
            'data' => $context->getDataRow(),
            'data_set' => $context->getDataSet(),
            'definition' => $context->getDefinition(),
            'params' => $context->getParams(),
            'configuration' => $context->getConfiguration(),
        ]);
    }
}
