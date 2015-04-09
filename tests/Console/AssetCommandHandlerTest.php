<?php

/*
 * This file is part of the puli/asset-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\AssetPlugin\Tests\Console;

use PHPUnit_Framework_Assert;
use PHPUnit_Framework_MockObject_MockObject;
use Puli\AssetPlugin\Api\Asset\AssetManager;
use Puli\AssetPlugin\Api\Asset\AssetMapping;
use Puli\AssetPlugin\Api\Installation\InstallationManager;
use Puli\AssetPlugin\Api\Installation\InstallationParams;
use Puli\AssetPlugin\Api\Installer\InstallerDescriptor;
use Puli\AssetPlugin\Api\Target\InstallTarget;
use Puli\AssetPlugin\Api\Target\InstallTargetManager;
use Puli\AssetPlugin\Console\AssetCommandHandler;
use Puli\Manager\Tests\TestException;
use Puli\Repository\Resource\Collection\ArrayResourceCollection;
use Puli\Repository\Resource\GenericResource;
use Rhumsaa\Uuid\Uuid;
use Webmozart\Console\Api\Command\Command;
use Webmozart\Console\Args\StringArgs;
use Webmozart\Expression\Expr;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class AssetCommandHandlerTest extends AbstractCommandHandlerTest
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
    private static $mapCommand;

    /**
     * @var Command
     */
    private static $removeCommand;

    /**
     * @var Command
     */
    private static $installCommand;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|AssetManager
     */
    private $assetManager;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|InstallationManager
     */
    private $installationManager;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|InstallTargetManager
     */
    private $targetManager;

    /**
     * @var AssetCommandHandler
     */
    private $handler;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$listCommand = self::$application->getCommand('asset')->getSubCommand('list');
        self::$mapCommand = self::$application->getCommand('asset')->getSubCommand('map');
        self::$removeCommand = self::$application->getCommand('asset')->getSubCommand('remove');
        self::$installCommand = self::$application->getCommand('asset')->getSubCommand('install');
    }

    protected function setUp()
    {
        parent::setUp();

        $this->assetManager = $this->getMock('Puli\AssetPlugin\Api\Asset\AssetManager');
        $this->installationManager = $this->getMock('Puli\AssetPlugin\Api\Installation\InstallationManager');
        $this->targetManager = $this->getMock('Puli\AssetPlugin\Api\Target\InstallTargetManager');
        $this->handler = new AssetCommandHandler($this->assetManager, $this->installationManager, $this->targetManager);
    }

    public function testListMappings()
    {
        $localTarget = new InstallTarget('local', 'symlink', 'web', '/%s');
        $remoteTarget = new InstallTarget('remote', 'rsync', 'ssh://example.com', 'http://example.com/%s');

        $this->targetManager->expects($this->any())
            ->method('hasTarget')
            ->willReturnMap(array(
                array('local', true),
                array('remote', true),
                array(InstallTarget::DEFAULT_TARGET, true),
            ));

        $this->targetManager->expects($this->any())
            ->method('getTarget')
            ->willReturnMap(array(
                array('local', $localTarget),
                array('remote', $remoteTarget),
                array(InstallTarget::DEFAULT_TARGET, $remoteTarget),
            ));

        $this->assetManager->expects($this->once())
            ->method('getAssetMappings')
            ->willReturn(array(
                new AssetMapping('/app/public', 'local', '/', Uuid::fromString(self::UUID1)),
                new AssetMapping('/acme/blog/public', 'remote', '/blog', Uuid::fromString(self::UUID2)),
                new AssetMapping('/acme/profiler/public', 'local', '/profiler', Uuid::fromString(self::UUID3)),
                new AssetMapping('/acme/admin/public', InstallTarget::DEFAULT_TARGET, '/admin', Uuid::fromString(self::UUID4)),
            ));

        $args = self::$listCommand->parseArgs(new StringArgs(''));

        $expected = <<<EOF
The following web assets are currently enabled:

    Target local
    Location:   web
    Installer:  symlink
    URL Format: /%s

        e81b32 /app/public           /
        49cfdf /acme/profiler/public /profiler

    Target remote
    Location:   ssh://example.com
    Installer:  rsync
    URL Format: http://example.com/%s

        33dbec /acme/blog/public /blog

    Target default (alias of: remote)
    Location:   ssh://example.com
    Installer:  rsync
    URL Format: http://example.com/%s

        8c64be /acme/admin/public /admin

Use "puli asset install" to install the assets in their targets.

EOF;

        $this->assertSame(0, $this->handler->handleList($args, $this->io));
        $this->assertSame($expected, $this->io->fetchOutput());
        $this->assertEmpty($this->io->fetchErrors());
    }

    public function testListMappingsOfNonExistingTarget()
    {
        $remoteTarget = new InstallTarget('remote', 'rsync', 'ssh://example.com', 'http://example.com/%s');

        $this->targetManager->expects($this->any())
            ->method('hasTarget')
            ->willReturnMap(array(
                array('local', false),
                array('remote', true),
                array(InstallTarget::DEFAULT_TARGET, true),
            ));

        $this->targetManager->expects($this->any())
            ->method('getTarget')
            ->willReturnMap(array(
                array('remote', $remoteTarget),
                array(InstallTarget::DEFAULT_TARGET, $remoteTarget),
            ));

        $this->assetManager->expects($this->once())
            ->method('getAssetMappings')
            ->willReturn(array(
                new AssetMapping('/app/public', 'local', '/', Uuid::fromString(self::UUID1)),
                new AssetMapping('/acme/blog/public', 'remote', '/blog', Uuid::fromString(self::UUID2)),
                new AssetMapping('/acme/profiler/public', 'local', '/profiler', Uuid::fromString(self::UUID3)),
                new AssetMapping('/acme/admin/public', InstallTarget::DEFAULT_TARGET, '/admin', Uuid::fromString(self::UUID4)),
            ));

        $args = self::$listCommand->parseArgs(new StringArgs(''));

        $expected = <<<EOF
The following web assets are currently enabled:

    Target remote
    Location:   ssh://example.com
    Installer:  rsync
    URL Format: http://example.com/%s

        33dbec /acme/blog/public /blog

    Target default (alias of: remote)
    Location:   ssh://example.com
    Installer:  rsync
    URL Format: http://example.com/%s

        8c64be /acme/admin/public /admin

Use "puli asset install" to install the assets in their targets.

The following web assets are disabled since their target does not exist.

    Target local

        e81b32 /app/public           /
        49cfdf /acme/profiler/public /profiler

Use "puli target add <target> <location>" to add a target.

EOF;

        $this->assertSame(0, $this->handler->handleList($args, $this->io));
        $this->assertSame($expected, $this->io->fetchOutput());
        $this->assertEmpty($this->io->fetchErrors());
    }

    public function testListMappingsOfNonExistingDefaultTarget()
    {
        $this->targetManager->expects($this->any())
            ->method('hasTarget')
            ->willReturnMap(array(
                array('local', false),
                array('remote', false),
                array(InstallTarget::DEFAULT_TARGET, false),
            ));

        $this->targetManager->expects($this->never())
            ->method('getTarget');

        $this->assetManager->expects($this->once())
            ->method('getAssetMappings')
            ->willReturn(array(
                new AssetMapping('/app/public', 'local', '/', Uuid::fromString(self::UUID1)),
                new AssetMapping('/acme/blog/public', 'remote', '/blog', Uuid::fromString(self::UUID2)),
                new AssetMapping('/acme/profiler/public', 'local', '/profiler', Uuid::fromString(self::UUID3)),
                new AssetMapping('/acme/admin/public', InstallTarget::DEFAULT_TARGET, '/admin', Uuid::fromString(self::UUID4)),
            ));

        $args = self::$listCommand->parseArgs(new StringArgs(''));

        $expected = <<<EOF
The following web assets are disabled since their target does not exist.

    Target local

        e81b32 /app/public           /
        49cfdf /acme/profiler/public /profiler

    Target remote

        33dbec /acme/blog/public /blog

    Target default

        8c64be /acme/admin/public /admin

Use "puli target add <target> <location>" to add a target.

EOF;

        $this->assertSame(0, $this->handler->handleList($args, $this->io));
        $this->assertSame($expected, $this->io->fetchOutput());
        $this->assertEmpty($this->io->fetchErrors());
    }

    public function testListEmpty()
    {
        $this->assetManager->expects($this->once())
            ->method('getAssetMappings')
            ->willReturn(array());

        $args = self::$listCommand->parseArgs(new StringArgs(''));

        $expected = <<<EOF
No assets are mapped. Use "puli asset map <path> <web-path>" to map assets.

EOF;

        $this->assertSame(0, $this->handler->handleList($args, $this->io));
        $this->assertSame($expected, $this->io->fetchOutput());
        $this->assertEmpty($this->io->fetchErrors());
    }

    public function testMap()
    {
        $this->assetManager->expects($this->once())
            ->method('addAssetMapping')
            ->willReturnCallback(function (AssetMapping $mapping) {
                PHPUnit_Framework_Assert::assertSame('/app/public', $mapping->getGlob());
                PHPUnit_Framework_Assert::assertSame('/', $mapping->getWebPath());
                PHPUnit_Framework_Assert::assertSame(InstallTarget::DEFAULT_TARGET, $mapping->getTargetName());
            });

        $args = self::$mapCommand->parseArgs(new StringArgs('/app/public /'));

        $this->assertSame(0, $this->handler->handleMap($args));
    }

    public function testMapWithTarget()
    {
        $this->assetManager->expects($this->once())
            ->method('addAssetMapping')
            ->willReturnCallback(function (AssetMapping $mapping) {
                PHPUnit_Framework_Assert::assertSame('/app/public', $mapping->getGlob());
                PHPUnit_Framework_Assert::assertSame('/', $mapping->getWebPath());
                PHPUnit_Framework_Assert::assertSame('remote', $mapping->getTargetName());
            });

        $args = self::$mapCommand->parseArgs(new StringArgs('/app/public / --target remote'));

        $this->assertSame(0, $this->handler->handleMap($args));
    }

    public function testMapForce()
    {
        $this->assetManager->expects($this->once())
            ->method('addAssetMapping')
            ->willReturnCallback(function (AssetMapping $mapping, $flags) {
                PHPUnit_Framework_Assert::assertSame('/app/public', $mapping->getGlob());
                PHPUnit_Framework_Assert::assertSame('/', $mapping->getWebPath());
                PHPUnit_Framework_Assert::assertSame(InstallTarget::DEFAULT_TARGET, $mapping->getTargetName());
                PHPUnit_Framework_Assert::assertSame(AssetManager::IGNORE_TARGET_NOT_FOUND, $flags);
            });

        $args = self::$mapCommand->parseArgs(new StringArgs('--force /app/public /'));

        $this->assertSame(0, $this->handler->handleMap($args));
    }

    public function testMapWithRelativeRepositoryPath()
    {
        $this->assetManager->expects($this->once())
            ->method('addAssetMapping')
            ->willReturnCallback(function (AssetMapping $mapping) {
                PHPUnit_Framework_Assert::assertSame('/app/public', $mapping->getGlob());
                PHPUnit_Framework_Assert::assertSame('/', $mapping->getWebPath());
                PHPUnit_Framework_Assert::assertSame(InstallTarget::DEFAULT_TARGET, $mapping->getTargetName());
            });

        $args = self::$mapCommand->parseArgs(new StringArgs('app/public /'));

        $this->assertSame(0, $this->handler->handleMap($args));
    }

    public function testMapWithRelativeWebPath()
    {
        $this->assetManager->expects($this->once())
            ->method('addAssetMapping')
            ->willReturnCallback(function (AssetMapping $mapping) {
                PHPUnit_Framework_Assert::assertSame('/app/public', $mapping->getGlob());
                PHPUnit_Framework_Assert::assertSame('/path', $mapping->getWebPath());
                PHPUnit_Framework_Assert::assertSame(InstallTarget::DEFAULT_TARGET, $mapping->getTargetName());
            });

        $args = self::$mapCommand->parseArgs(new StringArgs('/app/public path'));

        $this->assertSame(0, $this->handler->handleMap($args));
    }

    public function testRemoveMapping()
    {
        $this->assetManager->expects($this->at(0))
            ->method('findAssetMappings')
            ->with(Expr::startsWith(AssetMapping::UUID, 'abcd'))
            ->willReturn(array(
                $mapping1 = new AssetMapping('/app/public', 'local', '/'),
                $mapping2 = new AssetMapping('/acme/blog/public', 'remote', '/blog'),
            ));

        $this->assetManager->expects($this->at(1))
            ->method('removeAssetMapping')
            ->with($mapping1->getUuid());

        $this->assetManager->expects($this->at(2))
            ->method('removeAssetMapping')
            ->with($mapping2->getUuid());

        $args = self::$removeCommand->parseArgs(new StringArgs('abcd'));

        $this->assertSame(0, $this->handler->handleRemove($args));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testRemoveMappingFailsIfNotFound()
    {
        $this->assetManager->expects($this->once())
            ->method('findAssetMappings')
            ->with(Expr::startsWith(AssetMapping::UUID, 'abcd'))
            ->willReturn(array());

        $this->assetManager->expects($this->never())
            ->method('removeAssetMapping');

        $args = self::$removeCommand->parseArgs(new StringArgs('abcd'));

        $this->handler->handleRemove($args);
    }

    public function testInstall()
    {
        $mapping1 = new AssetMapping('/app/public', 'local', '/');
        $mapping2 = new AssetMapping('/acme/blog/public/{css,js}', 'remote', '/blog');

        $symlinkInstaller = $this->getMock('Puli\AssetPlugin\Api\Installer\ResourceInstaller');
        $symlinkInstallerDescriptor = new InstallerDescriptor('symlink', get_class($symlinkInstaller));
        $rsyncInstaller = $this->getMock('Puli\AssetPlugin\Api\Installer\ResourceInstaller');
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

        $this->assetManager->expects($this->once())
            ->method('getAssetMappings')
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
        $mapping1 = new AssetMapping('/app/public', 'local', '/');
        $mapping2 = new AssetMapping('/acme/blog/public/{css,js}', 'local', '/blog');

        $symlinkInstaller = $this->getMock('Puli\AssetPlugin\Api\Installer\ResourceInstaller');
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

        $this->assetManager->expects($this->once())
            ->method('findAssetMappings')
            ->with(Expr::same(AssetMapping::TARGET_NAME, 'local'))
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
        $mapping1 = new AssetMapping('/app/public', 'local', '/');
        $mapping2 = new AssetMapping('/acme/blog/public/{css,js}', 'local', '/blog');

        $symlinkInstaller = $this->getMock('Puli\AssetPlugin\Api\Installer\ResourceInstaller');
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

        $this->assetManager->expects($this->once())
            ->method('getAssetMappings')
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
        $this->assetManager->expects($this->once())
            ->method('getAssetMappings')
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
