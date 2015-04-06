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

use PHPUnit_Framework_TestCase;
use Puli\AssetPlugin\Api\AssetPlugin;
use Puli\Cli\PuliApplicationConfig;
use Puli\Manager\Api\Puli;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\Console\Api\Application\Application;
use Webmozart\Console\Api\Formatter\Formatter;
use Webmozart\Console\ConsoleApplication;
use Webmozart\Console\Formatter\PlainFormatter;
use Webmozart\Console\IO\BufferedIO;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class AbstractCommandHandlerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected static $tempDir;

    /**
     * @var Application
     */
    protected static $application;

    /**
     * @var Formatter
     */
    protected static $formatter;

    /**
     * @var BufferedIO
     */
    protected $io;

    public static function setUpBeforeClass()
    {
        while (false === @mkdir(self::$tempDir = sys_get_temp_dir().'/puli-web-plugin/AbstractCommandHandlerTest'.rand(10000, 99999), 0777, true)) {}

        $puli = new Puli(self::$tempDir);
        $config = new PuliApplicationConfig($puli);
        $plugin = new AssetPlugin();
        $plugin->activate($puli);

        self::$application = new ConsoleApplication($config);
        self::$formatter = new PlainFormatter(self::$application->getConfig()->getStyleSet());
    }

    public static function tearDownAfterClass()
    {
        $filesystem = new Filesystem();
        $filesystem->remove(self::$tempDir);
    }

    protected function setUp()
    {
        $this->io = new BufferedIO('', self::$formatter);
    }
}
