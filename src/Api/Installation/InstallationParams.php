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

use Puli\AssetPlugin\Api\Asset\AssetMapping;
use Puli\AssetPlugin\Api\Installer\InstallerDescriptor;
use Puli\AssetPlugin\Api\Installer\ResourceInstaller;
use Puli\AssetPlugin\Api\Installer\Validation\ConstraintViolation;
use Puli\AssetPlugin\Api\Installer\Validation\InstallerParameterValidator;
use Puli\AssetPlugin\Api\Target\InstallTarget;
use Puli\Repository\Api\Resource\Resource;
use Puli\Repository\Api\ResourceCollection;
use Webmozart\Glob\Glob;
use Webmozart\PathUtil\Path;

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
     * @var InstallerDescriptor
     */
    private $installerDescriptor;

    /**
     * @var Resource
     */
    private $resources;

    /**
     * @var AssetMapping
     */
    private $mapping;

    /**
     * @var InstallTarget
     */
    private $installTarget;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var array
     */
    private $parameterValues;

    /**
     * Creates the installation request.
     *
     * @param ResourceInstaller   $installer           The used resource installer.
     * @param InstallerDescriptor $installerDescriptor The descriptor of the
     *                                                 resource installer.
     * @param ResourceCollection  $resources           The resources to install.
     * @param AssetMapping      $mapping             The web path mapping.
     * @param InstallTarget       $installTarget       The install target.
     * @param string              $rootDir             The project's root directory.
     */
    public function __construct(ResourceInstaller $installer, InstallerDescriptor $installerDescriptor, ResourceCollection $resources, AssetMapping $mapping, InstallTarget $installTarget, $rootDir)
    {
        $glob = $mapping->getGlob();
        $parameterValues = $installTarget->getParameterValues();

        $this->validateParameterValues($parameterValues, $installerDescriptor);

        $this->installer = $installer;
        $this->installerDescriptor = $installerDescriptor;
        $this->resources = $resources;
        $this->mapping = $mapping;
        $this->installTarget = $installTarget;
        $this->rootDir = $rootDir;
        $this->basePath = Glob::isDynamic($glob) ? Glob::getBasePath($glob) : $glob;
        $this->parameterValues = array_replace(
            $installerDescriptor->getParameterValues(),
            $parameterValues
        );
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
     * Returns the descriptor of the installer.
     *
     * @return InstallerDescriptor The descriptor of the installer.
     */
    public function getInstallerDescriptor()
    {
        return $this->installerDescriptor;
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
     * Returns the web path mapping.
     *
     * @return AssetMapping The web path mapping.
     */
    public function getMapping()
    {
        return $this->mapping;
    }

    /**
     * Returns the install target.
     *
     * @return InstallTarget The install target.
     */
    public function getInstallTarget()
    {
        return $this->installTarget;
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
        return $this->installTarget->getLocation();
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
        return $this->mapping->getWebPath();
    }

    /**
     * Returns the install path of a resource in the target location.
     *
     * This is a path relative to the root of the target location.
     *
     * @param Resource $resource The resource.
     *
     * @return string The web path.
     */
    public function getWebPathForResource(Resource $resource)
    {
        $relPath = Path::makeRelative($resource->getRepositoryPath(), $this->basePath);

        return '/'.trim($this->mapping->getWebPath().'/'.$relPath, '/');
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

    private function validateParameterValues(array $parameterValues, InstallerDescriptor $installerDescriptor)
    {
        $validator = new InstallerParameterValidator();
        $violations = $validator->validate($parameterValues, $installerDescriptor);

        foreach ($violations as $violation) {
            switch ($violation->getCode()) {
                case ConstraintViolation::MISSING_PARAMETER:
                    throw NotInstallableException::missingParameter(
                        $violation->getParameterName(),
                        $violation->getInstallerName()
                    );
                case ConstraintViolation::NO_SUCH_PARAMETER:
                    throw NotInstallableException::noSuchParameter(
                        $violation->getParameterName(),
                        $violation->getInstallerName()
                    );
            }
        }
    }
}
