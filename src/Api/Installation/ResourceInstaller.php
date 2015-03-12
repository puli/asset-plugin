<?php

/*
 * This file is part of the puli/web-resource-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\WebResourcePlugin\Api\Installation;

/**
 * Installs resources to a target location.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface ResourceInstaller
{
    /**
     * Installs resources to a target location.
     *
     * @param InstallationRequest $request The installation request containing
     *                                     all the information needed to perform
     *                                     the installation.
     *
     * @throws CannotInstallResourcesException If the installation fails.
     */
    public function installResources(InstallationRequest $request);
}
