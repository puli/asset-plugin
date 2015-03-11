<?php

/*
 * This file is part of the puli/web-resource-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\WebResourcePlugin\Tests\Target;

use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use Puli\RepositoryManager\Api\Package\RootPackageFileManager;
use Puli\WebResourcePlugin\Api\Installer\InstallerDescriptor;
use Puli\WebResourcePlugin\Api\Installer\InstallerManager;
use Puli\WebResourcePlugin\Api\Installer\InstallerParameter;
use Puli\WebResourcePlugin\Api\Target\InstallTarget;
use Puli\WebResourcePlugin\Target\PackageFileInstallTargetManager;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PackageFileInstallTargetManagerUnloadedTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PHPUnit_Framework_MockObject_MockObject|RootPackageFileManager
     */
    protected $packageFileManager;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|InstallerManager
     */
    protected $installerManager;

    /**
     * @var PackageFileInstallTargetManager
     */
    protected $targetManager;

    /**
     * @var InstallerDescriptor
     */
    protected $symlinkInstaller;

    /**
     * @var InstallerDescriptor
     */
    protected $rsyncInstaller;

    protected function setUp()
    {
        $this->packageFileManager = $this->getMock('Puli\RepositoryManager\Api\Package\RootPackageFileManager');
        $this->installerManager = $this->getMock('Puli\WebResourcePlugin\Api\Installer\InstallerManager');
        $this->targetManager = new PackageFileInstallTargetManager($this->packageFileManager, $this->installerManager);
        $this->symlinkInstaller = new InstallerDescriptor('symlink', 'Installer\Class', null, array(
             new InstallerParameter('param'),
        ));
        $this->rsyncInstaller = new InstallerDescriptor('rsync', 'Installer\Class', null, array(
             new InstallerParameter('param'),
        ));

        $this->installerManager->expects($this->any())
            ->method('getInstallerDescriptor')
            ->willReturnMap(array(
                array('symlink', $this->symlinkInstaller),
                array('rsync', $this->rsyncInstaller),
            ));
    }

    public function testGetTarget()
    {
        $this->populateDefaultManager();

        $target = new InstallTarget('local', $this->symlinkInstaller, 'web', '/public/%s');

        $this->assertEquals($target, $this->targetManager->getTarget('local'));
    }

    /**
     * @expectedException \Puli\WebResourcePlugin\Api\Target\NoSuchTargetException
     * @expectedExceptionMessage foobar
     */
    public function testGetTargetFailsIfNotFound()
    {
        $this->populateDefaultManager();

        $this->targetManager->getTarget('foobar');
    }

    public function testGetTargetWithParameters()
    {
        $this->packageFileManager->expects($this->any())
            ->method('getExtraKey')
            ->with(PackageFileInstallTargetManager::INSTALL_TARGETS_KEY)
            ->willReturn(array(
                'local' => array(
                    'installer' => 'symlink',
                    'location' => 'web',
                    'url-format' => '/public/%s',
                    'parameters' => array('param' => 'value'),
                )
            ));

        $target = new InstallTarget('local', $this->symlinkInstaller, 'web', '/public/%s', array(
            'param' => 'value',
        ));

        $this->assertEquals($target, $this->targetManager->getTarget('local'));
    }

    public function testGetTargetWithoutUrlFormat()
    {
        $this->packageFileManager->expects($this->any())
            ->method('getExtraKey')
            ->with(PackageFileInstallTargetManager::INSTALL_TARGETS_KEY)
            ->willReturn(array(
                'local' => array(
                    'installer' => 'symlink',
                    'location' => 'web',
                )
            ));

        $target = new InstallTarget('local', $this->symlinkInstaller, 'web');

        $this->assertEquals($target, $this->targetManager->getTarget('local'));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testFailIfMissingInstallerKey()
    {
        $this->packageFileManager->expects($this->any())
            ->method('getExtraKey')
            ->with(PackageFileInstallTargetManager::INSTALL_TARGETS_KEY)
            ->willReturn(array(
                'local' => array(
                    'location' => 'web',
                )
            ));

        $this->targetManager->getTarget('local');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testFailIfMissingLocationKey()
    {
        $this->packageFileManager->expects($this->any())
            ->method('getExtraKey')
            ->with(PackageFileInstallTargetManager::INSTALL_TARGETS_KEY)
            ->willReturn(array(
                'local' => array(
                    'installer' => 'symlink',
                )
            ));

        $this->targetManager->getTarget('local');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testFailIfKeyNotAnArray()
    {
        $this->packageFileManager->expects($this->any())
            ->method('getExtraKey')
            ->with(PackageFileInstallTargetManager::INSTALL_TARGETS_KEY)
            ->willReturn('foobar');

        $this->targetManager->getTarget('local');
    }

    public function testGetTargets()
    {
        $this->populateDefaultManager();

        $target = new InstallTarget('local', $this->symlinkInstaller, 'web', '/public/%s');

        $collection = $this->targetManager->getTargets();

        $this->assertInstanceOf('Puli\WebResourcePlugin\Api\Target\InstallTargetCollection', $collection);
        $this->assertEquals(array('local' => $target), $collection->toArray());
    }

    public function testHasTarget()
    {
        $this->populateDefaultManager();

        $this->assertTrue($this->targetManager->hasTarget('local'));
        $this->assertFalse($this->targetManager->hasTarget('cdn'));
    }

    public function testHasTargets()
    {
        $this->populateDefaultManager();

        $this->assertTrue($this->targetManager->hasTargets());
    }

    public function testHasNoTargets()
    {
        $this->packageFileManager->expects($this->any())
            ->method('getExtraKey')
            ->with(PackageFileInstallTargetManager::INSTALL_TARGETS_KEY, array())
            ->willReturn(array());

        $this->assertFalse($this->targetManager->hasTargets());
    }

    public function testAddTarget()
    {
        $this->populateDefaultManager();

        $this->packageFileManager->expects($this->once())
            ->method('setExtraKey')
            ->with(PackageFileInstallTargetManager::INSTALL_TARGETS_KEY, array(
                'local' => array(
                    'installer' => 'symlink',
                    'location' => 'web',
                    'url-format' => '/public/%s',
                    'default' => true,
                ),
                'cdn' => array(
                    'installer' => 'rsync',
                    'location' => 'ssh://my.cdn.com',
                ),
            ));

        $target = new InstallTarget('cdn', $this->rsyncInstaller, 'ssh://my.cdn.com');

        $this->targetManager->addTarget($target);

        $this->assertSame($target, $this->targetManager->getTarget('cdn'));
    }

    public function testAddTargetWithUrlFormat()
    {
        $this->populateDefaultManager();

        $this->packageFileManager->expects($this->once())
            ->method('setExtraKey')
            ->with(PackageFileInstallTargetManager::INSTALL_TARGETS_KEY, array(
                'local' => array(
                    'installer' => 'symlink',
                    'location' => 'web',
                    'url-format' => '/public/%s',
                    'default' => true,
                ),
                'cdn' => array(
                    'installer' => 'rsync',
                    'location' => 'ssh://my.cdn.com',
                    'url-format' => 'http://my.cdn.com/%s'
                ),
            ));

        $target = new InstallTarget('cdn', $this->rsyncInstaller, 'ssh://my.cdn.com', 'http://my.cdn.com/%s');

        $this->targetManager->addTarget($target);

        $this->assertSame($target, $this->targetManager->getTarget('cdn'));
    }

    public function testAddTargetWithParameters()
    {
        $this->populateDefaultManager();

        $this->packageFileManager->expects($this->once())
            ->method('setExtraKey')
            ->with(PackageFileInstallTargetManager::INSTALL_TARGETS_KEY, array(
                'local' => array(
                    'installer' => 'symlink',
                    'location' => 'web',
                    'url-format' => '/public/%s',
                    'default' => true,
                ),
                'cdn' => array(
                    'installer' => 'rsync',
                    'location' => 'ssh://my.cdn.com',
                    'parameters' => array('param' => 'value'),
                ),
            ));

        $target = new InstallTarget('cdn', $this->rsyncInstaller, 'ssh://my.cdn.com', '/%s', array(
            'param' => 'value',
        ));

        $this->targetManager->addTarget($target);

        $this->assertSame($target, $this->targetManager->getTarget('cdn'));
    }

    public function testRemoveTarget()
    {
        $this->packageFileManager->expects($this->any())
            ->method('getExtraKey')
            ->with(PackageFileInstallTargetManager::INSTALL_TARGETS_KEY)
            ->willReturn(array(
                'local' => array(
                    'installer' => 'symlink',
                    'location' => 'web',
                    'url-format' => '/public/%s',
                    'default' => true,
                ),
                'cdn' => array(
                    'installer' => 'rsync',
                    'location' => 'ssh://my.cdn.com',
                    'parameters' => array('param' => 'value'),
                ),
            ));

        $this->packageFileManager->expects($this->once())
            ->method('setExtraKey')
            ->with(PackageFileInstallTargetManager::INSTALL_TARGETS_KEY, array(
                'cdn' => array(
                    'installer' => 'rsync',
                    'location' => 'ssh://my.cdn.com',
                    'parameters' => array('param' => 'value'),
                    'default' => true,
                ),
            ));

        $this->targetManager->removeTarget('local');

        $this->assertFalse($this->targetManager->hasTarget('local'));
        $this->assertTrue($this->targetManager->hasTarget('cdn'));
    }

    public function testRemoveLastTarget()
    {
        $this->populateDefaultManager();

        $this->packageFileManager->expects($this->once())
            ->method('removeExtraKey')
            ->with(PackageFileInstallTargetManager::INSTALL_TARGETS_KEY);

        $this->targetManager->removeTarget('local');

        $this->assertFalse($this->targetManager->hasTarget('local'));
    }

    public function testRemoveNonExistingTarget()
    {
        $this->populateDefaultManager();

        $this->packageFileManager->expects($this->never())
            ->method('setExtraKey');
        $this->packageFileManager->expects($this->never())
            ->method('removeExtraKey');

        $this->targetManager->removeTarget('foobar');

        $this->assertTrue($this->targetManager->hasTarget('local'));
    }

    public function testGetDefaultTarget()
    {
        $this->populateDefaultManager();

        $this->assertSame($this->targetManager->getTarget('local'), $this->targetManager->getDefaultTarget());
    }

    public function testSetDefaultTarget()
    {
        $this->packageFileManager->expects($this->any())
            ->method('getExtraKey')
            ->with(PackageFileInstallTargetManager::INSTALL_TARGETS_KEY)
            ->willReturn(array(
                'local' => array(
                    'installer' => 'symlink',
                    'location' => 'web',
                    'url-format' => '/public/%s',
                    'default' => true,
                ),
                'cdn' => array(
                    'installer' => 'rsync',
                    'location' => 'ssh://my.cdn.com',
                    'parameters' => array('param' => 'value'),
                ),
            ));

        $this->packageFileManager->expects($this->any())
            ->method('setExtraKey')
            ->with(PackageFileInstallTargetManager::INSTALL_TARGETS_KEY, array(
                'local' => array(
                    'installer' => 'symlink',
                    'location' => 'web',
                    'url-format' => '/public/%s',
                ),
                'cdn' => array(
                    'installer' => 'rsync',
                    'location' => 'ssh://my.cdn.com',
                    'parameters' => array('param' => 'value'),
                    'default' => true,
                ),
            ));

        $this->targetManager->setDefaultTarget('cdn');

        $this->assertSame($this->targetManager->getTarget('cdn'), $this->targetManager->getDefaultTarget());
    }

    protected function populateDefaultManager()
    {
        $this->packageFileManager->expects($this->any())
            ->method('getExtraKey')
            ->with(PackageFileInstallTargetManager::INSTALL_TARGETS_KEY)
            ->willReturn(array(
                'local' => array(
                    'installer' => 'symlink',
                    'location' => 'web',
                    'url-format' => '/public/%s',
                    'default' => true,
                )
            ));
    }
}
