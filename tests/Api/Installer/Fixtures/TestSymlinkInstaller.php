<?php

/*
 * This file is part of the puli/web-resource-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\WebResourcePlugin\Tests\Api\Installer\Fixtures;

use Puli\Repository\Api\Resource\Resource;
use Puli\WebResourcePlugin\Api\Installer\ResourceInstaller;
use Puli\WebResourcePlugin\Api\Target\InstallTarget;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class TestSymlinkInstaller implements ResourceInstaller
{
    public function installResource(Resource $resource, InstallTarget $target)
    {
    }
}
