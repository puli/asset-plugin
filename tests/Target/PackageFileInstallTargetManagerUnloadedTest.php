<?php

/*
 * This file is part of the puli/asset-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\AssetPlugin\Tests\Target;

use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use Puli\AssetPlugin\Api\AssetPlugin;
use Puli\AssetPlugin\Api\Installer\InstallerManager;
use Puli\AssetPlugin\Api\Target\InstallTarget;
use Puli\AssetPlugin\Target\PackageFileInstallTargetManager;
use Puli\Manager\Api\Package\RootPackageFileManager;
use Puli\Manager\Tests\TestException;
use Webmozart\Expression\Expr;

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

    protected function setUp()
    {
        $this->packageFileManager = $this->getMock('Puli\Manager\Api\Package\RootPackageFileManager');
        $this->installerManager = $this->getMock('Puli\AssetPlugin\Api\Installer\InstallerManager');
        $this->targetManager = new PackageFileInstallTargetManager($this->packageFileManager, $this->installerManager);

        $this->installerManager->expects($this->any())
            ->method('hasInstallerDescriptor')
            ->willReturnMap(array(
                array('symlink', true),
                array('rsync', true),
            ));
    }

    public function testGetTarget()
    {
        $this->populateDefaultManager();

        $target = new InstallTarget('local', 'symlink', 'web', '/public/%s');

        $this->assertEquals($target, $this->targetManager->getTarget('local'));
    }

    /**
     * @expectedException \Puli\AssetPlugin\Api\Target\NoSuchTargetException
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
            ->with(AssetPlugin::INSTALL_TARGETS_KEY)
            ->willReturn((object) array(
                'local' => (object) array(
                    'installer' => 'symlink',
                    'location' => 'web',
                    'url-format' => '/public/%s',
                    'parameters' => (object) array('param' => 'value'),
                )
            ));

        $target = new InstallTarget('local', 'symlink', 'web', '/public/%s', array(
            'param' => 'value',
        ));

        $this->assertEquals($target, $this->targetManager->getTarget('local'));
    }

    public function testGetTargetWithoutUrlFormat()
    {
        $this->packageFileManager->expects($this->any())
            ->method('getExtraKey')
            ->with(AssetPlugin::INSTALL_TARGETS_KEY)
            ->willReturn((object) array(
                'local' => (object) array(
                    'installer' => 'symlink',
                    'location' => 'web',
                )
            ));

        $target = new InstallTarget('local', 'symlink', 'web');

        $this->assertEquals($target, $this->targetManager->getTarget('local'));
    }

    /**
     * @expectedException \Webmozart\Json\ValidationFailedException
     */
    public function testFailIfKeyNotAnArray()
    {
        $this->packageFileManager->expects($this->any())
            ->method('getExtraKey')
            ->with(AssetPlugin::INSTALL_TARGETS_KEY)
            ->willReturn('foobar');

        $this->targetManager->getTarget('local');
    }

    public function testGetTargets()
    {
        $this->populateDefaultManager();

        $target = new InstallTarget('local', 'symlink', 'web', '/public/%s');

        $collection = $this->targetManager->getTargets();

        $this->assertInstanceOf('Puli\AssetPlugin\Api\Target\InstallTargetCollection', $collection);
        $this->assertEquals(array('local' => $target), $collection->toArray());
    }

    public function testFindTargets()
    {
        $this->packageFileManager->expects($this->any())
            ->method('getExtraKey')
            ->with(AssetPlugin::INSTALL_TARGETS_KEY)
            ->willReturn((object) array(
                'local1' => (object) array(
                    'installer' => 'symlink',
                    'location' => 'web',
                    'url-format' => '/public/%s',
                    'default' => true,
                ),
                'local2' => (object) array(
                    'installer' => 'copy',
                    'location' => 'alternative',
                    'url-format' => '/alternative/%s',
                ),
                'cdn' => (object) array(
                    'installer' => 'rsync',
                    'location' => 'ssh://my.cdn.com',
                    'parameters' => (object) array('param' => 'value'),
                ),
            ));

        $target1 = new InstallTarget('local1', 'symlink', 'web', '/public/%s');
        $target2 = new InstallTarget('local2', 'copy', 'alternative', '/alternative/%s');

        $collection = $this->targetManager->findTargets(Expr::startsWith('local', InstallTarget::NAME));

        $this->assertInstanceOf('Puli\AssetPlugin\Api\Target\InstallTargetCollection', $collection);
        $this->assertEquals(array('local1' => $target1, 'local2' => $target2), $collection->toArray());
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
        $this->assertTrue($this->targetManager->hasTargets(Expr::same('local', InstallTarget::NAME)));
        $this->assertFalse($this->targetManager->hasTargets(Expr::same('foobar', InstallTarget::NAME)));
    }

    public function testHasNoTargets()
    {
        $this->packageFileManager->expects($this->any())
            ->method('getExtraKey')
            ->with(AssetPlugin::INSTALL_TARGETS_KEY)
            ->willReturn(null);

        $this->assertFalse($this->targetManager->hasTargets());
    }

    public function testAddTarget()
    {
        $this->populateDefaultManager();

        $this->packageFileManager->expects($this->once())
            ->method('setExtraKey')
            ->with(AssetPlugin::INSTALL_TARGETS_KEY, (object) array(
                'local' => (object) array(
                    'installer' => 'symlink',
                    'location' => 'web',
                    'url-format' => '/public/%s',
                    'default' => true,
                ),
                'cdn' => (object) array(
                    'installer' => 'rsync',
                    'location' => 'ssh://my.cdn.com',
                ),
            ));

        $target = new InstallTarget('cdn', 'rsync', 'ssh://my.cdn.com');

        $this->targetManager->addTarget($target);

        $this->assertSame($target, $this->targetManager->getTarget('cdn'));
    }

    public function testAddTargetWithUrlFormat()
    {
        $this->populateDefaultManager();

        $this->packageFileManager->expects($this->once())
            ->method('setExtraKey')
            ->with(AssetPlugin::INSTALL_TARGETS_KEY, (object) array(
                'local' => (object) array(
                    'installer' => 'symlink',
                    'location' => 'web',
                    'url-format' => '/public/%s',
                    'default' => true,
                ),
                'cdn' => (object) array(
                    'installer' => 'rsync',
                    'location' => 'ssh://my.cdn.com',
                    'url-format' => 'http://my.cdn.com/%s'
                ),
            ));

        $target = new InstallTarget('cdn', 'rsync', 'ssh://my.cdn.com', 'http://my.cdn.com/%s');

        $this->targetManager->addTarget($target);

        $this->assertSame($target, $this->targetManager->getTarget('cdn'));
    }

    public function testAddTargetWithParameters()
    {
        $this->populateDefaultManager();

        $this->packageFileManager->expects($this->once())
            ->method('setExtraKey')
            ->with(AssetPlugin::INSTALL_TARGETS_KEY, (object) array(
                'local' => (object) array(
                    'installer' => 'symlink',
                    'location' => 'web',
                    'url-format' => '/public/%s',
                    'default' => true,
                ),
                'cdn' => (object) array(
                    'installer' => 'rsync',
                    'location' => 'ssh://my.cdn.com',
                    'parameters' => (object) array('param' => 'value'),
                ),
            ));

        $target = new InstallTarget('cdn', 'rsync', 'ssh://my.cdn.com', '/%s', array(
            'param' => 'value',
        ));

        $this->targetManager->addTarget($target);

        $this->assertSame($target, $this->targetManager->getTarget('cdn'));
    }

    /**
     * @expectedException \Puli\AssetPlugin\Api\Installer\NoSuchInstallerException
     */
    public function testAddTargetFailsIfInstallerNotFound()
    {
        $this->populateDefaultManager();

        $this->packageFileManager->expects($this->never())
            ->method('setExtraKey');

        $target = new InstallTarget('cdn', 'foobar', 'ssh://my.cdn.com');

        $this->targetManager->addTarget($target);
    }

    public function testAddTargetRevertsIfSavingFails()
    {
        $this->populateDefaultManager();

        $this->packageFileManager->expects($this->any())
            ->method('getExtraKey')
            ->with(AssetPlugin::INSTALL_TARGETS_KEY)
            ->willReturn((object) array(
                'local' => (object) array(
                    'installer' => 'symlink',
                    'location' => 'web',
                    'url-format' => '/public/%s',
                    'default' => true,
                ),
            ));

        $this->packageFileManager->expects($this->once())
            ->method('setExtraKey')
            ->willThrowException(new TestException());

        $target = new InstallTarget('cdn', 'rsync', 'ssh://my.cdn.com');

        try {
            $this->targetManager->addTarget($target);
            $this->fail('Expected a TestException');
        } catch (TestException $e) {
        }

        $this->assertTrue($this->targetManager->hasTarget('local'));
        $this->assertFalse($this->targetManager->hasTarget('cdn'));
    }

    public function testRemoveTarget()
    {
        $this->packageFileManager->expects($this->any())
            ->method('getExtraKey')
            ->with(AssetPlugin::INSTALL_TARGETS_KEY)
            ->willReturn((object) array(
                'local' => (object) array(
                    'installer' => 'symlink',
                    'location' => 'web',
                    'url-format' => '/public/%s',
                    'default' => true,
                ),
                'cdn' => (object) array(
                    'installer' => 'rsync',
                    'location' => 'ssh://my.cdn.com',
                    'parameters' => (object) array('param' => 'value'),
                ),
            ));

        $this->packageFileManager->expects($this->once())
            ->method('setExtraKey')
            ->with(AssetPlugin::INSTALL_TARGETS_KEY, (object) array(
                'cdn' => (object) array(
                    'installer' => 'rsync',
                    'location' => 'ssh://my.cdn.com',
                    'parameters' => (object) array('param' => 'value'),
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
            ->with(AssetPlugin::INSTALL_TARGETS_KEY);

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

    public function testRemoveTargetRevertsIfSavingFails()
    {
        $this->packageFileManager->expects($this->any())
            ->method('getExtraKey')
            ->with(AssetPlugin::INSTALL_TARGETS_KEY)
            ->willReturn((object) array(
                'local' => (object) array(
                    'installer' => 'symlink',
                    'location' => 'web',
                    'url-format' => '/public/%s',
                    'default' => true,
                ),
                'cdn' => (object) array(
                    'installer' => 'rsync',
                    'location' => 'ssh://my.cdn.com',
                    'parameters' => (object) array('param' => 'value'),
                ),
            ));

        $this->packageFileManager->expects($this->once())
            ->method('setExtraKey')
            ->willThrowException(new TestException());

        try {
            $this->targetManager->removeTarget('local');
            $this->fail('Expected a TestException');
        } catch (TestException $e) {
        }

        $this->assertTrue($this->targetManager->hasTarget('local'));
        $this->assertTrue($this->targetManager->hasTarget('cdn'));
    }

    public function testRemoveTargets()
    {
        $this->packageFileManager->expects($this->any())
            ->method('getExtraKey')
            ->with(AssetPlugin::INSTALL_TARGETS_KEY)
            ->willReturn((object) array(
                'local1' => (object) array(
                    'installer' => 'symlink',
                    'location' => 'web',
                    'url-format' => '/public/%s',
                    'default' => true,
                ),
                'local2' => (object) array(
                    'installer' => 'copy',
                    'location' => 'alternative',
                    'url-format' => '/alternative/%s',
                ),
                'cdn' => (object) array(
                    'installer' => 'rsync',
                    'location' => 'ssh://my.cdn.com',
                    'parameters' => (object) array('param' => 'value'),
                ),
            ));

        $this->packageFileManager->expects($this->once())
            ->method('setExtraKey')
            ->with(AssetPlugin::INSTALL_TARGETS_KEY, (object) array(
                'cdn' => (object) array(
                    'installer' => 'rsync',
                    'location' => 'ssh://my.cdn.com',
                    'parameters' => (object) array('param' => 'value'),
                    'default' => true,
                ),
            ));

        $this->targetManager->removeTargets(Expr::startsWith('local', InstallTarget::NAME));

        $this->assertFalse($this->targetManager->hasTarget('local1'));
        $this->assertFalse($this->targetManager->hasTarget('local2'));
        $this->assertTrue($this->targetManager->hasTarget('cdn'));
    }

    public function testRemoveTargetsRevertsIfSavingFails()
    {
        $this->packageFileManager->expects($this->any())
            ->method('getExtraKey')
            ->with(AssetPlugin::INSTALL_TARGETS_KEY)
            ->willReturn((object) array(
                'local' => (object) array(
                    'installer' => 'symlink',
                    'location' => 'web',
                    'url-format' => '/public/%s',
                    'default' => true,
                ),
                'cdn' => (object) array(
                    'installer' => 'rsync',
                    'location' => 'ssh://my.cdn.com',
                    'parameters' => (object) array('param' => 'value'),
                ),
            ));

        $this->packageFileManager->expects($this->once())
            ->method('setExtraKey')
            ->willThrowException(new TestException());

        try {
            $this->targetManager->removeTargets(Expr::startsWith('local', InstallTarget::NAME));
            $this->fail('Expected a TestException');
        } catch (TestException $e) {
        }

        $this->assertTrue($this->targetManager->hasTarget('local'));
        $this->assertTrue($this->targetManager->hasTarget('cdn'));
    }

    public function testClearTargets()
    {
        $this->packageFileManager->expects($this->any())
            ->method('getExtraKey')
            ->with(AssetPlugin::INSTALL_TARGETS_KEY)
            ->willReturn((object) array(
                'local' => (object) array(
                    'installer' => 'symlink',
                    'location' => 'web',
                    'url-format' => '/public/%s',
                    'default' => true,
                ),
                'cdn' => (object) array(
                    'installer' => 'rsync',
                    'location' => 'ssh://my.cdn.com',
                    'parameters' => (object) array('param' => 'value'),
                ),
            ));

        $this->packageFileManager->expects($this->once())
            ->method('removeExtraKey')
            ->with(AssetPlugin::INSTALL_TARGETS_KEY);

        $this->targetManager->clearTargets();

        $this->assertFalse($this->targetManager->hasTarget('local1'));
        $this->assertFalse($this->targetManager->hasTarget('cdn'));
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
            ->with(AssetPlugin::INSTALL_TARGETS_KEY)
            ->willReturn((object) array(
                'local' => (object) array(
                    'installer' => 'symlink',
                    'location' => 'web',
                    'url-format' => '/public/%s',
                    'default' => true,
                ),
                'cdn' => (object) array(
                    'installer' => 'rsync',
                    'location' => 'ssh://my.cdn.com',
                    'parameters' => (object) array('param' => 'value'),
                ),
            ));

        $this->packageFileManager->expects($this->any())
            ->method('setExtraKey')
            ->with(AssetPlugin::INSTALL_TARGETS_KEY, (object) array(
                'local' => (object) array(
                    'installer' => 'symlink',
                    'location' => 'web',
                    'url-format' => '/public/%s',
                ),
                'cdn' => (object) array(
                    'installer' => 'rsync',
                    'location' => 'ssh://my.cdn.com',
                    'parameters' => (object) array('param' => 'value'),
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
            ->with(AssetPlugin::INSTALL_TARGETS_KEY)
            ->willReturn((object) array(
                'local' => (object) array(
                    'installer' => 'symlink',
                    'location' => 'web',
                    'url-format' => '/public/%s',
                    'default' => true,
                )
            ));
    }
}
