<?php

/*
 * This file is part of the puli/asset-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\AssetPlugin\Tests\Installation;

use PHPUnit_Framework_MockObject_MockObject;
use Puli\AssetPlugin\Api\Installation\InstallationParams;
use Puli\AssetPlugin\Api\Installer\InstallerDescriptor;
use Puli\AssetPlugin\Api\Installer\InstallerManager;
use Puli\AssetPlugin\Api\Installer\InstallerParameter;
use Puli\AssetPlugin\Api\Target\InstallTarget;
use Puli\AssetPlugin\Api\Target\InstallTargetCollection;
use Puli\AssetPlugin\Api\WebPath\WebPathMapping;
use Puli\AssetPlugin\Installation\InstallationManagerImpl;
use Puli\AssetPlugin\Tests\Installation\Fixtures\TestInstaller;
use Puli\Manager\Tests\ManagerTestCase;
use Puli\Repository\Resource\Collection\ArrayResourceCollection;
use Puli\Repository\Resource\GenericResource;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class InstallationManagerImplTest extends ManagerTestCase
{
    const INSTALLER_CLASS = 'Puli\AssetPlugin\Tests\Installation\Fixtures\TestInstaller';

    const INSTALLER_CLASS_NO_DEFAULT_CONSTRUCTOR = 'Puli\AssetPlugin\Tests\Installation\Fixtures\TestInstallerWithoutDefaultConstructor';

    const INSTALLER_CLASS_INVALID = 'Puli\AssetPlugin\Tests\Installation\Fixtures\TestInstallerInvalid';

    /**
     * @var InstallTargetCollection
     */
    private $targets;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|InstallerManager
     */
    private $installerManager;

    /**
     * @var InstallationManagerImpl
     */
    private $manager;

    protected function setUp()
    {
        $this->initEnvironment(__DIR__.'/Fixtures/home', __DIR__.'/Fixtures/root');

        $this->targets = new InstallTargetCollection();
        $this->installerManager = $this->getMock('Puli\AssetPlugin\Api\Installer\InstallerManager');
        $this->manager = new InstallationManagerImpl(
            $this->environment,
            $this->repo,
            $this->targets,
            $this->installerManager
        );

        TestInstaller::resetValidatedParams();
    }

    public function testPrepareInstallation()
    {
        $resources = new ArrayResourceCollection(array(
            new GenericResource('/path/css'),
            new GenericResource('/path/js'),
        ));
        $installerDescriptor = new InstallerDescriptor('rsync', self::INSTALLER_CLASS, null, array(
            new InstallerParameter('param1', InstallerParameter::REQUIRED),
            new InstallerParameter('param2', InstallerParameter::OPTIONAL, 'default1'),
            new InstallerParameter('param3', InstallerParameter::OPTIONAL, 'default2'),
        ));
        $target = new InstallTarget('server', 'rsync', 'ssh://server/public_html', '/%s', array(
            'param1' => 'custom1',
            'param3' => 'custom2',
        ));
        $mapping = new WebPathMapping('/path/{css,js}', 'server', 'assets');

        $this->targets->add($target);

        $this->repo->expects($this->any())
            ->method('find')
            ->with('/path/{css,js}')
            ->willReturn($resources);

        $this->installerManager->expects($this->any())
            ->method('hasInstallerDescriptor')
            ->with('rsync')
            ->willReturn(true);

        $this->installerManager->expects($this->any())
            ->method('getInstallerDescriptor')
            ->with('rsync')
            ->willReturn($installerDescriptor);

        $params = new InstallationParams(
            new TestInstaller(),
            $installerDescriptor,
            $resources,
            $mapping,
            $target,
            $this->environment->getRootDirectory()
        );

        $this->assertEquals($params, $this->manager->prepareInstallation($mapping));
        $this->assertEquals($params, TestInstaller::getValidatedParams());
    }

    /**
     * @expectedException \Puli\AssetPlugin\Api\Installation\NotInstallableException
     * @expectedExceptionMessage foobar
     * @expectedExceptionCode 3
     */
    public function testFailIfInstallerNotFound()
    {
        $resources = new ArrayResourceCollection(array(
            new GenericResource('/path/css'),
            new GenericResource('/path/js'),
        ));
        $target = new InstallTarget('server', 'foobar', 'ssh://server/public_html');
        $mapping = new WebPathMapping('/path/{css,js}', 'server', 'assets');

        $this->targets->add($target);

        $this->repo->expects($this->any())
            ->method('find')
            ->with('/path/{css,js}')
            ->willReturn($resources);

        $this->installerManager->expects($this->any())
            ->method('hasInstallerDescriptor')
            ->with('foobar')
            ->willReturn(false);

        $this->installerManager->expects($this->never())
            ->method('getInstallerDescriptor');

        $this->manager->prepareInstallation($mapping);
    }

    /**
     * @expectedException \Puli\AssetPlugin\Api\Installation\NotInstallableException
     * @expectedExceptionMessage /path/{css,js}
     * @expectedExceptionCode 4
     */
    public function testFailIfNoResourceMatches()
    {
        $resources = new ArrayResourceCollection();
        $installerDescriptor = new InstallerDescriptor('rsync', self::INSTALLER_CLASS, null, array(
            new InstallerParameter('param1', InstallerParameter::REQUIRED),
            new InstallerParameter('param2', InstallerParameter::OPTIONAL, 'default1'),
            new InstallerParameter('param3', InstallerParameter::OPTIONAL, 'default2'),
        ));
        $target = new InstallTarget('server', 'rsync', 'ssh://server/public_html', '/%s', array(
            'param1' => 'custom1',
            'param3' => 'custom2',
        ));
        $mapping = new WebPathMapping('/path/{css,js}', 'server', 'assets');

        $this->targets->add($target);

        $this->repo->expects($this->any())
            ->method('find')
            ->with('/path/{css,js}')
            ->willReturn($resources);

        $this->installerManager->expects($this->any())
            ->method('hasInstallerDescriptor')
            ->with('rsync')
            ->willReturn(true);

        $this->installerManager->expects($this->any())
            ->method('getInstallerDescriptor')
            ->with('rsync')
            ->willReturn($installerDescriptor);

        $this->manager->prepareInstallation($mapping);
    }

    /**
     * @expectedException \Puli\AssetPlugin\Api\Installation\NotInstallableException
     * @expectedExceptionMessage foobar
     * @expectedExceptionCode 5
     */
    public function testFailIfTargetNotFound()
    {
        $resources = new ArrayResourceCollection(array(
            new GenericResource('/path/css'),
            new GenericResource('/path/js'),
        ));
        $installerDescriptor = new InstallerDescriptor('rsync', self::INSTALLER_CLASS, null, array(
            new InstallerParameter('param1', InstallerParameter::REQUIRED),
            new InstallerParameter('param2', InstallerParameter::OPTIONAL, 'default1'),
            new InstallerParameter('param3', InstallerParameter::OPTIONAL, 'default2'),
        ));
        $mapping = new WebPathMapping('/path/{css,js}', 'foobar', 'assets');

        $this->repo->expects($this->any())
            ->method('find')
            ->with('/path/{css,js}')
            ->willReturn($resources);

        $this->installerManager->expects($this->any())
            ->method('hasInstallerDescriptor')
            ->with('rsync')
            ->willReturn(true);

        $this->installerManager->expects($this->any())
            ->method('getInstallerDescriptor')
            ->with('rsync')
            ->willReturn($installerDescriptor);

        $this->manager->prepareInstallation($mapping);
    }

    /**
     * @expectedException \Puli\AssetPlugin\Api\Installation\NotInstallableException
     * @expectedExceptionMessage Puli\AssetPlugin\Tests\Installation\Foobar
     * @expectedExceptionCode 6
     */
    public function testFailIfInstallerClassNotFound()
    {
        $resources = new ArrayResourceCollection(array(
            new GenericResource('/path/css'),
            new GenericResource('/path/js'),
        ));
        $installerDescriptor = new InstallerDescriptor('rsync', __NAMESPACE__.'\Foobar', null, array(
            new InstallerParameter('param1', InstallerParameter::REQUIRED),
            new InstallerParameter('param2', InstallerParameter::OPTIONAL, 'default1'),
            new InstallerParameter('param3', InstallerParameter::OPTIONAL, 'default2'),
        ));
        $target = new InstallTarget('server', 'rsync', 'ssh://server/public_html', '/%s', array(
            'param1' => 'custom1',
            'param3' => 'custom2',
        ));
        $mapping = new WebPathMapping('/path/{css,js}', 'server', 'assets');

        $this->targets->add($target);

        $this->repo->expects($this->any())
            ->method('find')
            ->with('/path/{css,js}')
            ->willReturn($resources);

        $this->installerManager->expects($this->any())
            ->method('hasInstallerDescriptor')
            ->with('rsync')
            ->willReturn(true);

        $this->installerManager->expects($this->any())
            ->method('getInstallerDescriptor')
            ->with('rsync')
            ->willReturn($installerDescriptor);

        $this->manager->prepareInstallation($mapping);
    }

    /**
     * @expectedException \Puli\AssetPlugin\Api\Installation\NotInstallableException
     * @expectedExceptionMessage Puli\AssetPlugin\Tests\Installation\Fixtures\TestInstallerWithoutDefaultConstructor
     * @expectedExceptionCode 7
     */
    public function testFailIfInstallerClassNoDefaultConstructor()
    {
        $resources = new ArrayResourceCollection(array(
            new GenericResource('/path/css'),
            new GenericResource('/path/js'),
        ));
        $installerDescriptor = new InstallerDescriptor('rsync', self::INSTALLER_CLASS_NO_DEFAULT_CONSTRUCTOR, null, array(
            new InstallerParameter('param1', InstallerParameter::REQUIRED),
            new InstallerParameter('param2', InstallerParameter::OPTIONAL, 'default1'),
            new InstallerParameter('param3', InstallerParameter::OPTIONAL, 'default2'),
        ));
        $target = new InstallTarget('server', 'rsync', 'ssh://server/public_html', '/%s', array(
            'param1' => 'custom1',
            'param3' => 'custom2',
        ));
        $mapping = new WebPathMapping('/path/{css,js}', 'server', 'assets');

        $this->targets->add($target);

        $this->repo->expects($this->any())
            ->method('find')
            ->with('/path/{css,js}')
            ->willReturn($resources);

        $this->installerManager->expects($this->any())
            ->method('hasInstallerDescriptor')
            ->with('rsync')
            ->willReturn(true);

        $this->installerManager->expects($this->any())
            ->method('getInstallerDescriptor')
            ->with('rsync')
            ->willReturn($installerDescriptor);

        $this->manager->prepareInstallation($mapping);
    }

    /**
     * @expectedException \Puli\AssetPlugin\Api\Installation\NotInstallableException
     * @expectedExceptionMessage Puli\AssetPlugin\Tests\Installation\Fixtures\TestInstallerInvalid
     * @expectedExceptionCode 8
     */
    public function testFailIfInstallerClassInvalid()
    {
        $resources = new ArrayResourceCollection(array(
            new GenericResource('/path/css'),
            new GenericResource('/path/js'),
        ));
        $installerDescriptor = new InstallerDescriptor('rsync', self::INSTALLER_CLASS_INVALID, null, array(
            new InstallerParameter('param1', InstallerParameter::REQUIRED),
            new InstallerParameter('param2', InstallerParameter::OPTIONAL, 'default1'),
            new InstallerParameter('param3', InstallerParameter::OPTIONAL, 'default2'),
        ));
        $target = new InstallTarget('server', 'rsync', 'ssh://server/public_html', '/%s', array(
            'param1' => 'custom1',
            'param3' => 'custom2',
        ));
        $mapping = new WebPathMapping('/path/{css,js}', 'server', 'assets');

        $this->targets->add($target);

        $this->repo->expects($this->any())
            ->method('find')
            ->with('/path/{css,js}')
            ->willReturn($resources);

        $this->installerManager->expects($this->any())
            ->method('hasInstallerDescriptor')
            ->with('rsync')
            ->willReturn(true);

        $this->installerManager->expects($this->any())
            ->method('getInstallerDescriptor')
            ->with('rsync')
            ->willReturn($installerDescriptor);

        $this->manager->prepareInstallation($mapping);
    }

    public function testInstallResource()
    {
        $resources = new ArrayResourceCollection(array(
            $first = new GenericResource('/path/css'),
            $second = new GenericResource('/path/js'),
        ));

        $installer = $this->getMock('Puli\AssetPlugin\Api\Installer\ResourceInstaller');
        $installerDescriptor = new InstallerDescriptor('symlink', get_class($installer));
        $target = new InstallTarget('server', 'rsync', 'ssh://server/public_html');
        $mapping = new WebPathMapping('/path/{css,js}', 'server', 'assets');

        $params = new InstallationParams(
            $installer,
            $installerDescriptor,
            $resources,
            $mapping,
            $target,
            $this->environment->getRootDirectory()
        );

        $installer->expects($this->at(0))
            ->method('validateParams')
            ->with($params);
        $installer->expects($this->at(1))
            ->method('installResource')
            ->with($first, $params);

        $this->manager->installResource($first, $params);
    }
}
