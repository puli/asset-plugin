<?php

/*
 * This file is part of the puli/web-resource-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\WebResourcePlugin\Tests\Console;

use PHPUnit_Framework_Assert;
use PHPUnit_Framework_MockObject_MockObject;
use Puli\Manager\Tests\TestException;
use Puli\Repository\Resource\Collection\ArrayResourceCollection;
use Puli\Repository\Resource\GenericResource;
use Puli\WebResourcePlugin\Api\Installation\InstallationManager;
use Puli\WebResourcePlugin\Api\Installation\InstallationParams;
use Puli\WebResourcePlugin\Api\Installer\InstallerDescriptor;
use Puli\WebResourcePlugin\Api\Target\InstallTarget;
use Puli\WebResourcePlugin\Api\Target\InstallTargetManager;
use Puli\WebResourcePlugin\Api\WebPath\WebPathManager;
use Puli\WebResourcePlugin\Api\WebPath\WebPathMapping;
use Puli\WebResourcePlugin\Console\WebCommandHandler;
use Rhumsaa\Uuid\Uuid;
use Webmozart\Console\Api\Command\Command;
use Webmozart\Console\Args\StringArgs;
use Webmozart\Expression\Expr;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class WebCommandHandlerTest extends AbstractCommandHandlerTest
{
    const UUID1 = 'e81b32f4-5851-4955-bea7-c90382112cba';

    const UUID2 = '33dbec79-aa8a-48ad-a15e-24e20799075d';

    const UUID3 = '49cfdf53-4720-4548-88d3-564a9faccdc6';

    const UUID4 = '8c64be21-7a1a-4ea8-9a68-5081a54249ef';

    /**
     * @var Command
     */
    private static $listCommand;

    /**
     * @var Command
     */
    private static $addCommand;

    /**
     * @var Command
     */
    private static $removeCommand;

    /**
     * @var Command
     */
    private static $installCommand;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|WebPathManager
     */
    private $webPathManager;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|InstallationManager
     */
    private $installationManager;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|InstallTargetManager
     */
    private $targetManager;

    /**
     * @var WebCommandHandler
     */
    private $handler;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$listCommand = self::$application->getCommand('web')->getSubCommand('list');
        self::$addCommand = self::$application->getCommand('web')->getSubCommand('add');
        self::$removeCommand = self::$application->getCommand('web')->getSubCommand('remove');
        self::$installCommand = self::$application->getCommand('web')->getSubCommand('install');
    }

    protected function setUp()
    {
        parent::setUp();

        $this->webPathManager = $this->getMock('Puli\WebResourcePlugin\Api\WebPath\WebPathManager');
        $this->installationManager = $this->getMock('Puli\WebResourcePlugin\Api\Installation\InstallationManager');
        $this->targetManager = $this->getMock('Puli\WebResourcePlugin\Api\Target\InstallTargetManager');
        $this->handler = new WebCommandHandler($this->webPathManager, $this->installationManager, $this->targetManager);
    }

    public function testListMappings()
    {
        $localTarget = new InstallTarget('local', 'symlink', 'web', '/%s');
        $remoteTarget = new InstallTarget('remote', 'rsync', 'ssh://example.com', 'http://example.com/%s');

        $this->targetManager->expects($this->any())
            ->method('getTarget')
            ->willReturnMap(array(
                array('local', $localTarget),
                array('remote', $remoteTarget),
                array(InstallTarget::DEFAULT_TARGET, $remoteTarget),
            ));

        $this->webPathManager->expects($this->once())
            ->method('getWebPathMappings')
            ->willReturn(array(
                new WebPathMapping('/app/public', 'local', '/', Uuid::fromString(self::UUID1)),
                new WebPathMapping('/acme/blog/public', 'remote', '/blog', Uuid::fromString(self::UUID2)),
                new WebPathMapping('/acme/profiler/public', 'local', '/profiler', Uuid::fromString(self::UUID3)),
                new WebPathMapping('/acme/admin/public', InstallTarget::DEFAULT_TARGET, '/admin', Uuid::fromString(self::UUID4)),
            ));

        $args = self::$listCommand->parseArgs(new StringArgs(''));

        $expected = <<<EOF
The following web resources are currently enabled:

    Target "local"
    Location:   web
    Installer:  symlink
    URL Format: /%s

        e81b32 /app/public           /
        49cfdf /acme/profiler/public /profiler

    Target "remote"
    Location:   ssh://example.com
    Installer:  rsync
    URL Format: http://example.com/%s

        33dbec /acme/blog/public /blog

    Target "default" (current: "remote")
    Location:   ssh://example.com
    Installer:  rsync
    URL Format: http://example.com/%s

        8c64be /acme/admin/public /admin

Use "puli web install" to install the resources in their targets.

EOF;

        $this->assertSame(0, $this->handler->handleList($args, $this->io));
        $this->assertSame($expected, $this->io->fetchOutput());
        $this->assertEmpty($this->io->fetchErrors());
    }

    public function testListEmpty()
    {
        $this->webPathManager->expects($this->once())
            ->method('getWebPathMappings')
            ->willReturn(array());

        $args = self::$listCommand->parseArgs(new StringArgs(''));

        $expected = <<<EOF
No web resources. Use "puli web add <path> <web-path>" to map web resources.

EOF;

        $this->assertSame(0, $this->handler->handleList($args, $this->io));
        $this->assertSame($expected, $this->io->fetchOutput());
        $this->assertEmpty($this->io->fetchErrors());
    }

    public function testAddMapping()
    {
        $this->webPathManager->expects($this->once())
            ->method('addWebPathMapping')
            ->willReturnCallback(function (WebPathMapping $mapping) {
                PHPUnit_Framework_Assert::assertSame('/app/public', $mapping->getGlob());
                PHPUnit_Framework_Assert::assertSame('/', $mapping->getWebPath());
                PHPUnit_Framework_Assert::assertSame(InstallTarget::DEFAULT_TARGET, $mapping->getTargetName());
            });

        $args = self::$addCommand->parseArgs(new StringArgs('/app/public /'));

        $this->assertSame(0, $this->handler->handleAdd($args));
    }

    public function testAddMappingWithTarget()
    {
        $this->webPathManager->expects($this->once())
            ->method('addWebPathMapping')
            ->willReturnCallback(function (WebPathMapping $mapping) {
                PHPUnit_Framework_Assert::assertSame('/app/public', $mapping->getGlob());
                PHPUnit_Framework_Assert::assertSame('/', $mapping->getWebPath());
                PHPUnit_Framework_Assert::assertSame('remote', $mapping->getTargetName());
            });

        $args = self::$addCommand->parseArgs(new StringArgs('/app/public / --target remote'));

        $this->assertSame(0, $this->handler->handleAdd($args));
    }

    public function testRemoveMapping()
    {
        $this->webPathManager->expects($this->at(0))
            ->method('findWebPathMappings')
            ->with(Expr::startsWith(WebPathMapping::UUID, 'abcd'))
            ->willReturn(array(
                $mapping1 = new WebPathMapping('/app/public', 'local', '/'),
                $mapping2 = new WebPathMapping('/acme/blog/public', 'remote', '/blog'),
            ));

        $this->webPathManager->expects($this->at(1))
            ->method('removeWebPathMapping')
            ->with($mapping1->getUuid());

        $this->webPathManager->expects($this->at(2))
            ->method('removeWebPathMapping')
            ->with($mapping2->getUuid());

        $args = self::$removeCommand->parseArgs(new StringArgs('abcd'));

        $this->assertSame(0, $this->handler->handleRemove($args));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testRemoveMappingFailsIfNotFound()
    {
        $this->webPathManager->expects($this->once())
            ->method('findWebPathMappings')
            ->with(Expr::startsWith(WebPathMapping::UUID, 'abcd'))
            ->willReturn(array());

        $this->webPathManager->expects($this->never())
            ->method('removeWebPathMapping');

        $args = self::$removeCommand->parseArgs(new StringArgs('abcd'));

        $this->handler->handleRemove($args);
    }

    public function testInstall()
    {
        $mapping1 = new WebPathMapping('/app/public', 'local', '/');
        $mapping2 = new WebPathMapping('/acme/blog/public/{css,js}', 'remote', '/blog');

        $symlinkInstaller = $this->getMock('Puli\WebResourcePlugin\Api\Installer\ResourceInstaller');
        $symlinkInstallerDescriptor = new InstallerDescriptor('symlink', get_class($symlinkInstaller));
        $rsyncInstaller = $this->getMock('Puli\WebResourcePlugin\Api\Installer\ResourceInstaller');
        $rsyncInstallerDescriptor = new InstallerDescriptor('rsync', get_class($rsyncInstaller));

        $localTarget = new InstallTarget('local', 'symlink', 'public_html');
        $remoteTarget = new InstallTarget('remote', 'rsync', 'ssh://example.com');

        $resource1 = new GenericResource('/app/public');
        $resource2 = new GenericResource('/acme/blog/public/css');
        $resource3 = new GenericResource('/acme/blog/public/js');

        $params1 = new InstallationParams(
            $symlinkInstaller,
            $symlinkInstallerDescriptor,
            new ArrayResourceCollection(array($resource1)),
            $mapping1,
            $localTarget,
            __DIR__
        );
        $params2 = new InstallationParams(
            $rsyncInstaller,
            $rsyncInstallerDescriptor,
            new ArrayResourceCollection(array($resource2, $resource3)),
            $mapping2,
            $remoteTarget,
            __DIR__
        );

        $this->webPathManager->expects($this->once())
            ->method('getWebPathMappings')
            ->willReturn(array($mapping1, $mapping2));

        $this->installationManager->expects($this->at(0))
            ->method('prepareInstallation')
            ->with($mapping1)
            ->willReturn($params1);

        $this->installationManager->expects($this->at(1))
            ->method('prepareInstallation')
            ->with($mapping2)
            ->willReturn($params2);

        $this->installationManager->expects($this->at(2))
            ->method('installResource')
            ->with($resource1, $params1);

        $this->installationManager->expects($this->at(3))
            ->method('installResource')
            ->with($resource2, $params2);

        $this->installationManager->expects($this->at(4))
            ->method('installResource')
            ->with($resource3, $params2);

        $args = self::$installCommand->parseArgs(new StringArgs(''));

        $expected = <<<EOF
Installing /app/public into public_html via symlink...
Installing /acme/blog/public/css into ssh://example.com/blog/css via rsync...
Installing /acme/blog/public/js into ssh://example.com/blog/js via rsync...

EOF;

        $this->assertSame(0, $this->handler->handleInstall($args, $this->io));
        $this->assertSame($expected, $this->io->fetchOutput());
        $this->assertEmpty($this->io->fetchErrors());
    }

    public function testInstallWithTarget()
    {
        $mapping1 = new WebPathMapping('/app/public', 'local', '/');
        $mapping2 = new WebPathMapping('/acme/blog/public/{css,js}', 'local', '/blog');

        $symlinkInstaller = $this->getMock('Puli\WebResourcePlugin\Api\Installer\ResourceInstaller');
        $symlinkInstallerDescriptor = new InstallerDescriptor('symlink', get_class($symlinkInstaller));

        $localTarget = new InstallTarget('local', 'symlink', 'public_html');

        $resource1 = new GenericResource('/app/public');
        $resource2 = new GenericResource('/acme/blog/public/css');
        $resource3 = new GenericResource('/acme/blog/public/js');

        $params1 = new InstallationParams(
            $symlinkInstaller,
            $symlinkInstallerDescriptor,
            new ArrayResourceCollection(array($resource1)),
            $mapping1,
            $localTarget,
            __DIR__
        );
        $params2 = new InstallationParams(
            $symlinkInstaller,
            $symlinkInstallerDescriptor,
            new ArrayResourceCollection(array($resource2, $resource3)),
            $mapping2,
            $localTarget,
            __DIR__
        );

        $this->webPathManager->expects($this->once())
            ->method('findWebPathMappings')
            ->with(Expr::same(WebPathMapping::TARGET_NAME, 'local'))
            ->willReturn(array($mapping1, $mapping2));

        $this->installationManager->expects($this->at(0))
            ->method('prepareInstallation')
            ->with($mapping1)
            ->willReturn($params1);

        $this->installationManager->expects($this->at(1))
            ->method('prepareInstallation')
            ->with($mapping2)
            ->willReturn($params2);

        $this->installationManager->expects($this->at(2))
            ->method('installResource')
            ->with($resource1, $params1);

        $this->installationManager->expects($this->at(3))
            ->method('installResource')
            ->with($resource2, $params2);

        $this->installationManager->expects($this->at(4))
            ->method('installResource')
            ->with($resource3, $params2);

        $args = self::$installCommand->parseArgs(new StringArgs('local'));

        $expected = <<<EOF
Installing /app/public into public_html via symlink...
Installing /acme/blog/public/css into public_html/blog/css via symlink...
Installing /acme/blog/public/js into public_html/blog/js via symlink...

EOF;

        $this->assertSame(0, $this->handler->handleInstall($args, $this->io));
        $this->assertSame($expected, $this->io->fetchOutput());
        $this->assertEmpty($this->io->fetchErrors());
    }

    /**
     * @expectedException \Puli\Manager\Tests\TestException
     */
    public function testInstallDoesNothingIfPrepareFails()
    {
        $mapping1 = new WebPathMapping('/app/public', 'local', '/');
        $mapping2 = new WebPathMapping('/acme/blog/public/{css,js}', 'local', '/blog');

        $symlinkInstaller = $this->getMock('Puli\WebResourcePlugin\Api\Installer\ResourceInstaller');
        $symlinkInstallerDescriptor = new InstallerDescriptor('symlink', get_class($symlinkInstaller));

        $localTarget = new InstallTarget('local', 'symlink', 'public_html');

        $resource1 = new GenericResource('/app/public');

        $params1 = new InstallationParams(
            $symlinkInstaller,
            $symlinkInstallerDescriptor,
            new ArrayResourceCollection(array($resource1)),
            $mapping1,
            $localTarget,
            __DIR__
        );

        $this->webPathManager->expects($this->once())
            ->method('getWebPathMappings')
            ->willReturn(array($mapping1, $mapping2));

        $this->installationManager->expects($this->at(0))
            ->method('prepareInstallation')
            ->with($mapping1)
            ->willReturn($params1);

        $this->installationManager->expects($this->at(1))
            ->method('prepareInstallation')
            ->with($mapping2)
            ->willThrowException(new TestException());

        $this->installationManager->expects($this->never())
            ->method('installResource');

        $args = self::$installCommand->parseArgs(new StringArgs(''));

        $this->handler->handleInstall($args, $this->io);
    }

    public function testInstallNothing()
    {
        $this->webPathManager->expects($this->once())
            ->method('getWebPathMappings')
            ->willReturn(array());

        $this->installationManager->expects($this->never())
            ->method('prepareInstallation');

        $this->installationManager->expects($this->never())
            ->method('installResource');

        $args = self::$installCommand->parseArgs(new StringArgs(''));

        $expected = <<<EOF
Nothing to install.

EOF;

        $this->assertSame(0, $this->handler->handleInstall($args, $this->io));
        $this->assertSame($expected, $this->io->fetchOutput());
        $this->assertEmpty($this->io->fetchErrors());
    }
}
