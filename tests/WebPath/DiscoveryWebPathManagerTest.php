<?php

/*
 * This file is part of the puli/web-resource-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\WebResourcePlugin\Tests\WebPath;

use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use Puli\RepositoryManager\Api\Discovery\BindingDescriptor;
use Puli\RepositoryManager\Api\Discovery\BindingState;
use Puli\RepositoryManager\Api\Discovery\BindingTypeDescriptor;
use Puli\RepositoryManager\Api\Discovery\DiscoveryManager;
use Puli\RepositoryManager\Api\Package\Package;
use Puli\RepositoryManager\Api\Package\PackageFile;
use Puli\RepositoryManager\Api\Package\RootPackage;
use Puli\RepositoryManager\Api\Package\RootPackageFile;
use Puli\WebResourcePlugin\Api\Target\InstallTarget;
use Puli\WebResourcePlugin\Api\Target\InstallTargetCollection;
use Puli\WebResourcePlugin\Api\WebPath\WebPathMapping;
use Puli\WebResourcePlugin\Api\WebResourcePlugin;
use Puli\WebResourcePlugin\WebPath\DiscoveryWebPathManager;
use Rhumsaa\Uuid\Uuid;
use Webmozart\Expression\Expr;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class DiscoveryWebPathManagerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PHPUnit_Framework_MockObject_MockObject|DiscoveryManager
     */
    private $discoveryManager;

    /**
     * @var InstallTarget
     */
    private $target1;

    /**
     * @var InstallTarget
     */
    private $target2;

    /**
     * @var InstallTargetCollection
     */
    private $targets;

    /**
     * @var Package
     */
    private $package;

    /**
     * @var RootPackage
     */
    private $rootPackage;

    /**
     * @var BindingTypeDescriptor
     */
    private $bindingType;

    /**
     * @var BindingDescriptor
     */
    private $binding1;

    /**
     * @var BindingDescriptor
     */
    private $binding2;

    /**
     * @var DiscoveryWebPathManager
     */
    private $manager;

    protected function setUp()
    {
        $this->discoveryManager = $this->getMock('Puli\RepositoryManager\Api\Discovery\DiscoveryManager');
        $this->target1 = new InstallTarget('target1', 'symlink', 'public_html');
        $this->target2 = new InstallTarget('target2', 'rsync', 'ssh://server');
        $this->targets = new InstallTargetCollection(array($this->target1, $this->target2));
        $this->manager = new DiscoveryWebPathManager($this->discoveryManager, $this->targets);
        $this->package = new Package(new PackageFile('vendor/package'), '/path');
        $this->rootPackage = new RootPackage(new RootPackageFile('vendor/root'), '/path');
        $this->bindingType = new BindingTypeDescriptor(WebResourcePlugin::BINDING_TYPE);
        $this->binding1 = new BindingDescriptor(
            '/path{,/**}',
            WebResourcePlugin::BINDING_TYPE,
            array(
                WebResourcePlugin::TARGET_PARAMETER => 'target1',
                WebResourcePlugin::PATH_PARAMETER => 'css',
            )
        );
        $this->binding2 = new BindingDescriptor(
            '/other/path{,/**}',
            WebResourcePlugin::BINDING_TYPE,
            array(
                WebResourcePlugin::TARGET_PARAMETER => 'target2',
                WebResourcePlugin::PATH_PARAMETER => 'js',
            )
        );
    }

    public function testAddWebPathMapping()
    {
        $uuid = Uuid::uuid4();

        $expectedBinding = new BindingDescriptor(
            '/path{,/**}',
            WebResourcePlugin::BINDING_TYPE,
            array(
                WebResourcePlugin::TARGET_PARAMETER => 'target1',
                WebResourcePlugin::PATH_PARAMETER => 'css',
            ),
            'glob',
            $uuid
        );

        $this->discoveryManager->expects($this->once())
            ->method('addBinding')
            ->with($expectedBinding);

        $this->manager->addWebPathMapping(new WebPathMapping('/path', 'target1', 'css', $uuid));
    }

    /**
     * @expectedException \Puli\WebResourcePlugin\Api\Target\NoSuchTargetException
     * @expectedExceptionMessage foobar
     */
    public function testAddWebPathMappingFailsIfTargetNotFound()
    {
        $this->discoveryManager->expects($this->never())
            ->method('addBinding');

        $this->manager->addWebPathMapping(new WebPathMapping('/path', 'foobar', 'css'));
    }

    public function testRemoveRootWebPathMapping()
    {
        $uuid = $this->binding1->getUuid();

        $this->bindingType->load($this->rootPackage);
        $this->binding1->load($this->rootPackage, $this->bindingType);

        $this->discoveryManager->expects($this->at(0))
            ->method('findBindings')
            ->with($this->uuid($uuid))
            ->willReturn(array($this->binding1));

        $this->discoveryManager->expects($this->at(1))
            ->method('removeBinding')
            ->with($uuid);
        $this->discoveryManager->expects($this->never())
            ->method('disableBinding');

        $this->manager->removeWebPathMapping($uuid);
    }

    public function testDisablePackageWebPathMapping()
    {
        $uuid = $this->binding1->getUuid();

        $this->bindingType->load($this->package);
        $this->binding1->load($this->package, $this->bindingType);

        $this->discoveryManager->expects($this->at(0))
            ->method('findBindings')
            ->with($this->uuid($uuid))
            ->willReturn(array($this->binding1));

        $this->discoveryManager->expects($this->never())
            ->method('removeBinding');
        $this->discoveryManager->expects($this->at(1))
            ->method('disableBinding')
            ->with($uuid, 'vendor/package');

        $this->manager->removeWebPathMapping($uuid);
    }

    public function testRemoveIgnoresNonExistingWebPathMapping()
    {
        $uuid = Uuid::uuid4();

        $this->discoveryManager->expects($this->once())
            ->method('findBindings')
            ->with($this->uuid($uuid))
            ->willReturn(array());

        $this->discoveryManager->expects($this->never())
            ->method('removeBinding');
        $this->discoveryManager->expects($this->never())
            ->method('disableBinding');

        $this->manager->removeWebPathMapping($uuid);
    }

    public function testGetWebPathMapping()
    {
        $uuid = $this->binding1->getUuid();

        $this->discoveryManager->expects($this->at(0))
            ->method('findBindings')
            ->with($this->uuid($uuid))
            ->willReturn(array($this->binding1));

        $expected = new WebPathMapping('/path', 'target1', 'css', $uuid);

        $this->assertEquals($expected, $this->manager->getWebPathMapping($uuid));
    }

    /**
     * @expectedException \Puli\WebResourcePlugin\Api\WebPath\NoSuchWebPathMappingException
     */
    public function testGetWebPathMappingFailsIfNotFound()
    {
        $uuid = Uuid::uuid4();

        $this->discoveryManager->expects($this->at(0))
            ->method('findBindings')
            ->with($this->uuid($uuid))
            ->willReturn(array());

        $this->manager->getWebPathMapping($uuid);
    }

    public function testGetWebPathMappings()
    {
        $this->discoveryManager->expects($this->once())
            ->method('findBindings')
            ->with($this->all())
            ->willReturn(array($this->binding1, $this->binding2));

        $expected = array(
            new WebPathMapping('/path', 'target1', 'css', $this->binding1->getUuid()),
            new WebPathMapping('/other/path', 'target2', 'js', $this->binding2->getUuid()),
        );

        $this->assertEquals($expected, $this->manager->getWebPathMappings());
    }

    public function testGetNoWebPathMappings()
    {
        $this->discoveryManager->expects($this->once())
            ->method('findBindings')
            ->with($this->all())
            ->willReturn(array());

        $this->assertEquals(array(), $this->manager->getWebPathMappings());
    }

    public function testFindWebPathMappings()
    {
        $this->discoveryManager->expects($this->once())
            ->method('findBindings')
            ->with($this->webPath('/other/path'))
            ->willReturn(array($this->binding2));

        $expr = Expr::same(WebPathMapping::WEB_PATH, '/other/path');
        $expected = new WebPathMapping('/other/path', 'target2', 'js', $this->binding2->getUuid());

        $this->assertEquals(array($expected), $this->manager->findWebPathMappings($expr));
    }

    public function testFindNoWebPathMappings()
    {
        $this->discoveryManager->expects($this->once())
            ->method('findBindings')
            ->with($this->webPath('/foobar'))
            ->willReturn(array());

        $expr = Expr::same(WebPathMapping::WEB_PATH, '/foobar');

        $this->assertEquals(array(), $this->manager->findWebPathMappings($expr));
    }

    public function testHasWebPathMapping()
    {
        $uuid = Uuid::uuid4();

        $this->discoveryManager->expects($this->once())
            ->method('hasBindings')
            ->with($this->uuid($uuid))
            ->willReturn(true);

        $this->assertTrue($this->manager->hasWebPathMapping($uuid));
    }

    public function testNotHasWebPathMapping()
    {
        $uuid = Uuid::uuid4();

        $this->discoveryManager->expects($this->once())
            ->method('hasBindings')
            ->with($this->uuid($uuid))
            ->willReturn(false);

        $this->assertFalse($this->manager->hasWebPathMapping($uuid));
    }

    public function testHasWebPathMappings()
    {
        $this->discoveryManager->expects($this->once())
            ->method('hasBindings')
            ->with($this->all())
            ->willReturn(true);

        $this->assertTrue($this->manager->hasWebPathMappings());
    }

    public function testHasNoWebPathMappings()
    {
        $this->discoveryManager->expects($this->once())
            ->method('hasBindings')
            ->with($this->all())
            ->willReturn(false);

        $this->assertFalse($this->manager->hasWebPathMappings());
    }

    public function testHasWebPathMappingsWithExpression()
    {
        $this->discoveryManager->expects($this->once())
            ->method('hasBindings')
            ->with($this->webPath('/path'))
            ->willReturn(true);

        $expr = Expr::same(WebPathMapping::WEB_PATH, '/path');

        $this->assertTrue($this->manager->hasWebPathMappings($expr));
    }

    private function all()
    {
        return Expr::same(BindingDescriptor::STATE, BindingState::ENABLED)
            ->andSame(BindingDescriptor::TYPE_NAME, WebResourcePlugin::BINDING_TYPE)
            ->andEndsWith(BindingDescriptor::QUERY, '{,/**}');
    }

    private function uuid(Uuid $uuid)
    {
        return $this->all()->andSame(BindingDescriptor::UUID, $uuid->toString());
    }

    private function webPath($path)
    {
        return $this->all()->andKeySame(BindingDescriptor::PARAMETER_VALUES, WebResourcePlugin::PATH_PARAMETER, $path);
    }
}
