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

namespace Instride\Bundle\DataDefinitionsBundle\Form\Type;

use Instride\Bundle\DataDefinitionsBundle\Model\DataDefinitionInterface;
use Instride\Bundle\DataDefinitionsBundle\Repository\DefinitionRepository;
use Symfony\Bridge\Doctrine\Form\DataTransformer\CollectionToArrayTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DefinitionChoiceType extends AbstractType
{
    public function __construct(
        private readonly DefinitionRepository $definitionRepository,
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['multiple']) {
            $builder->addModelTransformer(new CollectionToArrayTransformer());
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults(
                [
                    'choices' => function (Options $options) {
                        return array_map(static function (DataDefinitionInterface $def) {
                            return $def->getId();
                        }, $this->definitionRepository->findAll());
                    },
                    'choice_label' => function ($val) {
                        $def = $this->definitionRepository->find($val);

                        return $def !== null ? $def->getName() : null;
                    },
                    'choice_translation_domain' => false,
                    'active' => true,
                ],
            )
        ;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
