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

use Puli\WebResourcePlugin\Api\Installation\InstallationRequest;
use Puli\WebResourcePlugin\Api\Installation\ResourceInstaller;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class TestInstallerWithoutDefaultConstructor implements ResourceInstaller
{
    public function __construct($param)
    {
    }

    public function installResources(InstallationRequest $request)
    {
    }
}
