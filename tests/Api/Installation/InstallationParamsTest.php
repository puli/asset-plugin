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
use Puli\WebResourcePlugin\Api\Installation\InstallationParams;
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
        $resources = new ArrayResourceCollection();

        $params = new InstallationParams(
            $installer,
            $resources,
            '/root',
            '/base/path',
            'location',
            '/web',
            array('param' => 'value')
        );

        $this->assertSame($installer, $params->getInstaller());
        $this->assertSame($resources, $params->getResources());
        $this->assertSame('/root', $params->getRootDirectory());
        $this->assertSame('/base/path', $params->getBasePath());
        $this->assertSame('location', $params->getTargetLocation());
        $this->assertSame('/web', $params->getWebPath());
        $this->assertSame(array('param' => 'value'), $params->getParameterValues());
    }

    public function testCreateNormalizesWebPath()
    {
        $installer = new TestInstaller();
        $resources = new ArrayResourceCollection();

        $params = new InstallationParams(
            $installer,
            $resources,
            '/root',
            '/base/path',
            'location',
            'web/',
            array('param' => 'value')
        );

        $this->assertSame('/web', $params->getWebPath());
    }

    public function testCreateWithEmptyWebPath()
    {
        $installer = new TestInstaller();
        $resources = new ArrayResourceCollection();

        $params = new InstallationParams(
            $installer,
            $resources,
            '/root',
            '/base/path',
            'location',
            '',
            array('param' => 'value')
        );

        $this->assertSame('/', $params->getWebPath());
    }
}
