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

use Puli\WebResourcePlugin\Api\WebPath\WebPathMapping;

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
     * {@link InstallationRequest} which can be passed to
     * {@link executeInstallation()}.
     *
     * @param WebPathMapping $mapping The web path mapping.
     *
     * @return InstallationRequest The installation request.
     *
     * @throws CannotInstallResourcesException If the installation is not possible.
     */
    public function prepareInstallation(WebPathMapping $mapping);

    /**
     * Installs resources on their target.
     *
     * @param InstallationRequest $request The installation request obtained by
     *                                     {@link prepareInstallation()}.
     *
     * @throws CannotInstallResourcesException If the installation fails.
     */
    public function executeInstallation(InstallationRequest $request);
}
