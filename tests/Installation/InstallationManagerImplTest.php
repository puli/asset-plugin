<?php

/*
 * This file is part of the puli/web-resource-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\WebResourcePlugin\Tests\Installation;

use PHPUnit_Framework_MockObject_MockObject;
use Puli\Repository\Resource\Collection\ArrayResourceCollection;
use Puli\Repository\Resource\GenericResource;
use Puli\RepositoryManager\Tests\ManagerTestCase;
use Puli\WebResourcePlugin\Api\Installation\InstallationRequest;
use Puli\WebResourcePlugin\Api\Installation\Installer\InstallerDescriptor;
use Puli\WebResourcePlugin\Api\Installation\Installer\InstallerManager;
use Puli\WebResourcePlugin\Api\Installation\Installer\InstallerParameter;
use Puli\WebResourcePlugin\Api\Target\InstallTarget;
use Puli\WebResourcePlugin\Api\Target\InstallTargetCollection;
use Puli\WebResourcePlugin\Api\WebPath\WebPathMapping;
use Puli\WebResourcePlugin\Installation\InstallationManagerImpl;
use Puli\WebResourcePlugin\Tests\Installation\Fixtures\TestInstaller;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class InstallationManagerImplTest extends ManagerTestCase
{
    const INSTALLER_CLASS = 'Puli\WebResourcePlugin\Tests\Installation\Fixtures\TestInstaller';

    const INSTALLER_CLASS_NO_DEFAULT_CONSTRUCTOR = 'Puli\WebResourcePlugin\Tests\Installation\Fixtures\TestInstallerWithoutDefaultConstructor';

    const INSTALLER_CLASS_INVALID = 'Puli\WebResourcePlugin\Tests\Installation\Fixtures\TestInstallerInvalid';

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
        $this->installerManager = $this->getMock('Puli\WebResourcePlugin\Api\Installation\Installer\InstallerManager');
        $this->manager = new InstallationManagerImpl(
            $this->environment,
            $this->targets,
            $this->installerManager
        );
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

        $request = new InstallationRequest(
            new TestInstaller(),
            $resources,
            $this->environment->getRootDirectory(),
            '/path',
            'ssh://server/public_html',
            'assets',
            array(
                'param1' => 'custom1',
                'param2' => 'default1',
                'param3' => 'custom2',
            )
        );

        $this->assertEquals($request, $this->manager->prepareInstallation($mapping));
    }

    /**
     * @expectedException \Puli\WebResourcePlugin\Api\Installation\CannotInstallResourcesException
     * @expectedExceptionMessage param1
     * @expectedExceptionCode 1
     */
    public function testFailIfMissingRequiredParameter()
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
     * @expectedException \Puli\WebResourcePlugin\Api\Installation\CannotInstallResourcesException
     * @expectedExceptionMessage foobar
     * @expectedExceptionCode 2
     */
    public function testFailIfUnknownParameter()
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
            'foobar' => 'custom2',
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
     * @expectedException \Puli\WebResourcePlugin\Api\Installation\CannotInstallResourcesException
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
     * @expectedException \Puli\WebResourcePlugin\Api\Installation\CannotInstallResourcesException
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
     * @expectedException \Puli\WebResourcePlugin\Api\Installation\CannotInstallResourcesException
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
     * @expectedException \Puli\WebResourcePlugin\Api\Installation\CannotInstallResourcesException
     * @expectedExceptionMessage Puli\WebResourcePlugin\Tests\Installation\Foobar
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
     * @expectedException \Puli\WebResourcePlugin\Api\Installation\CannotInstallResourcesException
     * @expectedExceptionMessage Puli\WebResourcePlugin\Tests\Installation\Fixtures\TestInstallerWithoutDefaultConstructor
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
     * @expectedException \Puli\WebResourcePlugin\Api\Installation\CannotInstallResourcesException
     * @expectedExceptionMessage Puli\WebResourcePlugin\Tests\Installation\Fixtures\TestInstallerInvalid
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
}
