<?php

/*
 * This file is part of the puli/asset-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\AssetPlugin\Tests\Asset;

use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use Puli\AssetPlugin\Api\Asset\AssetManager;
use Puli\AssetPlugin\Api\Asset\AssetMapping;
use Puli\AssetPlugin\Api\AssetPlugin;
use Puli\AssetPlugin\Api\Target\InstallTarget;
use Puli\AssetPlugin\Api\Target\InstallTargetCollection;
use Puli\AssetPlugin\Asset\DiscoveryAssetManager;
use Puli\Manager\Api\Discovery\BindingDescriptor;
use Puli\Manager\Api\Discovery\BindingState;
use Puli\Manager\Api\Discovery\BindingTypeDescriptor;
use Puli\Manager\Api\Discovery\DiscoveryManager;
use Puli\Manager\Api\Package\Package;
use Puli\Manager\Api\Package\PackageFile;
use Puli\Manager\Api\Package\RootPackage;
use Puli\Manager\Api\Package\RootPackageFile;
use Rhumsaa\Uuid\Uuid;
use Webmozart\Expression\Expr;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class DiscoveryAssetManagerTest extends PHPUnit_Framework_TestCase
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
     * @var DiscoveryAssetManager
     */
    private $manager;

    protected function setUp()
    {
        $this->discoveryManager = $this->getMock('Puli\Manager\Api\Discovery\DiscoveryManager');
        $this->target1 = new InstallTarget('target1', 'symlink', 'public_html');
        $this->target2 = new InstallTarget('target2', 'rsync', 'ssh://server');
        $this->targets = new InstallTargetCollection(array($this->target1, $this->target2));
        $this->manager = new DiscoveryAssetManager($this->discoveryManager, $this->targets);
        $this->package = new Package(new PackageFile('vendor/package'), '/path');
        $this->rootPackage = new RootPackage(new RootPackageFile('vendor/root'), '/path');
        $this->bindingType = new BindingTypeDescriptor(AssetPlugin::BINDING_TYPE);
        $this->binding1 = new BindingDescriptor(
            '/path{,/**/*}',
            AssetPlugin::BINDING_TYPE,
            array(
                AssetPlugin::TARGET_PARAMETER => 'target1',
                AssetPlugin::PATH_PARAMETER => '/css',
            )
        );
        $this->binding2 = new BindingDescriptor(
            '/other/path{,/**/*}',
            AssetPlugin::BINDING_TYPE,
            array(
                AssetPlugin::TARGET_PARAMETER => 'target2',
                AssetPlugin::PATH_PARAMETER => '/js',
            )
        );
    }

    public function testAddAssetMapping()
    {
        $uuid = Uuid::uuid4();

        $expectedBinding = new BindingDescriptor(
            '/path{,/**/*}',
            AssetPlugin::BINDING_TYPE,
            array(
                AssetPlugin::TARGET_PARAMETER => 'target1',
                AssetPlugin::PATH_PARAMETER => '/css',
            ),
            'glob',
            $uuid
        );

        $this->discoveryManager->expects($this->once())
            ->method('addBinding')
            ->with($expectedBinding);

        $this->manager->addAssetMapping(new AssetMapping('/path', 'target1', '/css', $uuid));
    }

    /**
     * @expectedException \Puli\AssetPlugin\Api\Target\NoSuchTargetException
     * @expectedExceptionMessage foobar
     */
    public function testAddAssetMappingFailsIfTargetNotFound()
    {
        $this->discoveryManager->expects($this->never())
            ->method('addBinding');

        $this->manager->addAssetMapping(new AssetMapping('/path', 'foobar', '/css'));
    }

    public function testAddAssetMappingDoesNotFailIfTargetNotFoundAndNoTargetCheck()
    {
        $uuid = Uuid::uuid4();

        $expectedBinding = new BindingDescriptor(
            '/path{,/**/*}',
            AssetPlugin::BINDING_TYPE,
            array(
                AssetPlugin::TARGET_PARAMETER => 'foobar',
                AssetPlugin::PATH_PARAMETER => '/css',
            ),
            'glob',
            $uuid
        );

        $this->discoveryManager->expects($this->once())
            ->method('addBinding')
            ->with($expectedBinding);

        $this->manager->addAssetMapping(new AssetMapping('/path', 'foobar', '/css', $uuid), AssetManager::NO_TARGET_CHECK);
    }

    public function testRemoveRootAssetMapping()
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

        $this->manager->removeAssetMapping($uuid);
    }

    public function testDisablePackageAssetMapping()
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

        $this->manager->removeAssetMapping($uuid);
    }

    public function testRemoveIgnoresNonExistingAssetMapping()
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

        $this->manager->removeAssetMapping($uuid);
    }

    public function testGetAssetMapping()
    {
        $uuid = $this->binding1->getUuid();

        $this->discoveryManager->expects($this->at(0))
            ->method('findBindings')
            ->with($this->uuid($uuid))
            ->willReturn(array($this->binding1));

        $expected = new AssetMapping('/path', 'target1', '/css', $uuid);

        $this->assertEquals($expected, $this->manager->getAssetMapping($uuid));
    }

    /**
     * @expectedException \Puli\AssetPlugin\Api\Asset\NoSuchAssetMappingException
     */
    public function testGetAssetMappingFailsIfNotFound()
    {
        $uuid = Uuid::uuid4();

        $this->discoveryManager->expects($this->at(0))
            ->method('findBindings')
            ->with($this->uuid($uuid))
            ->willReturn(array());

        $this->manager->getAssetMapping($uuid);
    }

    public function testGetAssetMappings()
    {
        $this->discoveryManager->expects($this->once())
            ->method('findBindings')
            ->with($this->all())
            ->willReturn(array($this->binding1, $this->binding2));

        $expected = array(
            new AssetMapping('/path', 'target1', '/css', $this->binding1->getUuid()),
            new AssetMapping('/other/path', 'target2', '/js', $this->binding2->getUuid()),
        );

        $this->assertEquals($expected, $this->manager->getAssetMappings());
    }

    public function testGetNoAssetMappings()
    {
        $this->discoveryManager->expects($this->once())
            ->method('findBindings')
            ->with($this->all())
            ->willReturn(array());

        $this->assertEquals(array(), $this->manager->getAssetMappings());
    }

    public function testFindAssetMappings()
    {
        $this->discoveryManager->expects($this->once())
            ->method('findBindings')
            ->with($this->webPath('/other/path'))
            ->willReturn(array($this->binding2));

        $expr = Expr::same(AssetMapping::WEB_PATH, '/other/path');
        $expected = new AssetMapping('/other/path', 'target2', '/js', $this->binding2->getUuid());

        $this->assertEquals(array($expected), $this->manager->findAssetMappings($expr));
    }

    public function testFindNoAssetMappings()
    {
        $this->discoveryManager->expects($this->once())
            ->method('findBindings')
            ->with($this->webPath('/foobar'))
            ->willReturn(array());

        $expr = Expr::same(AssetMapping::WEB_PATH, '/foobar');

        $this->assertEquals(array(), $this->manager->findAssetMappings($expr));
    }

    public function testHasAssetMapping()
    {
        $uuid = Uuid::uuid4();

        $this->discoveryManager->expects($this->once())
            ->method('hasBindings')
            ->with($this->uuid($uuid))
            ->willReturn(true);

        $this->assertTrue($this->manager->hasAssetMapping($uuid));
    }

    public function testNotHasAssetMapping()
    {
        $uuid = Uuid::uuid4();

        $this->discoveryManager->expects($this->once())
            ->method('hasBindings')
            ->with($this->uuid($uuid))
            ->willReturn(false);

        $this->assertFalse($this->manager->hasAssetMapping($uuid));
    }

    public function testHasAssetMappings()
    {
        $this->discoveryManager->expects($this->once())
            ->method('hasBindings')
            ->with($this->all())
            ->willReturn(true);

        $this->assertTrue($this->manager->hasAssetMappings());
    }

    public function testHasNoAssetMappings()
    {
        $this->discoveryManager->expects($this->once())
            ->method('hasBindings')
            ->with($this->all())
            ->willReturn(false);

        $this->assertFalse($this->manager->hasAssetMappings());
    }

    public function testHasAssetMappingsWithExpression()
    {
        $this->discoveryManager->expects($this->once())
            ->method('hasBindings')
            ->with($this->webPath('/path'))
            ->willReturn(true);

        $expr = Expr::same(AssetMapping::WEB_PATH, '/path');

        $this->assertTrue($this->manager->hasAssetMappings($expr));
    }

    private function all()
    {
        return Expr::same(BindingDescriptor::STATE, BindingState::ENABLED)
            ->andSame(BindingDescriptor::TYPE_NAME, AssetPlugin::BINDING_TYPE)
            ->andEndsWith(BindingDescriptor::QUERY, '{,/**/*}');
    }

    private function uuid(Uuid $uuid)
    {
        return $this->all()->andSame(BindingDescriptor::UUID, $uuid->toString());
    }

    private function webPath($path)
    {
        return $this->all()->andKeySame(BindingDescriptor::PARAMETER_VALUES, AssetPlugin::PATH_PARAMETER, $path);
    }
}
