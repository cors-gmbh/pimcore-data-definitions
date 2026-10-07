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

namespace Instride\Bundle\DataDefinitionsBundle\Studio\Mercure;

use Pimcore\Bundle\StudioBackendBundle\Mercure\Provider\AbstractServerToClientProvider;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;

/**
 * Adds the run topics to the Studio subscriber JWT, per run type only for users allowed to see those runs.
 */
final class RunTopicProvider extends AbstractServerToClientProvider
{
    public function __construct(
        private SecurityServiceInterface $securityService,
    ) {
    }

    #[\Override]
    public function getClientSubscribableTopic(): array
    {
        if (!$this->securityService->isLoggedIn()) {
            return [];
        }

        $user = $this->securityService->getCurrentUser();
        $topics = [];

        foreach (RunTopics::PERMISSIONS as $type => $permission) {
            if ($user->isAllowed($permission)) {
                $topics[] = RunTopics::forType($type);
            }
        }

        return $topics;
    }

    #[\Override]
    public function getServerPublishableTopic(): array
    {
        return array_map(RunTopics::forType(...), array_keys(RunTopics::PERMISSIONS));
    }
}
