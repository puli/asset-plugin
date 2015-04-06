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

use PHPUnit_Framework_MockObject_MockObject;
use Puli\AssetPlugin\Api\Installer\InstallerDescriptor;
use Puli\AssetPlugin\Api\Installer\InstallerManager;
use Puli\AssetPlugin\Api\Installer\InstallerParameter;
use Puli\AssetPlugin\Console\InstallerCommandHandler;
use Webmozart\Console\Api\Command\Command;
use Webmozart\Console\Args\StringArgs;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class InstallerCommandHandlerTest extends AbstractCommandHandlerTest
{
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
     * @var PHPUnit_Framework_MockObject_MockObject|InstallerManager
     */
    private $installerManager;

    /**
     * @var InstallerCommandHandler
     */
    private $handler;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$listCommand = self::$application->getCommand('installer')->getSubCommand('list');
        self::$addCommand = self::$application->getCommand('installer')->getSubCommand('add');
        self::$removeCommand = self::$application->getCommand('installer')->getSubCommand('remove');
    }

    protected function setUp()
    {
        parent::setUp();

        $this->installerManager = $this->getMock('Puli\AssetPlugin\Api\Installer\InstallerManager');
        $this->handler = new InstallerCommandHandler($this->installerManager);
    }

    public function testListInstallers()
    {
        $this->initDefaultInstallers();

        $args = self::$listCommand->parseArgs(new StringArgs(''));

        $nbsp = "\xc2\xa0";
        $expected = <<<EOF
symlink SymlinkInstaller Symlink description
copy    CopyInstaller    The copy description is significantly longer than all
                         the other descriptions, although it doesn't bear any
                         more information.
rsync   RsyncInstaller   Just a short description (required,{$nbsp}optional=42)

EOF;

        $this->assertSame(0, $this->handler->handleList($args, $this->io));
        $this->assertSame($expected, $this->io->fetchOutput());
        $this->assertEmpty($this->io->fetchErrors());
    }

    public function testListLongInstallers()
    {
        $this->initDefaultInstallers();

        $args = self::$listCommand->parseArgs(new StringArgs('-l'));

        $nbsp = "\xc2\xa0";
        $expected = <<<EOF
symlink Puli\Installer\SymlinkInstaller Symlink description
copy    Puli\Installer\CopyInstaller    The copy description is significantly
                                        longer than all the other descriptions,
                                        although it doesn't bear any more
                                        information.
rsync   Puli\Installer\RsyncInstaller   Just a short description
                                        (required,{$nbsp}optional=42)

EOF;

        $this->assertSame(0, $this->handler->handleList($args, $this->io));
        $this->assertSame($expected, $this->io->fetchOutput());
        $this->assertEmpty($this->io->fetchErrors());
    }

    public function testAddInstaller()
    {
        $args = self::$addCommand->parseArgs(new StringArgs('symlink Puli\Installer\SymlinkInstaller'));

        $descriptor = new InstallerDescriptor('symlink', 'Puli\Installer\SymlinkInstaller');

        $this->installerManager->expects($this->once())
            ->method('addInstallerDescriptor')
            ->with($descriptor);

        $this->assertSame(0, $this->handler->handleAdd($args));
    }

    public function testAddInstallerWithDescription()
    {
        $args = self::$addCommand->parseArgs(new StringArgs('symlink Puli\Installer\SymlinkInstaller --description "The description"'));

        $descriptor = new InstallerDescriptor('symlink', 'Puli\Installer\SymlinkInstaller', 'The description');

        $this->installerManager->expects($this->once())
            ->method('addInstallerDescriptor')
            ->with($descriptor);

        $this->assertSame(0, $this->handler->handleAdd($args));
    }

    public function testAddInstallerWithParameters()
    {
        $args = self::$addCommand->parseArgs(new StringArgs('symlink Puli\Installer\SymlinkInstaller --description "The description" --param required --description "required description" --param optional=42 --description "optional description"'));

        $descriptor = new InstallerDescriptor('symlink', 'Puli\Installer\SymlinkInstaller', 'The description', array(
            new InstallerParameter('required', InstallerParameter::REQUIRED, null, 'required description'),
            new InstallerParameter('optional', InstallerParameter::OPTIONAL, 42, 'optional description'),
        ));

        $this->installerManager->expects($this->once())
            ->method('addInstallerDescriptor')
            ->with($descriptor);

        $this->assertSame(0, $this->handler->handleAdd($args));
    }

    public function testRemoveInstaller()
    {
        $args = self::$removeCommand->parseArgs(new StringArgs('symlink'));

        $this->installerManager->expects($this->once())
            ->method('hasInstallerDescriptor')
            ->with('symlink')
            ->willReturn(true);

        $this->installerManager->expects($this->once())
            ->method('removeInstallerDescriptor')
            ->with('symlink');

        $this->assertSame(0, $this->handler->handleRemove($args));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testRemoveInstallerFailsIfNotFound()
    {
        $args = self::$removeCommand->parseArgs(new StringArgs('symlink'));

        $this->installerManager->expects($this->once())
            ->method('hasInstallerDescriptor')
            ->with('symlink')
            ->willReturn(false);

        $this->installerManager->expects($this->never())
            ->method('removeInstallerDescriptor');

        $this->handler->handleRemove($args);
    }

    private function initDefaultInstallers()
    {
        $this->installerManager->expects($this->once())
            ->method('getInstallerDescriptors')
            ->willReturn(array(
                new InstallerDescriptor('symlink', 'Puli\Installer\SymlinkInstaller', 'Symlink description'),
                new InstallerDescriptor('copy', 'Puli\Installer\CopyInstaller', 'The copy description is significantly longer than all the other descriptions, although it doesn\'t bear any more information.'),
                new InstallerDescriptor('rsync', 'Puli\Installer\RsyncInstaller', 'Just a short description', array(
                    new InstallerParameter('required', InstallerParameter::REQUIRED, null, 'The description of "required"'),
                    new InstallerParameter('optional', InstallerParameter::OPTIONAL, 42, 'The description of "optional"'),
                )),
            ));
    }
}
