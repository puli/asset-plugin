<?php

/*
 * This file is part of the puli/asset-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\AssetPlugin\Tests\Target;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PackageFileInstallTargetManagerLoadedTest extends PackageFileInstallTargetManagerUnloadedTest
{
    protected function populateDefaultManager()
    {
        parent::populateDefaultManager();

        // Load the targets
        $this->targetManager->getTargets();
    }
}
