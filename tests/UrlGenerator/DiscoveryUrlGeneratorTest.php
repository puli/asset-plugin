<?php

/*
 * This file is part of the puli/asset-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\AssetPlugin\Tests\UrlGenerator;

use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use Puli\AssetPlugin\Api\AssetPlugin;
use Puli\AssetPlugin\Api\Target\InstallTarget;
use Puli\AssetPlugin\Api\Target\InstallTargetCollection;
use Puli\AssetPlugin\UrlGenerator\DiscoveryUrlGenerator;
use Puli\Discovery\Api\Binding\BindingParameter;
use Puli\Discovery\Api\Binding\BindingType;
use Puli\Discovery\Api\ResourceDiscovery;
use Puli\Discovery\Binding\EagerBinding;
use Puli\Manager\Api\Package\Package;
use Puli\Manager\Api\Package\PackageFile;
use Puli\Repository\Api\ResourceCollection;
use Puli\Repository\Resource\Collection\ArrayResourceCollection;
use Puli\Repository\Resource\GenericResource;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class DiscoveryUrlGeneratorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PHPUnit_Framework_MockObject_MockObject|ResourceDiscovery
     */
    private $discovery;

    /**
     * @var InstallTargetCollection
     */
    private $targets;

    /**
     * @var DiscoveryUrlGenerator
     */
    private $generator;

    /**
     * @var Package
     */
    private $package;

    /**
     * @var BindingType
     */
    private $bindingType;

    /**
     * @var ResourceCollection
     */
    private $resources;

    protected function setUp()
    {
        $this->discovery = $this->getMock('Puli\Discovery\Api\ResourceDiscovery');
        $this->targets = new InstallTargetCollection();
        $this->generator = new DiscoveryUrlGenerator($this->discovery, $this->targets);
        $this->package = new Package(new PackageFile('vendor/package'), '/path');
        $this->bindingType = new BindingType(AssetPlugin::BINDING_TYPE, array(
            new BindingParameter(AssetPlugin::TARGET_PARAMETER),
            new BindingParameter(AssetPlugin::PATH_PARAMETER),
        ));
        $this->resources = new ArrayResourceCollection(array(new GenericResource('/path')));
    }

    public function testGenerateUrl()
    {
        $this->targets->add(new InstallTarget('local', 'symlink', 'public_html'));

        $binding = new EagerBinding(
            '/path/css{,/**/*}',
            $this->resources,
            $this->bindingType,
            array(
                AssetPlugin::TARGET_PARAMETER => 'local',
                AssetPlugin::PATH_PARAMETER => 'css',
            )
        );

        $this->discovery->expects($this->once())
            ->method('findByPath')
            ->with('/path/css/style.css', AssetPlugin::BINDING_TYPE)
            ->willReturn(array($binding));

        $this->assertSame('/css/style.css', $this->generator->generateUrl('/path/css/style.css'));
    }

    public function testAcceptWebPathWithLeadingSlash()
    {
        $this->targets->add(new InstallTarget('local', 'symlink', 'public_html'));

        $binding = new EagerBinding(
            '/path/css{,/**/*}',
            $this->resources,
            $this->bindingType,
            array(
                AssetPlugin::TARGET_PARAMETER => 'local',
                AssetPlugin::PATH_PARAMETER => '/css',
            )
        );

        $this->discovery->expects($this->once())
            ->method('findByPath')
            ->with('/path/css/style.css', AssetPlugin::BINDING_TYPE)
            ->willReturn(array($binding));

        $this->assertSame('/css/style.css', $this->generator->generateUrl('/path/css/style.css'));
    }

    public function testAcceptWebPathWithTrailingSlash()
    {
        $this->targets->add(new InstallTarget('local', 'symlink', 'public_html'));

        $binding = new EagerBinding(
            '/path/css{,/**/*}',
            $this->resources,
            $this->bindingType,
            array(
                AssetPlugin::TARGET_PARAMETER => 'local',
                AssetPlugin::PATH_PARAMETER => 'css/',
            )
        );

        $this->discovery->expects($this->once())
            ->method('findByPath')
            ->with('/path/css/style.css', AssetPlugin::BINDING_TYPE)
            ->willReturn(array($binding));

        $this->assertSame('/css/style.css', $this->generator->generateUrl('/path/css/style.css'));
    }

    public function testOnlyReplacePrefix()
    {
        $this->targets->add(new InstallTarget('local', 'symlink', 'public_html'));

        $binding = new EagerBinding(
            '/path{,/**/*}',
            $this->resources,
            $this->bindingType,
            array(
                AssetPlugin::TARGET_PARAMETER => 'local',
                AssetPlugin::PATH_PARAMETER => '/css',
            )
        );

        $this->discovery->expects($this->once())
            ->method('findByPath')
            ->with('/path/path/style.css', AssetPlugin::BINDING_TYPE)
            ->willReturn(array($binding));

        $this->assertSame('/css/path/style.css', $this->generator->generateUrl('/path/path/style.css'));
    }

    /**
     * @expectedException \Puli\AssetPlugin\Api\UrlGenerator\CannotGenerateUrlException
     * @expectedExceptionMessage /path/path/style.css
     */
    public function testFailIfResourceNotMapped()
    {
        $this->discovery->expects($this->once())
            ->method('findByPath')
            ->with('/path/path/style.css', AssetPlugin::BINDING_TYPE)
            ->willReturn(array());

        $this->generator->generateUrl('/path/path/style.css');
    }

    /**
     * @expectedException \Puli\AssetPlugin\Api\UrlGenerator\CannotGenerateUrlException
     * @expectedExceptionMessage foobar
     */
    public function testFailIfTargetNotFound()
    {
        $binding = new EagerBinding(
            '/path{,/**/*}',
            $this->resources,
            $this->bindingType,
            array(
                AssetPlugin::TARGET_PARAMETER => 'foobar',
                AssetPlugin::PATH_PARAMETER => '/css',
            )
        );

        $this->discovery->expects($this->once())
            ->method('findByPath')
            ->with('/path/path/style.css', AssetPlugin::BINDING_TYPE)
            ->willReturn(array($binding));

        $this->generator->generateUrl('/path/path/style.css');
    }
}
