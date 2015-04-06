<?php

/*
 * This file is part of the puli/asset-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\AssetPlugin\Tests\Installation\Fixtures;

use Puli\AssetPlugin\Api\Installation\InstallationParams;
use Puli\AssetPlugin\Api\Installer\ResourceInstaller;
use Puli\Repository\Api\Resource\Resource;

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
