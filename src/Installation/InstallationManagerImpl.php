<?php

/*
 * This file is part of the puli/asset-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\AssetPlugin\Installation;

use Puli\AssetPlugin\Api\Asset\AssetMapping;
use Puli\AssetPlugin\Api\Installation\InstallationManager;
use Puli\AssetPlugin\Api\Installation\InstallationParams;
use Puli\AssetPlugin\Api\Installation\NotInstallableException;
use Puli\AssetPlugin\Api\Installer\InstallerDescriptor;
use Puli\AssetPlugin\Api\Installer\InstallerManager;
use Puli\AssetPlugin\Api\Installer\ResourceInstaller;
use Puli\AssetPlugin\Api\Target\InstallTargetCollection;
use Puli\Manager\Api\Environment\ProjectEnvironment;
use Puli\Repository\Api\Resource\Resource;
use Puli\Repository\Api\ResourceRepository;
use ReflectionClass;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class InstallationManagerImpl implements InstallationManager
{
    /**
     * @var ProjectEnvironment
     */
    private $environment;

    /**
     * @var ResourceRepository
     */
    private $repo;

    /**
     * @var InstallTargetCollection
     */
    private $installTargets;

    /**
     * @var InstallerManager
     */
    private $installerManager;

    /**
     * @var ResourceInstaller[]
     */
    private $installers = array();

    public function __construct(ProjectEnvironment $environment, ResourceRepository $repo, InstallTargetCollection $installTargets, InstallerManager $installerManager)
    {
        $this->environment = $environment;
        $this->repo = $repo;
        $this->installTargets = $installTargets;
        $this->installerManager = $installerManager;
    }

    /**
     * {@inheritdoc}
     */
    public function prepareInstallation(AssetMapping $mapping)
    {
        $glob = $mapping->getGlob();
        $targetName = $mapping->getTargetName();
        $resources = $this->repo->find($glob);

        if ($resources->isEmpty()) {
            throw NotInstallableException::noResourceMatches($glob);
        }

        if (!$this->installTargets->contains($targetName)) {
            throw NotInstallableException::targetNotFound($targetName);
        }

        $target = $this->installTargets->get($targetName);
        $installerName = $target->getInstallerName();

        if (!$this->installerManager->hasInstallerDescriptor($installerName)) {
            throw NotInstallableException::installerNotFound($installerName);
        }

        $installerDescriptor = $this->installerManager->getInstallerDescriptor($installerName);
        $installer = $this->loadInstaller($installerDescriptor);
        $rootDir = $this->environment->getRootDirectory();

        $params = new InstallationParams($installer, $installerDescriptor, $resources, $mapping, $target, $rootDir);

        $installer->validateParams($params);

        return $params;
    }

    /**
     * {@inheritdoc}
     */
    public function installResource(Resource $resource, InstallationParams $params)
    {
        // Validate, as we cannot guarantee that the installation parameters
        // were actually retrieved via prepareInstallation()
        $params->getInstaller()->validateParams($params);

        // Go!
        $params->getInstaller()->installResource($resource, $params);
    }

    private function loadInstaller(InstallerDescriptor $descriptor)
    {
        $installerName = $descriptor->getName();

        if (!isset($this->installers[$installerName])) {
            $installerClass = $descriptor->getClassName();

            $this->validateInstallerClass($installerClass);

            $this->installers[$installerName] = new $installerClass();
        }

        return $this->installers[$installerName];
    }

    private function validateInstallerClass($installerClass)
    {
        if (!class_exists($installerClass)) {
            throw NotInstallableException::installerClassNotFound($installerClass);
        }

        $reflClass = new ReflectionClass($installerClass);

        if ($reflClass->hasMethod('__construct') && $reflClass->getMethod('__construct')->getNumberOfRequiredParameters() > 0) {
            throw NotInstallableException::installerClassNoDefaultConstructor($installerClass);
        }

        if (!$reflClass->implementsInterface('Puli\AssetPlugin\Api\Installer\ResourceInstaller')) {
            throw NotInstallableException::installerClassInvalid($installerClass);
        }
    }
}
