<?php

/*
 * This file is part of the puli/web-resource-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\WebResourcePlugin\Tests\Installation\Fixtures;

use Puli\Repository\Api\Resource\Resource;
use Puli\WebResourcePlugin\Api\Installation\InstallationParams;
use Puli\WebResourcePlugin\Api\Installer\ResourceInstaller;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class TestInstaller implements ResourceInstaller
{
    private static $validatedParams;

    public static function getValidatedParams()
    {
        return self::$validatedParams;
    }

    public static function resetValidatedParams()
    {
        self::$validatedParams = null;
    }

    public function __construct($param = null)
    {
    }

    public function validateParams(InstallationParams $params)
    {
        self::$validatedParams = $params;
    }

    public function installResource(Resource $resource, InstallationParams $params)
    {
    }
}
