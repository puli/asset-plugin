<?php

/*
 * This file is part of the puli/web-resource-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\WebResourcePlugin\Tests\Api;

use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use Puli\Discovery\Api\ResourceDiscovery;
use Puli\RepositoryManager\Api\Discovery\DiscoveryManager;
use Puli\RepositoryManager\Api\Environment\ProjectEnvironment;
use Puli\RepositoryManager\Api\Package\PackageCollection;
use Puli\RepositoryManager\Api\Package\PackageManager;
use Puli\RepositoryManager\Api\Package\RootPackageFileManager;
use Puli\RepositoryManager\Api\Puli;
use Puli\WebResourcePlugin\Api\WebResourcePlugin;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class WebResourcePluginTest extends PHPUnit_Framework_TestCase
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
     * @var WebResourcePlugin
     */
    private $plugin;

    protected function setUp()
    {
        $this->environment = $this->getMock('Puli\RepositoryManager\Api\Environment\ProjectEnvironment');
        $this->dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->puli = $this->getMock('Puli\RepositoryManager\Api\Puli');
        $this->rootPackageFileManager = $this->getMock('Puli\RepositoryManager\Api\Package\RootPackageFileManager');
        $this->discovery = $this->getMock('Puli\Discovery\Api\ResourceDiscovery');
        $this->discoveryManager = $this->getMock('Puli\RepositoryManager\Api\Discovery\DiscoveryManager');
        $this->packageManager = $this->getMock('Puli\RepositoryManager\Api\Package\PackageManager');
        $this->plugin = new WebResourcePlugin();

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
        $this->dispatcher->expects($this->once())
            ->method('addListener');

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

    public function testGetWebPathManager()
    {
        $this->plugin->activate($this->puli);

        $manager = $this->plugin->getWebPathManager();

        $this->assertInstanceOf('Puli\WebResourcePlugin\Api\WebPath\WebPathManager', $manager);

        $this->assertSame($manager, $this->plugin->getWebPathManager());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetWebPathManagerFailsIfNotActive()
    {
        $this->plugin->getWebPathManager();
    }

    public function testGetInstallationManager()
    {
        $this->plugin->activate($this->puli);

        $manager = $this->plugin->getInstallationManager();

        $this->assertInstanceOf('Puli\WebResourcePlugin\Api\Installation\InstallationManager', $manager);

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

        $this->assertInstanceOf('Puli\WebResourcePlugin\Api\Installation\Installer\InstallerManager', $manager);

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

        $this->assertInstanceOf('Puli\WebResourcePlugin\Api\Target\InstallTargetManager', $manager);

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

        $this->assertInstanceOf('Puli\WebResourcePlugin\Api\UrlGenerator\ResourceUrlGenerator', $manager);

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
