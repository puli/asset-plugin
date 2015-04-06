<?php

/*
 * This file is part of the puli/asset-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\AssetPlugin\Api;

use Puli\AssetPlugin\Api\Asset\AssetManager;
use Puli\AssetPlugin\Api\Installation\InstallationManager;
use Puli\AssetPlugin\Api\Installer\InstallerManager;
use Puli\AssetPlugin\Api\Target\InstallTargetManager;
use Puli\AssetPlugin\Api\UrlGenerator\ResourceUrlGenerator;
use Puli\AssetPlugin\Asset\DiscoveryAssetManager;
use Puli\AssetPlugin\Console\WebConsoleConfig;
use Puli\AssetPlugin\Factory\CreateUrlGeneratorMethodGenerator;
use Puli\AssetPlugin\Installation\InstallationManagerImpl;
use Puli\AssetPlugin\Installer\PackageFileInstallerManager;
use Puli\AssetPlugin\Target\PackageFileInstallTargetManager;
use Puli\AssetPlugin\UrlGenerator\DiscoveryUrlGenerator;
use Puli\Manager\Api\Event\GenerateFactoryEvent;
use Puli\Manager\Api\Event\PuliEvents;
use Puli\Manager\Api\Puli;
use Puli\Manager\Api\PuliPlugin;
use RuntimeException;
use Webmozart\Console\Api\Event\ConfigEvent;
use Webmozart\Console\Api\Event\ConsoleEvents;

/**
 * Adds web asset management capabilities to Puli.
 *
 * This class is the service container for all the services required by this
 * plugin.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class AssetPlugin implements PuliPlugin
{
    /**
     * The binding type of asset mappings.
     */
    const BINDING_TYPE = 'puli/asset-mapping';

    /**
     * The binding parameter used for the web path.
     */
    const PATH_PARAMETER = 'path';

    /**
     * The binding parameter used for the install target name.
     */
    const TARGET_PARAMETER = 'target';

    /**
     * The extra key that stores the install target data.
     */
    const INSTALL_TARGETS_KEY = 'install-targets';

    /**
     * The extra key that stores the installer data.
     */
    const INSTALLERS_KEY = 'installers';

    /**
     * @var Puli
     */
    private $puli;

    /**
     * @var AssetManager
     */
    private $assetManager;

    /**
     * @var InstallationManager
     */
    private $installationManager;

    /**
     * @var InstallerManager
     */
    private $installerManager;

    /**
     * @var InstallTargetManager
     */
    private $installTargetManager;

    /**
     * @var ResourceUrlGenerator
     */
    private $urlGenerator;

    /**
     * Activates the plugin.
     *
     * @param Puli $puli The {@link Puli} instance.
     */
    public function activate(Puli $puli)
    {
        $this->puli = $puli;

        $puli->getEventDispatcher()->addListener(ConsoleEvents::CONFIG, array($this, 'handleConfigEvent'));
        $puli->getEventDispatcher()->addListener(PuliEvents::GENERATE_FACTORY, array($this, 'handleGenerateFactoryEvent'));
    }

    /**
     * Returns the {@link Puli} instance.
     *
     * @return Puli The service container with all the Puli core services.
     */
    public function getPuli()
    {
        if (!$this->puli) {
            throw new RuntimeException('The web resource plugin must be activated before accessing its services.');
        }

        return $this->puli;
    }

    /**
     * Returns the asset manager.
     *
     * @return AssetManager The web path manager.
     */
    public function getAssetManager()
    {
        if (!$this->assetManager) {
            $this->assetManager = new DiscoveryAssetManager(
                $this->getPuli()->getDiscoveryManager(),
                $this->getInstallTargetManager()->getTargets()
            );
        }

        return $this->assetManager;
    }

    /**
     * Returns the installation manager.
     *
     * @return InstallationManager The installation manager.
     */
    public function getInstallationManager()
    {
        if (!$this->installationManager) {
            $this->installationManager = new InstallationManagerImpl(
                $this->getPuli()->getEnvironment(),
                $this->getPuli()->getRepository(),
                $this->getInstallTargetManager()->getTargets(),
                $this->getInstallerManager()
            );
        }

        return $this->installationManager;
    }

    /**
     * Returns the installer manager.
     *
     * @return InstallerManager The installer manager.
     */
    public function getInstallerManager()
    {
        if (!$this->installerManager) {
            $this->installerManager = new PackageFileInstallerManager(
                $this->getPuli()->getRootPackageFileManager(),
                $this->getPuli()->getPackageManager()->getPackages()
            );
        }

        return $this->installerManager;
    }

    /**
     * Returns the install target manager.
     *
     * @return InstallTargetManager The install target manager.
     */
    public function getInstallTargetManager()
    {
        if (!$this->installTargetManager) {
            $this->installTargetManager = new PackageFileInstallTargetManager(
                $this->getPuli()->getRootPackageFileManager(),
                $this->getInstallerManager()
            );
        }

        return $this->installTargetManager;
    }

    /**
     * Returns the resource URL generator.
     *
     * @return ResourceUrlGenerator The resource URL generator.
     */
    public function getUrlGenerator()
    {
        if (!$this->urlGenerator) {
            $this->urlGenerator = new DiscoveryUrlGenerator(
                $this->getPuli()->getDiscovery(),
                $this->getInstallTargetManager()->getTargets()
            );
        }

        return $this->urlGenerator;
    }

    /**
     * @param ConfigEvent $event
     *
     * @internal
     */
    public function handleConfigEvent(ConfigEvent $event)
    {
        WebConsoleConfig::addConfiguration($event->getConfig(), $this);
    }

    /**
     * @param GenerateFactoryEvent $event
     *
     * @internal
     */
    public function handleGenerateFactoryEvent(GenerateFactoryEvent $event)
    {
        $generator = new CreateUrlGeneratorMethodGenerator($this->getInstallTargetManager());
        $generator->addCreateUrlGeneratorMethod($event->getFactoryClass());
    }
}
