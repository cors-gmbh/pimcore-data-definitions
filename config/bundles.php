<?php

return [
    Pimcore\Bundle\ApplicationLoggerBundle\PimcoreApplicationLoggerBundle::class => ['all' => true],
    Pimcore\Bundle\GenericExecutionEngineBundle\PimcoreGenericExecutionEngineBundle::class => ['all' => true],
    Pimcore\Bundle\OpenSearchClientBundle\PimcoreOpenSearchClientBundle::class => ['all' => true],
    Pimcore\Bundle\GenericDataIndexBundle\PimcoreGenericDataIndexBundle::class => ['all' => true],
    Pimcore\Bundle\StudioBackendBundle\PimcoreStudioBackendBundle::class => ['all' => true],
    Pimcore\Bundle\StudioUiBundle\PimcoreStudioUiBundle::class => ['all' => true],
    // Behat (features/, tests/DataDefinitionsBundle/Behat) runs in the test environment only.
    FriendsOfBehat\SymfonyExtension\Bundle\FriendsOfBehatSymfonyExtensionBundle::class => ['test' => true],
];
