<?php

/*
 * This file is part of the puli/web-resource-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\WebResourcePlugin\Installation;

use Puli\Repository\Api\Resource\Resource;
use Puli\RepositoryManager\Api\Environment\ProjectEnvironment;
use Puli\WebResourcePlugin\Api\Installation\CannotInstallResourcesException;
use Puli\WebResourcePlugin\Api\Installation\InstallationManager;
use Puli\WebResourcePlugin\Api\Installation\InstallationParams;
use Puli\WebResourcePlugin\Api\Installation\Installer\InstallerDescriptor;
use Puli\WebResourcePlugin\Api\Installation\Installer\InstallerManager;
use Puli\WebResourcePlugin\Api\Installation\Installer\Validation\ConstraintViolation;
use Puli\WebResourcePlugin\Api\Installation\Installer\Validation\InstallerParameterValidator;
use Puli\WebResourcePlugin\Api\Installation\ResourceInstaller;
use Puli\WebResourcePlugin\Api\Target\InstallTargetCollection;
use Puli\WebResourcePlugin\Api\WebPath\WebPathMapping;
use ReflectionClass;
use Webmozart\Glob\Glob;

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

    public function __construct(ProjectEnvironment $environment, InstallTargetCollection $installTargets, InstallerManager $installerManager)
    {
        $this->environment = $environment;
        $this->installTargets = $installTargets;
        $this->installerManager = $installerManager;
    }

    /**
     * {@inheritdoc}
     */
    public function prepareInstallation(WebPathMapping $mapping)
    {
        $glob = $mapping->getGlob();
        $targetName = $mapping->getTargetName();
        $resources = $this->environment->getRepository()->find($glob);

        if ($resources->isEmpty()) {
            throw CannotInstallResourcesException::noResourceMatches($glob);
        }

        if (!$this->installTargets->contains($targetName)) {
            throw CannotInstallResourcesException::targetNotFound($targetName);
        }

        $target = $this->installTargets->get($targetName);
        $installerName = $target->getInstallerName();

        if (!$this->installerManager->hasInstallerDescriptor($installerName)) {
            throw CannotInstallResourcesException::installerNotFound($installerName);
        }

        $installerDescriptor = $this->installerManager->getInstallerDescriptor($installerName);
        $parameterValues = $target->getParameterValues();

        $this->validateParameterValues($parameterValues, $installerDescriptor);

        $installer = $this->loadInstaller($installerDescriptor);
        $rootDir = $this->environment->getRootDirectory();
        $basePath = Glob::getBasePath($glob);
        $location = $target->getLocation();
        $webPath = $mapping->getWebPath();

        // Merge with default parameters
        $parameterValues = array_replace(
            $installerDescriptor->getParameterValues(),
            $parameterValues
        );

        return new InstallationParams($installer, $resources, $rootDir, $basePath, $location, $webPath, $parameterValues);
    }

    /**
     * {@inheritdoc}
     */
    public function installResource(Resource $resource, InstallationParams $params)
    {
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

    private function validateParameterValues(array $parameterValues, InstallerDescriptor $installerDescriptor)
    {
        $validator = new InstallerParameterValidator();
        $violations = $validator->validate($parameterValues, $installerDescriptor);

        foreach ($violations as $violation) {
            switch ($violation->getCode()) {
                case ConstraintViolation::MISSING_PARAMETER:
                    throw CannotInstallResourcesException::missingParameter(
                        $violation->getParameterName(),
                        $violation->getInstallerName()
                    );
                case ConstraintViolation::NO_SUCH_PARAMETER:
                    throw CannotInstallResourcesException::noSuchParameter(
                        $violation->getParameterName(),
                        $violation->getInstallerName()
                    );
            }
        }
    }

    private function validateInstallerClass($installerClass)
    {
        if (!class_exists($installerClass)) {
            throw CannotInstallResourcesException::installerClassNotFound($installerClass);
        }

        $reflClass = new ReflectionClass($installerClass);

        if ($reflClass->hasMethod('__construct') && $reflClass->getMethod('__construct')->getNumberOfRequiredParameters() > 0) {
            throw CannotInstallResourcesException::installerClassNoDefaultConstructor($installerClass);
        }

        if (!$reflClass->implementsInterface('Puli\WebResourcePlugin\Api\Installation\ResourceInstaller')) {
            throw CannotInstallResourcesException::installerClassInvalid($installerClass);
        }
    }
}
