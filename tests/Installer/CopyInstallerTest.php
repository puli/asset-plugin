<?php

/*
 * This file is part of the puli/web-resource-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\WebResourcePlugin\Tests\Installer;

use PHPUnit_Framework_TestCase;
use Puli\Repository\Resource\Collection\ArrayResourceCollection;
use Puli\Repository\Resource\DirectoryResource;
use Puli\WebResourcePlugin\Api\Installation\InstallationParams;
use Puli\WebResourcePlugin\Api\Installer\InstallerDescriptor;
use Puli\WebResourcePlugin\Api\Target\InstallTarget;
use Puli\WebResourcePlugin\Api\WebPath\WebPathMapping;
use Puli\WebResourcePlugin\Installer\CopyInstaller;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class CopyInstallerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $tempDir;

    /**
     * @var CopyInstaller
     */
    private $installer;

    /**
     * @var InstallerDescriptor
     */
    private $installerDescriptor;

    protected function setUp()
    {
        while (false === @mkdir($this->tempDir = sys_get_temp_dir().'/puli-web-plugin/CopyInstallerTest'.rand(10000, 99999), 0777, true)) {}

        $this->installer = new CopyInstaller();
        $this->installerDescriptor = new InstallerDescriptor('copy', get_class($this->installer));
    }

    protected function tearDown()
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->tempDir);
    }

    public function testInstallResource()
    {
        $mapping = new WebPathMapping('/app/public', 'local', '/');
        $target = new InstallTarget('local', 'copy', 'public_html');

        $resource = new DirectoryResource(__DIR__.'/Fixtures', '/app/public');

        $params = new InstallationParams(
            $this->installer,
            $this->installerDescriptor,
            new ArrayResourceCollection(array($resource)),
            $mapping,
            $target,
            $this->tempDir
        );

        $this->installer->installResource($resource, $params);

        $this->assertFileExists($this->tempDir.'/public_html');
        $this->assertFileExists($this->tempDir.'/public_html/css');
        $this->assertFileExists($this->tempDir.'/public_html/css/style.css');
        $this->assertFileExists($this->tempDir.'/public_html/js');
        $this->assertFileExists($this->tempDir.'/public_html/js/script.js');

        $this->assertFalse(is_link($this->tempDir.'/public_html'));
        $this->assertFalse(is_link($this->tempDir.'/public_html/css'));
        $this->assertFalse(is_link($this->tempDir.'/public_html/css/style.css'));
        $this->assertFalse(is_link($this->tempDir.'/public_html/js'));
        $this->assertFalse(is_link($this->tempDir.'/public_html/js/script.js'));
    }

    public function testInstallResourceWithBasePath()
    {
        $mapping = new WebPathMapping('/app/public/{css,js}', 'local', '/');
        $target = new InstallTarget('local', 'symlink', 'public_html');

        $resource = new DirectoryResource(__DIR__.'/Fixtures/css', '/app/public/css');

        $params = new InstallationParams(
            $this->installer,
            $this->installerDescriptor,
            new ArrayResourceCollection(array($resource)),
            $mapping,
            $target,
            $this->tempDir
        );

        $this->installer->installResource($resource, $params);

        $this->assertFileExists($this->tempDir.'/public_html');
        $this->assertFileExists($this->tempDir.'/public_html/css');
        $this->assertFileExists($this->tempDir.'/public_html/css/style.css');
        $this->assertFileNotExists($this->tempDir.'/public_html/js');
        $this->assertFileNotExists($this->tempDir.'/public_html/js/script.js');

        $this->assertFalse(is_link($this->tempDir.'/public_html'));
        $this->assertFalse(is_link($this->tempDir.'/public_html/css'));
        $this->assertFalse(is_link($this->tempDir.'/public_html/css/style.css'));
    }
}
