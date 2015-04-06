<?php

/*
 * This file is part of the puli/asset-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\AssetPlugin\Api\Installation;

use Puli\AssetPlugin\Api\WebPath\WebPathMapping;
use Puli\Repository\Api\Resource\Resource;

/**
 * Manages the installation of resources.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface InstallationManager
{
    /**
     * Prepares the installation of a web path mapping.
     *
     * If the preparation succeeds, this method returns an
     * {@link InstallationParams} instance which can be passed to
     * {@link executeInstallation()}.
     *
     * @param WebPathMapping $mapping The web path mapping.
     *
     * @return InstallationParams The installation parameters.
     *
     * @throws NotInstallableException If the installation is not possible.
     */
    public function prepareInstallation(WebPathMapping $mapping);

    /**
     * Installs a resource on its target.
     *
     * @param Resource           $resource The resource to install.
     * @param InstallationParams $params   The installation parameters returned
     *                                     by {@link prepareInstallation()}.
     *
     * @throws NotInstallableException If the installation fails.
     */
    public function installResource(Resource $resource, InstallationParams $params);
}
