<?php

/*
 * This file is part of the puli/asset-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\AssetPlugin\Tests\Api\Asset;

use PHPUnit_Framework_TestCase;
use Puli\AssetPlugin\Api\Asset\AssetMapping;
use Rhumsaa\Uuid\Uuid;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class AssetMappingTest extends PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $mapping = new AssetMapping('/blog/public', 'local', '/blog');

        $this->assertSame('/blog/public', $mapping->getGlob());
        $this->assertSame('local', $mapping->getTargetName());
        $this->assertSame('/blog', $mapping->getWebPath());
        $this->assertInstanceOf('Rhumsaa\Uuid\Uuid', $mapping->getUuid());
    }

    function testCreateWithUuid()
    {
        $uuid = Uuid::uuid4();
        $mapping = new AssetMapping('/blog/public', 'local', '/blog', $uuid);

        $this->assertSame('/blog/public', $mapping->getGlob());
        $this->assertSame('local', $mapping->getTargetName());
        $this->assertSame('/blog', $mapping->getWebPath());
        $this->assertSame($uuid, $mapping->getUuid());
    }

    public function testCreateNormalizesWebPath()
    {
        $mapping = new AssetMapping('/blog/public', 'local', 'blog/');

        $this->assertSame('/blog', $mapping->getWebPath());
    }

    public function testCreateWithEmptyWebPath()
    {
        $mapping = new AssetMapping('/blog/public', 'local', '');

        $this->assertSame('/', $mapping->getWebPath());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfRepositoryPathNull()
    {
        new AssetMapping(null, 'local', 'blog');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfRepositoryPathEmpty()
    {
        new AssetMapping('', 'local', 'blog');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfRepositoryPathNoString()
    {
        new AssetMapping(1234, 'local', 'blog');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfTargetNameNull()
    {
        new AssetMapping('/blog/public', null, 'blog');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfTargetNameEmpty()
    {
        new AssetMapping('/blog/public', '', 'blog');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfTargetNameNoString()
    {
        new AssetMapping('/blog/public', 1234, 'blog');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfWebPathNull()
    {
        new AssetMapping('/blog/public', 'local', null);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfWebPathNoString()
    {
        new AssetMapping('/blog/public', 'local', 1234);
    }
}
