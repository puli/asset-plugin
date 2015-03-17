<?php

/*
 * This file is part of the puli/web-resource-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\WebResourcePlugin\Api\Installer;

use Puli\Repository\Api\Resource\Resource;
use Puli\WebResourcePlugin\Api\Installation\InstallationParams;
use Puli\WebResourcePlugin\Api\Installation\NotInstallableException;

/**
 * Installs resources to a target location.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface ResourceInstaller
{
    /**
     * Validates whether the given installation parameters can be installed.
     *
     * This method can be used to validate the parameters for an installer
     * independent of the actual installation process. It is guaranteed to be
     * called before {@link installResource()}.
     *
     * @param InstallationParams $params The installation parameters containing
     *                                   all the additional information needed
     *                                   to perform the installation.
     *
     * @throws NotInstallableException If the parameters are invalid.
     */
    public function validateParams(InstallationParams $params);

    /**
     * Installs a resource to a target location.
     *
     * @param Resource           $resource The resource to install.
     * @param InstallationParams $params   The installation parameters containing
     *                                     all the additional information needed
     *                                     to perform the installation.
     *
     * @throws NotInstallableException If the installation fails.
     */
    public function installResource(Resource $resource, InstallationParams $params);
}
