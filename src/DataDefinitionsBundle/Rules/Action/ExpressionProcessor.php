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

namespace Instride\Bundle\DataDefinitionsBundle\Rules\Action;

use Instride\Bundle\DataDefinitionsBundle\Rules\Model\ImportRuleInterface;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final class ExpressionProcessor implements ImportRuleProcessorInterface
{
    private ExpressionLanguage $expressionLanguage;

    private ContainerInterface $container;

    public function __construct(
        ExpressionLanguage $expressionLanguage,
        ContainerInterface $container,
    ) {
        $this->expressionLanguage = $expressionLanguage;
        $this->container = $container;
    }

    public function apply(
        ImportRuleInterface $rule,
        Concrete $concrete,
        $value,
        array $configuration,
        array $params = [],
    ) {
        $expression = $configuration['expression'];

        return $this->expressionLanguage->evaluate(
            $expression,
            array_merge($params, ['container' => $this->container]),
        );
    }
}
