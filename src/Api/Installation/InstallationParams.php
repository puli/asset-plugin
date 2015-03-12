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

use Puli\Repository\Api\ResourceCollection;
use Puli\WebResourcePlugin\Api\Installer\ResourceInstaller;

/**
 * Contains all the necessary information to install resources on a target.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class InstallationParams
{
    /**
     * @var ResourceInstaller
     */
    private $installer;

    /**
     * @var Resource
     */
    private $resources;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var string
     */
    private $basePath;

    /**
     * @var string
     */
    private $targetLocation;

    /**
     * @var string
     */
    private $webPath;

    /**
     * @var array
     */
    private $parameterValues;

    /**
     * Creates the installation request.
     *
     * @param ResourceInstaller  $installer       The used resource installer.
     * @param ResourceCollection $resources       The resources to install.
     * @param string             $rootDir         The project's root directory.
     * @param string             $basePath        The common repository base
     *                                            path of all the resources.
     * @param string             $targetLocation  The location where to install
     *                                            the resources.
     * @param string             $webPath         The path where to install the
     *                                            resources in the target
     *                                            location.
     * @param array              $parameterValues Values for the installer
     *                                            parameters.
     */
    public function __construct(ResourceInstaller $installer, ResourceCollection $resources, $rootDir, $basePath, $targetLocation, $webPath, array $parameterValues = array())
    {
        $this->installer = $installer;
        $this->resources = $resources;
        $this->rootDir = $rootDir;
        $this->basePath = $basePath;
        $this->targetLocation = $targetLocation;
        $this->webPath = '/'.trim($webPath, '/');
        $this->parameterValues = $parameterValues;
    }

    /**
     * Returns the used resource installer.
     *
     * @return ResourceInstaller The installer used to install the resources in
     *                           the target location.
     */
    public function getInstaller()
    {
        return $this->installer;
    }

    /**
     * Returns the installed resources.
     *
     * @return ResourceCollection The installed resources.
     */
    public function getResources()
    {
        return $this->resources;
    }

    /**
     * Returns the root directory of the Puli project.
     *
     * @return string The project's root directory.
     */
    public function getRootDirectory()
    {
        return $this->rootDir;
    }

    /**
     * Returns the common base path of the installed resources.
     *
     * @return string The common base path of the installed resources.
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * Returns the location string of the install target.
     *
     * This can be a directory name, a URL or any other string that can be
     * interpreted by the installer.
     *
     * @return string The target location.
     */
    public function getTargetLocation()
    {
        return $this->targetLocation;
    }

    /**
     * Returns the install path in the target location.
     *
     * This is a path relative to the root of the target location.
     *
     * @return string The web path.
     */
    public function getWebPath()
    {
        return $this->webPath;
    }

    /**
     * Returns the installer parameters.
     *
     * The result is a merge of the default parameter values of the installer
     * and the parameter values set for the specific install target.
     *
     * @return array The installer parameters.
     */
    public function getParameterValues()
    {
        return $this->parameterValues;
    }
}
