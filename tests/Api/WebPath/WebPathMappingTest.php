<?php

/*
 * This file is part of the puli/asset-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\AssetPlugin\Tests\Api\WebPath;

use PHPUnit_Framework_TestCase;
use Puli\AssetPlugin\Api\WebPath\WebPathMapping;
use Rhumsaa\Uuid\Uuid;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class WebPathMappingTest extends PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $mapping = new WebPathMapping('/blog/public', 'local', '/blog');

        $this->assertSame('/blog/public', $mapping->getGlob());
        $this->assertSame('local', $mapping->getTargetName());
        $this->assertSame('/blog', $mapping->getWebPath());
        $this->assertInstanceOf('Rhumsaa\Uuid\Uuid', $mapping->getUuid());
    }

    function testCreateWithUuid()
    {
        $uuid = Uuid::uuid4();
        $mapping = new WebPathMapping('/blog/public', 'local', '/blog', $uuid);

        $this->assertSame('/blog/public', $mapping->getGlob());
        $this->assertSame('local', $mapping->getTargetName());
        $this->assertSame('/blog', $mapping->getWebPath());
        $this->assertSame($uuid, $mapping->getUuid());
    }

    public function testCreateNormalizesWebPath()
    {
        $mapping = new WebPathMapping('/blog/public', 'local', 'blog/');

        $this->assertSame('/blog', $mapping->getWebPath());
    }

    public function testCreateWithEmptyWebPath()
    {
        $mapping = new WebPathMapping('/blog/public', 'local', '');

        $this->assertSame('/', $mapping->getWebPath());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfRepositoryPathNull()
    {
        new WebPathMapping(null, 'local', 'blog');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfRepositoryPathEmpty()
    {
        new WebPathMapping('', 'local', 'blog');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfRepositoryPathNoString()
    {
        new WebPathMapping(1234, 'local', 'blog');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfTargetNameNull()
    {
        new WebPathMapping('/blog/public', null, 'blog');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfTargetNameEmpty()
    {
        new WebPathMapping('/blog/public', '', 'blog');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfTargetNameNoString()
    {
        new WebPathMapping('/blog/public', 1234, 'blog');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfWebPathNull()
    {
        new WebPathMapping('/blog/public', 'local', null);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfWebPathNoString()
    {
        new WebPathMapping('/blog/public', 'local', 1234);
    }
}
