<?php

/*
 * This file is part of the puli/web-resource-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\WebResourcePlugin\Tests\Api\Installation;

use PHPUnit_Framework_TestCase;
use Puli\Repository\Resource\Collection\ArrayResourceCollection;
use Puli\Repository\Resource\GenericResource;
use Puli\WebResourcePlugin\Api\Installation\InstallationParams;
use Puli\WebResourcePlugin\Api\Installer\InstallerDescriptor;
use Puli\WebResourcePlugin\Api\Installer\InstallerParameter;
use Puli\WebResourcePlugin\Api\Target\InstallTarget;
use Puli\WebResourcePlugin\Api\WebPath\WebPathMapping;
use Puli\WebResourcePlugin\Tests\Installation\Fixtures\TestInstaller;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class InstallationParamsTest extends PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $installer = new TestInstaller();
        $descriptor = new InstallerDescriptor('test', get_class($installer), null, array(
            new InstallerParameter('param1', InstallerParameter::OPTIONAL, 'default1'),
            new InstallerParameter('param2', InstallerParameter::OPTIONAL, 'default2'),
        ));
        $resources = new ArrayResourceCollection();
        $mapping = new WebPathMapping('/path/to/{css,js}', 'target', '/demo');
        $target = new InstallTarget('target', 'symlink', 'public_html', '/%s', array(
            'param2' => 'custom',
        ));

        $params = new InstallationParams(
            $installer,
            $descriptor,
            $resources,
            $mapping,
            $target,
            '/root'
        );

        $this->assertSame($installer, $params->getInstaller());
        $this->assertSame($descriptor, $params->getInstallerDescriptor());
        $this->assertSame($resources, $params->getResources());
        $this->assertSame('/root', $params->getRootDirectory());
        $this->assertSame('/path/to', $params->getBasePath());
        $this->assertSame('public_html', $params->getTargetLocation());
        $this->assertSame('/demo', $params->getWebPath());
        $this->assertSame(array(
            'param1' => 'default1',
            'param2' => 'custom',
        ), $params->getParameterValues());
    }

    public function testCreateWithStaticGlob()
    {
        $installer = new TestInstaller();
        $descriptor = new InstallerDescriptor('test', get_class($installer), null, array(
            new InstallerParameter('param1', InstallerParameter::OPTIONAL, 'default1'),
            new InstallerParameter('param2', InstallerParameter::OPTIONAL, 'default2'),
        ));
        $resources = new ArrayResourceCollection();
        $mapping = new WebPathMapping('/path/to/css', 'target', '/demo');
        $target = new InstallTarget('target', 'symlink', 'public_html', '/%s', array(
            'param2' => 'custom',
        ));

        $params = new InstallationParams(
            $installer,
            $descriptor,
            $resources,
            $mapping,
            $target,
            '/root'
        );

        $this->assertSame('/path/to/css', $params->getBasePath());
    }

    /**
     * @expectedException \Puli\WebResourcePlugin\Api\Installation\NotInstallableException
     * @expectedExceptionMessage foobar
     * @expectedExceptionCode 1
     */
    public function testFailIfMissingRequiredParameters()
    {
        $installer = new TestInstaller();
        $descriptor = new InstallerDescriptor('test', get_class($installer), null, array(
            new InstallerParameter('foobar', InstallerParameter::REQUIRED),
        ));
        $resources = new ArrayResourceCollection();
        $mapping = new WebPathMapping('/path/to/{css,js}', 'target', '/demo');
        $target = new InstallTarget('target', 'symlink', 'public_html');

        new InstallationParams(
            $installer,
            $descriptor,
            $resources,
            $mapping,
            $target,
            '/root'
        );
    }

    /**
     * @expectedException \Puli\WebResourcePlugin\Api\Installation\NotInstallableException
     * @expectedExceptionMessage foobar
     * @expectedExceptionCode 2
     */
    public function testFailIfUnknownParameter()
    {
        $installer = new TestInstaller();
        $descriptor = new InstallerDescriptor('test', get_class($installer));
        $resources = new ArrayResourceCollection();
        $mapping = new WebPathMapping('/path/to/{css,js}', 'target', '/demo');
        $target = new InstallTarget('target', 'symlink', 'public_html', '/%s', array(
            'foobar' => 'value',
        ));

        new InstallationParams(
            $installer,
            $descriptor,
            $resources,
            $mapping,
            $target,
            '/root'
        );
    }

    public function testGetWebPathForResource()
    {
        $installer = new TestInstaller();
        $descriptor = new InstallerDescriptor('test', get_class($installer));
        $resources = new ArrayResourceCollection(array(
            $resource1 = new GenericResource('/acme/blog/public/css'),
            $resource2 = new GenericResource('/acme/blog/public/js'),
        ));
        $mapping = new WebPathMapping('/acme/blog/public/{css,js}', 'target', '/blog');
        $target = new InstallTarget('target', 'symlink', 'public_html');

        $params = new InstallationParams(
            $installer,
            $descriptor,
            $resources,
            $mapping,
            $target,
            '/root'
        );

        $this->assertSame('/blog/css', $params->getWebPathForResource($resource1));
        $this->assertSame('/blog/js', $params->getWebPathForResource($resource2));
    }

    public function testGetWebPathForResourceSamePathAsBasePath()
    {
        $installer = new TestInstaller();
        $descriptor = new InstallerDescriptor('test', get_class($installer));
        $resources = new ArrayResourceCollection(array(
            $resource1 = new GenericResource('/acme/blog/public'),
        ));
        $mapping = new WebPathMapping('/acme/blog/public', 'target', '/blog');
        $target = new InstallTarget('target', 'symlink', 'public_html');

        $params = new InstallationParams(
            $installer,
            $descriptor,
            $resources,
            $mapping,
            $target,
            '/root'
        );

        $this->assertSame('/blog', $params->getWebPathForResource($resource1));
    }

    public function testGetWebPathForResourceInRoot()
    {
        $installer = new TestInstaller();
        $descriptor = new InstallerDescriptor('test', get_class($installer));
        $resources = new ArrayResourceCollection(array(
            $resource1 = new GenericResource('/acme/blog/public'),
        ));
        $mapping = new WebPathMapping('/acme/blog/public', 'target', '/');
        $target = new InstallTarget('target', 'symlink', 'public_html');

        $params = new InstallationParams(
            $installer,
            $descriptor,
            $resources,
            $mapping,
            $target,
            '/root'
        );

        $this->assertSame('/', $params->getWebPathForResource($resource1));
    }
}
