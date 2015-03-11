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

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface InstallerManager
{
    public function getInstaller($name);

    public function addInstallerDescriptor(InstallerDescriptor $descriptor);

    public function removeInstallerDescriptor($name);

    public function getInstallerDescriptor($name);

    public function getInstallerDescriptors();

    public function hasInstallerDescriptor($name);

    public function hasInstallerDescriptors();
}
