<?php

/*
 * This file is part of the puli/web-resource-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\WebResourcePlugin\Tests\Target;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class InstallTargetManagerImplLoadedTest extends InstallTargetManagerImplUnloadedTest
{
    protected function populateDefaultManager()
    {
        parent::populateDefaultManager();

        // Load the targets
        $this->targetManager->getTargets();
    }
}
