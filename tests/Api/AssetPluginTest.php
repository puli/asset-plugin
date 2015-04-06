<?php

/*
 * This file is part of the puli/asset-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\AssetPlugin\Tests\Api;

use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use Puli\AssetPlugin\Api\AssetPlugin;
use Puli\Discovery\Api\ResourceDiscovery;
use Puli\Manager\Api\Discovery\DiscoveryManager;
use Puli\Manager\Api\Environment\ProjectEnvironment;
use Puli\Manager\Api\Event\PuliEvents;
use Puli\Manager\Api\Package\PackageCollection;
use Puli\Manager\Api\Package\PackageManager;
use Puli\Manager\Api\Package\RootPackageFileManager;
use Puli\Manager\Api\Puli;
use Puli\Repository\Api\ResourceRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webmozart\Console\Api\Event\ConsoleEvents;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class AssetPluginTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PHPUnit_Framework_MockObject_MockObject|ProjectEnvironment
     */
    private $environment;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|Puli
     */
    private $puli;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|RootPackageFileManager
     */
    private $rootPackageFileManager;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|ResourceRepository
     */
    private $repo;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|ResourceDiscovery
     */
    private $discovery;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|DiscoveryManager
     */
    private $discoveryManager;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|PackageManager
     */
    private $packageManager;

    /**
     * @var AssetPlugin
     */
    private $plugin;

    protected function setUp()
    {
        $this->environment = $this->getMockBuilder('Puli\Manager\Api\Environment\ProjectEnvironment')
            ->disableOriginalConstructor()
            ->getMock();
        $this->dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->puli = $this->getMock('Puli\Manager\Api\Puli');
        $this->rootPackageFileManager = $this->getMock('Puli\Manager\Api\Package\RootPackageFileManager');
        $this->repo = $this->getMock('Puli\Repository\Api\ResourceRepository');
        $this->discovery = $this->getMock('Puli\Discovery\Api\ResourceDiscovery');
        $this->discoveryManager = $this->getMock('Puli\Manager\Api\Discovery\DiscoveryManager');
        $this->packageManager = $this->getMock('Puli\Manager\Api\Package\PackageManager');
        $this->plugin = new AssetPlugin();

        $this->puli->expects($this->any())
            ->method('getEnvironment')
            ->willReturn($this->environment);

        $this->puli->expects($this->any())
            ->method('getEventDispatcher')
            ->willReturn($this->dispatcher);

        $this->puli->expects($this->any())
            ->method('getRootPackageFileManager')
            ->willReturn($this->rootPackageFileManager);

        $this->puli->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->repo);

        $this->puli->expects($this->any())
            ->method('getDiscovery')
            ->willReturn($this->discovery);

        $this->puli->expects($this->any())
            ->method('getDiscoveryManager')
            ->willReturn($this->discoveryManager);

        $this->puli->expects($this->any())
            ->method('getPackageManager')
            ->willReturn($this->packageManager);

        $this->packageManager->expects($this->any())
            ->method('getPackages')
            ->willReturn(new PackageCollection());
    }

    public function testActivate()
    {
        $this->dispatcher->expects($this->at(0))
            ->method('addListener')
            ->with(ConsoleEvents::CONFIG);
        $this->dispatcher->expects($this->at(1))
            ->method('addListener')
            ->with(PuliEvents::GENERATE_FACTORY);

        $this->plugin->activate($this->puli);
    }

    public function testGetPuli()
    {
        $this->plugin->activate($this->puli);

        $this->assertSame($this->puli, $this->plugin->getPuli());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetPuliFailsIfNotActive()
    {
        $this->plugin->getPuli();
    }

    public function testGetAssetManager()
    {
        $this->plugin->activate($this->puli);

        $manager = $this->plugin->getAssetManager();

        $this->assertInstanceOf('Puli\AssetPlugin\Api\Asset\AssetManager', $manager);

        $this->assertSame($manager, $this->plugin->getAssetManager());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetAssetManagerFailsIfNotActive()
    {
        $this->plugin->getAssetManager();
    }

    public function testGetInstallationManager()
    {
        $this->plugin->activate($this->puli);

        $manager = $this->plugin->getInstallationManager();

        $this->assertInstanceOf('Puli\AssetPlugin\Api\Installation\InstallationManager', $manager);

        $this->assertSame($manager, $this->plugin->getInstallationManager());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetInstallationManagerFailsIfNotActive()
    {
        $this->plugin->getInstallationManager();
    }

    public function testGetInstallerManager()
    {
        $this->plugin->activate($this->puli);

        $manager = $this->plugin->getInstallerManager();

        $this->assertInstanceOf('Puli\AssetPlugin\Api\Installer\InstallerManager', $manager);

        $this->assertSame($manager, $this->plugin->getInstallerManager());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetInstallerManagerFailsIfNotActive()
    {
        $this->plugin->getInstallerManager();
    }

    public function testGetInstallTargetManager()
    {
        $this->plugin->activate($this->puli);

        $manager = $this->plugin->getInstallTargetManager();

        $this->assertInstanceOf('Puli\AssetPlugin\Api\Target\InstallTargetManager', $manager);

        $this->assertSame($manager, $this->plugin->getInstallTargetManager());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetInstallTargetManagerFailsIfNotActive()
    {
        $this->plugin->getInstallTargetManager();
    }

    public function testGetUrlGenerator()
    {
        $this->plugin->activate($this->puli);

        $manager = $this->plugin->getUrlGenerator();

        $this->assertInstanceOf('Puli\AssetPlugin\Api\UrlGenerator\AssetUrlGenerator', $manager);

        $this->assertSame($manager, $this->plugin->getUrlGenerator());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetUrlGeneratorFailsIfNotActive()
    {
        $this->plugin->getUrlGenerator();
    }
}
