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
use Puli\AssetPlugin\Api\Target\InstallTarget;
use Puli\AssetPlugin\Api\Target\InstallTargetCollection;
use Puli\AssetPlugin\Api\Target\InstallTargetManager;
use Puli\AssetPlugin\Console\TargetCommandHandler;
use Webmozart\Console\Api\Command\Command;
use Webmozart\Console\Args\StringArgs;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class TargetCommandHandlerTest extends AbstractCommandHandlerTest
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
    private static $updateCommand;

    /**
     * @var Command
     */
    private static $removeCommand;

    /**
     * @var Command
     */
    private static $setDefaultCommand;

    /**
     * @var Command
     */
    private static $getDefaultCommand;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|InstallTargetManager
     */
    private $targetManager;

    /**
     * @var TargetCommandHandler
     */
    private $handler;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$listCommand = self::$application->getCommand('target')->getSubCommand('list');
        self::$addCommand = self::$application->getCommand('target')->getSubCommand('add');
        self::$updateCommand = self::$application->getCommand('target')->getSubCommand('update');
        self::$removeCommand = self::$application->getCommand('target')->getSubCommand('remove');
        self::$setDefaultCommand = self::$application->getCommand('target')->getSubCommand('set-default');
        self::$getDefaultCommand = self::$application->getCommand('target')->getSubCommand('get-default');
    }

    protected function setUp()
    {
        parent::setUp();

        $this->targetManager = $this->getMock('Puli\AssetPlugin\Api\Target\InstallTargetManager');
        $this->handler = new TargetCommandHandler($this->targetManager);
    }

    public function testListTargets()
    {
        $targets = new InstallTargetCollection(array(
            new InstallTarget('local', 'symlink', 'public_html', '/%s'),
            new InstallTarget('remote', 'rsync', 'ssh://example.com', 'http://example.com/%s', array(
                'user' => 'webmozart',
                'password' => 'password',
            )),
        ));

        $targets->setDefaultTarget('remote');

        $this->targetManager->expects($this->any())
            ->method('getTargets')
            ->willReturn($targets);

        $args = self::$listCommand->parseArgs(new StringArgs(''));

        $expected = <<<EOF
  local  symlink public_html         /%s
* remote rsync   ssh://example.com   http://example.com/%s
                 user="webmozart"
                 password="password"

EOF;

        $this->assertSame(0, $this->handler->handleList($args, $this->io));
        $this->assertSame($expected, $this->io->fetchOutput());
        $this->assertEmpty($this->io->fetchErrors());
    }

    public function testListEmpty()
    {
        $targets = new InstallTargetCollection(array());

        $this->targetManager->expects($this->any())
            ->method('getTargets')
            ->willReturn($targets);

        $args = self::$listCommand->parseArgs(new StringArgs(''));

        $expected = <<<EOF
No install targets. Use "puli target add <name> <directory>" to add a target.

EOF;

        $this->assertSame(0, $this->handler->handleList($args, $this->io));
        $this->assertSame($expected, $this->io->fetchOutput());
        $this->assertEmpty($this->io->fetchErrors());
    }

    public function testAddTarget()
    {
        $target = new InstallTarget('local', 'symlink', 'public_html');

        $this->targetManager->expects($this->once())
            ->method('addTarget')
            ->with($target);

        $args = self::$addCommand->parseArgs(new StringArgs('local public_html'));

        $this->assertSame(0, $this->handler->handleAdd($args));
    }

    public function testAddTargetWithInstaller()
    {
        $target = new InstallTarget('local', 'copy', 'public_html');

        $this->targetManager->expects($this->once())
            ->method('addTarget')
            ->with($target);

        $args = self::$addCommand->parseArgs(new StringArgs('local public_html --installer copy'));

        $this->assertSame(0, $this->handler->handleAdd($args));
    }

    public function testAddTargetWithUrlFormat()
    {
        $target = new InstallTarget('local', 'symlink', 'public_html', '/blog/%s');

        $this->targetManager->expects($this->once())
            ->method('addTarget')
            ->with($target);

        $args = self::$addCommand->parseArgs(new StringArgs('local public_html --url-format /blog/%s'));

        $this->assertSame(0, $this->handler->handleAdd($args));
    }

    public function testAddTargetWithParameters()
    {
        $target = new InstallTarget('local', 'symlink', 'public_html', InstallTarget::DEFAULT_URL_FORMAT, array(
            'param1' => 'value1',
            'param2' => 'value2',
        ));

        $this->targetManager->expects($this->once())
            ->method('addTarget')
            ->with($target);

        $args = self::$addCommand->parseArgs(new StringArgs('local public_html --param param1=value1 --param param2=value2'));

        $this->assertSame(0, $this->handler->handleAdd($args));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testFailIfInvalidParameter()
    {
        $this->targetManager->expects($this->never())
            ->method('addTarget');

        $args = self::$addCommand->parseArgs(new StringArgs('local public_html --param param1'));

        $this->handler->handleAdd($args);
    }

    public function testUpdateTarget()
    {
        $target = new InstallTarget('local', 'symlink', 'public_html', '/%s', array(
            'param1' => 'old',
            'param2' => 'value2',
        ));

        $this->targetManager->expects($this->once())
            ->method('hasTarget')
            ->with('local')
            ->willReturn(true);

        $this->targetManager->expects($this->once())
            ->method('getTarget')
            ->with('local')
            ->willReturn($target);

        $this->targetManager->expects($this->once())
            ->method('addTarget')
            ->with(new InstallTarget('local', 'copy', 'web', '/dir/%s', array(
                'param1' => 'new',
                'param2' => 'value2',
            )));

        $args = self::$updateCommand->parseArgs(new StringArgs('local --installer copy --location web --url-format /dir/%s --param param1=new'));

        $this->assertSame(0, $this->handler->handleUpdate($args));
    }

    public function testUpdateTargetWithRemovedParameters()
    {
        $target = new InstallTarget('local', 'symlink', 'public_html', '/%s', array(
            'param1' => 'value1',
            'param2' => 'value2',
        ));

        $this->targetManager->expects($this->once())
            ->method('hasTarget')
            ->with('local')
            ->willReturn(true);

        $this->targetManager->expects($this->once())
            ->method('getTarget')
            ->with('local')
            ->willReturn($target);

        $this->targetManager->expects($this->once())
            ->method('addTarget')
            ->with(new InstallTarget('local', 'symlink', 'public_html', '/%s', array(
                'param2' => 'value2',
            )));

        $args = self::$updateCommand->parseArgs(new StringArgs('local --unset-param param1'));

        $this->assertSame(0, $this->handler->handleUpdate($args));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testUpdateTargetFailsIfNoChange()
    {
        $target = new InstallTarget('local', 'symlink', 'public_html');

        $this->targetManager->expects($this->once())
            ->method('hasTarget')
            ->with('local')
            ->willReturn(true);

        $this->targetManager->expects($this->once())
            ->method('getTarget')
            ->with('local')
            ->willReturn($target);

        $this->targetManager->expects($this->never())
            ->method('addTarget');

        $args = self::$updateCommand->parseArgs(new StringArgs('local'));

        $this->handler->handleUpdate($args);
    }

    public function testRemoveTarget()
    {
        $this->targetManager->expects($this->once())
            ->method('hasTarget')
            ->with('local')
            ->willReturn(true);

        $this->targetManager->expects($this->once())
            ->method('removeTarget')
            ->with('local');

        $args = self::$removeCommand->parseArgs(new StringArgs('local'));

        $this->assertSame(0, $this->handler->handleRemove($args));
    }

    /**
     * @expectedException \Puli\AssetPlugin\Api\Target\NoSuchTargetException
     */
    public function testRemoveTargetFailsIfNotFound()
    {
        $this->targetManager->expects($this->once())
            ->method('hasTarget')
            ->with('local')
            ->willReturn(false);

        $this->targetManager->expects($this->never())
            ->method('removeTarget');

        $args = self::$removeCommand->parseArgs(new StringArgs('local'));

        $this->handler->handleRemove($args);
    }

    public function testSetDefaultTarget()
    {
        $this->targetManager->expects($this->once())
            ->method('setDefaultTarget')
            ->with('local');

        $args = self::$setDefaultCommand->parseArgs(new StringArgs('local'));

        $this->assertSame(0, $this->handler->handleSetDefault($args));
    }

    public function testGetDefaultTarget()
    {
        $target = new InstallTarget('local', 'symlink', 'public_html');

        $this->targetManager->expects($this->once())
            ->method('getDefaultTarget')
            ->willReturn($target);

        $args = self::$getDefaultCommand->parseArgs(new StringArgs(''));

        $this->assertSame(0, $this->handler->handleGetDefault($args, $this->io));
        $this->assertSame("local\n", $this->io->fetchOutput());
        $this->assertEmpty($this->io->fetchErrors());
    }
}
