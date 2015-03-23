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
use Puli\WebResourcePlugin\Api\Installer\InstallerParameter;
use Puli\WebResourcePlugin\Api\Target\InstallTarget;
use Puli\WebResourcePlugin\Api\WebPath\WebPathMapping;
use Puli\WebResourcePlugin\Installer\SymlinkInstaller;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class SymlinkInstallerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $tempBaseDir;

    /**
     * @var string
     */
    private $tempDir;

    /**
     * @var string
     */
    private $fixturesDir;

    /**
     * @var SymlinkInstaller
     */
    private $installer;

    /**
     * @var InstallerDescriptor
     */
    private $installerDescriptor;

    protected function setUp()
    {
        while (false === @mkdir($this->tempBaseDir = sys_get_temp_dir().'/puli-web-plugin/SymlinkInstallerTest'.rand(10000, 99999), 0777, true)) {}

        $this->tempDir = $this->tempBaseDir.'/workspace';
        $this->fixturesDir = $this->tempBaseDir.'/fixtures';

        mkdir($this->tempDir);
        mkdir($this->fixturesDir);

        // Mirror the fixtures so that we can test the relative paths
        $filesystem = new Filesystem();
        $filesystem->mirror(__DIR__.'/Fixtures', $this->fixturesDir);

        $this->installer = new SymlinkInstaller();
        $this->installerDescriptor = new InstallerDescriptor('symlink', get_class($this->installer), null, array(
            new InstallerParameter('relative', InstallerParameter::OPTIONAL, true),
        ));
    }

    protected function tearDown()
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->tempBaseDir);
    }

    public function testInstallResource()
    {
        $mapping = new WebPathMapping('/app/public', 'local', '/');
        $target = new InstallTarget('local', 'symlink', 'public_html');

        $resource = new DirectoryResource($this->fixturesDir, '/app/public');

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
        $this->assertTrue(is_link($this->tempDir.'/public_html/css'));
        $this->assertFalse(is_link($this->tempDir.'/public_html/css/style.css'));
        $this->assertTrue(is_link($this->tempDir.'/public_html/js'));
        $this->assertFalse(is_link($this->tempDir.'/public_html/js/script.js'));

        $this->assertSame('../../fixtures/css', readlink($this->tempDir.'/public_html/css'));
        $this->assertSame('../../fixtures/js', readlink($this->tempDir.'/public_html/js'));
    }

    public function testInstallResourceWithAbsolutePaths()
    {
        $mapping = new WebPathMapping('/app/public', 'local', '/');
        $target = new InstallTarget('local', 'symlink', 'public_html', '/%s', array(
            'relative' => false,
        ));

        $resource = new DirectoryResource($this->fixturesDir, '/app/public');

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
        $this->assertTrue(is_link($this->tempDir.'/public_html/css'));
        $this->assertFalse(is_link($this->tempDir.'/public_html/css/style.css'));
        $this->assertTrue(is_link($this->tempDir.'/public_html/js'));
        $this->assertFalse(is_link($this->tempDir.'/public_html/js/script.js'));

        $this->assertSame($this->fixturesDir.'/css', readlink($this->tempDir.'/public_html/css'));
        $this->assertSame($this->fixturesDir.'/js', readlink($this->tempDir.'/public_html/js'));
    }

    public function testInstallResourceWithBasePath()
    {
        $mapping = new WebPathMapping('/app/public/{css,js}', 'local', '/');
        $target = new InstallTarget('local', 'symlink', 'public_html');

        $resource = new DirectoryResource($this->fixturesDir.'/css', '/app/public/css');

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
        $this->assertTrue(is_link($this->tempDir.'/public_html/css'));
        $this->assertFalse(is_link($this->tempDir.'/public_html/css/style.css'));

        $this->assertSame('../../fixtures/css', readlink($this->tempDir.'/public_html/css'));
    }

    public function testInstallResourceTwiceToRoot()
    {
        $mapping = new WebPathMapping('/app/public', 'local', '/');
        $target = new InstallTarget('local', 'symlink', 'public_html');

        $resource = new DirectoryResource($this->fixturesDir, '/app/public');

        $params = new InstallationParams(
            $this->installer,
            $this->installerDescriptor,
            new ArrayResourceCollection(array($resource)),
            $mapping,
            $target,
            $this->tempDir
        );

        $this->installer->installResource($resource, $params);
        $this->installer->installResource($resource, $params);

        // The links are correct even after calling the method twice
        $this->assertTrue(is_link($this->tempDir.'/public_html/css'));
        $this->assertTrue(is_link($this->tempDir.'/public_html/js'));

        $this->assertSame('../../fixtures/css', readlink($this->tempDir.'/public_html/css'));
        $this->assertSame('../../fixtures/js', readlink($this->tempDir.'/public_html/js'));
    }

    public function testInstallResourceTwiceToSubPath()
    {
        $mapping = new WebPathMapping('/app/public', 'local', '/path');
        $target = new InstallTarget('local', 'symlink', 'public_html');

        $resource = new DirectoryResource($this->fixturesDir, '/app/public');

        $params = new InstallationParams(
            $this->installer,
            $this->installerDescriptor,
            new ArrayResourceCollection(array($resource)),
            $mapping,
            $target,
            $this->tempDir
        );

        $this->installer->installResource($resource, $params);
        $this->installer->installResource($resource, $params);

        // The links are correct even after calling the method twice
        $this->assertTrue(is_link($this->tempDir.'/public_html/path'));

        $this->assertSame('../../fixtures', readlink($this->tempDir.'/public_html/path'));
    }
}
