<?php

/*
 * This file is part of the puli/web-resource-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\WebResourcePlugin\Tests\Api\Target;

use PHPUnit_Framework_TestCase;
use Puli\WebResourcePlugin\Api\Target\InstallTarget;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class InstallTargetTest extends PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $target = new InstallTarget('local', 'symlink', 'web/assets', '/assets/%s', array('user' => 'webmozart'));

        $this->assertSame('local', $target->getName());
        $this->assertSame('symlink', $target->getInstallerName());
        $this->assertSame('web/assets', $target->getLocation());
        $this->assertSame('/assets/%s', $target->getUrlFormat());
        $this->assertSame(array('user' => 'webmozart'), $target->getParameters());
    }

    public function testCreateWithDefaultUrlFormat()
    {
        $target = new InstallTarget('local', 'symlink', 'web/assets');

        $this->assertSame('/%s', $target->getUrlFormat());
        $this->assertSame(array(), $target->getParameters());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfNameNull()
    {
        new InstallTarget(null, 'symlink', 'web/assets');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfNameEmpty()
    {
        new InstallTarget('', 'symlink', 'web/assets');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfNameNoString()
    {
        new InstallTarget(1234, 'symlink', 'web/assets');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfNameDefault()
    {
        new InstallTarget(InstallTarget::DEFAULT_TARGET, 'symlink', 'web/assets');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfInstallerNameNull()
    {
        new InstallTarget('local', null, 'web/assets');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfInstallerNameEmpty()
    {
        new InstallTarget('local', '', 'web/assets');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfInstallerNameNoString()
    {
        new InstallTarget('local', 1234, 'web/assets');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfLocationNull()
    {
        new InstallTarget('local', 'symlink', null);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfLocationEmpty()
    {
        new InstallTarget('local', 'symlink', '');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfLocationNoString()
    {
        new InstallTarget('local', 'symlink', 1234);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfUrlFormatNull()
    {
        new InstallTarget('local', 'symlink', 'web/assets', null);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfUrlFormatEmpty()
    {
        new InstallTarget('local', 'symlink', 'web/assets', '');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfUrlFormatNoString()
    {
        new InstallTarget('local', 'symlink', 'web/assets', 1234);
    }

    public function testGetParameter()
    {
        $target = new InstallTarget('local', 'symlink', 'web', '/%s', array('param1' => 'value1', 'param2' => 'value2'));

        $this->assertSame('value1', $target->getParameter('param1'));
        $this->assertSame('value2', $target->getParameter('param2'));
    }

    /**
     * @expectedException \OutOfBoundsException
     */
    public function testGetParameterFailsIfNotFound()
    {
        $target = new InstallTarget('local', 'symlink', 'web');

        $target->getParameter('foobar');
    }

    public function testHasParameter()
    {
        $target = new InstallTarget('local', 'symlink', 'web', '/%s', array('param1' => 'value1', 'param2' => 'value2'));

        $this->assertTrue($target->hasParameter('param1'));
        $this->assertTrue($target->hasParameter('param2'));
        $this->assertFalse($target->hasParameter('foo'));
    }

    public function testHasParameters()
    {
        $target = new InstallTarget('local', 'symlink', 'web', '/%s', array('param1' => 'value1'));

        $this->assertTrue($target->hasParameters());
    }

    public function testHasNoParameters()
    {
        $target = new InstallTarget('local', 'symlink', 'web', '/%s', array());

        $this->assertFalse($target->hasParameters());
    }
}
