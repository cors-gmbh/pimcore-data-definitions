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

namespace Instride\Bundle\DataDefinitionsBundle\Form\Type\Interpreter;

use Instride\Bundle\DataDefinitionsBundle\Form\Type\DefinitionChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

final class DefinitionType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('definition', DefinitionChoiceType::class)
        ;
    }
}
