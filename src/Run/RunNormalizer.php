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

namespace Instride\Bundle\DataDefinitionsBundle\Run;

use Instride\Bundle\DataDefinitionsBundle\Entity\Run;
use Instride\Bundle\DataDefinitionsBundle\Repository\DefinitionRepository;
use Pimcore\Model\User;
use Throwable;

/**
 * Run as returned by the Studio API and pushed via Mercure.
 */
final class RunNormalizer
{
    public function __construct(
        private DefinitionRepository $importDefinitionRepository,
        private DefinitionRepository $exportDefinitionRepository,
    ) {
    }

    public function normalize(Run $run): array
    {
        $data = $run->toArray();

        $user = null !== $run->getUserId() && $run->getUserId() > 0 ? User::getById($run->getUserId()) : null;
        $data['userName'] = $user?->getName();
        $data['definitionName'] = $this->getDefinitionName($run);
        $data['active'] = RunStatus::isActive($run->getStatus());

        return $data;
    }

    private function getDefinitionName(Run $run): ?string
    {
        $repository = $run->isImport() ? $this->importDefinitionRepository : $this->exportDefinitionRepository;

        try {
            return $repository->find($run->getDefinition())?->getName();
        } catch (Throwable) {
            return null;
        }
    }
}
