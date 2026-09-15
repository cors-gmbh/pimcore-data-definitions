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

namespace Instride\Bundle\DataDefinitionsBundle;

use Composer\InstalledVersions;
use CoreShop\Bundle\ResourceBundle\AbstractResourceBundle;
use CoreShop\Bundle\ResourceBundle\CoreShopResourceBundle;
use CoreShop\Bundle\RuleBundle\CoreShopRuleBundle;
use Instride\Bundle\DataDefinitionsBundle\DependencyInjection\Compiler\CleanerRegistryCompilerPass;
use Instride\Bundle\DataDefinitionsBundle\DependencyInjection\Compiler\ExportProviderRegistryCompilerPass;
use Instride\Bundle\DataDefinitionsBundle\DependencyInjection\Compiler\ExportRunnerRegistryCompilerPass;
use Instride\Bundle\DataDefinitionsBundle\DependencyInjection\Compiler\FetcherRegistryCompilerPass;
use Instride\Bundle\DataDefinitionsBundle\DependencyInjection\Compiler\FilterRegistryCompilerPass;
use Instride\Bundle\DataDefinitionsBundle\DependencyInjection\Compiler\GetterRegistryCompilerPass;
use Instride\Bundle\DataDefinitionsBundle\DependencyInjection\Compiler\ImportRuleActionPass;
use Instride\Bundle\DataDefinitionsBundle\DependencyInjection\Compiler\ImportRuleConditionPass;
use Instride\Bundle\DataDefinitionsBundle\DependencyInjection\Compiler\InterpreterRegistryCompilerPass;
use Instride\Bundle\DataDefinitionsBundle\DependencyInjection\Compiler\LoaderRegistryCompilerPass;
use Instride\Bundle\DataDefinitionsBundle\DependencyInjection\Compiler\PersisterRegistryCompilerPass;
use Instride\Bundle\DataDefinitionsBundle\DependencyInjection\Compiler\ProviderRegistryCompilerPass;
use Instride\Bundle\DataDefinitionsBundle\DependencyInjection\Compiler\RunnerRegistryCompilerPass;
use Instride\Bundle\DataDefinitionsBundle\DependencyInjection\Compiler\SetterRegistryCompilerPass;
use Pimcore\Extension\Bundle\Installer\InstallerInterface;
use Pimcore\HttpKernel\BundleCollection\BundleCollection;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DataDefinitionsBundle extends AbstractResourceBundle
{
    #[\Override]
    public static function registerDependentBundles(BundleCollection $collection): void
    {
        parent::registerDependentBundles($collection);

        // PimcoreAdminBundle + PimcoreSimpleBackendSearchBundle removed with
        // Pimcore 2026 (classic admin gone) - no longer registered as dependents

        $collection->addBundles([
            new CoreShopRuleBundle(),
        ], 3500);
    }

    #[\Override]
    public function getSupportedDrivers(): array
    {
        return [
            CoreShopResourceBundle::DRIVER_PIMCORE,
        ];
    }

    #[\Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new CleanerRegistryCompilerPass());
        $container->addCompilerPass(new FilterRegistryCompilerPass());
        $container->addCompilerPass(new InterpreterRegistryCompilerPass());
        $container->addCompilerPass(new ProviderRegistryCompilerPass());
        $container->addCompilerPass(new RunnerRegistryCompilerPass());
        $container->addCompilerPass(new SetterRegistryCompilerPass());
        $container->addCompilerPass(new LoaderRegistryCompilerPass());
        $container->addCompilerPass(new GetterRegistryCompilerPass());
        $container->addCompilerPass(new FetcherRegistryCompilerPass());
        $container->addCompilerPass(new ExportProviderRegistryCompilerPass());
        $container->addCompilerPass(new ExportRunnerRegistryCompilerPass());
        $container->addCompilerPass(new ImportRuleConditionPass());
        $container->addCompilerPass(new ImportRuleActionPass());
        $container->addCompilerPass(new PersisterRegistryCompilerPass());
    }

    #[\Override]
    public function getVersion(): string
    {
        if (InstalledVersions::isInstalled('cors/data-definitions')) {
            return InstalledVersions::getVersion('cors/data-definitions');
        }

        return '';
    }

    #[\Override]
    public function getNiceName(): string
    {
        return 'Data Definitions';
    }

    #[\Override]
    public function getDescription(): string
    {
        return 'Data Definitions allows you to create reusable Definitions for Importing all kinds of data into DataObjects.';
    }

    #[\Override]
    public function getInstaller(): ?InstallerInterface
    {
        return $this->container->get(Installer::class);
    }

    // classic-admin asset hooks (getAdminIframePath/getJsPaths/getCssPaths/
    // getEditmodeJsPaths/getEditmodeCssPaths) removed with Pimcore 2026 —
    // the Studio UI ships via WebpackEntryPointProvider instead
}
