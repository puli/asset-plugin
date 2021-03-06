<?php

/*
 * This file is part of the puli/asset-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\AssetPlugin\Target;

use Exception;
use Puli\AssetPlugin\Api\AssetPlugin;
use Puli\AssetPlugin\Api\Installer\InstallerManager;
use Puli\AssetPlugin\Api\Installer\NoSuchInstallerException;
use Puli\AssetPlugin\Api\Target\InstallTarget;
use Puli\AssetPlugin\Api\Target\InstallTargetCollection;
use Puli\AssetPlugin\Api\Target\InstallTargetManager;
use Puli\Manager\Api\Package\RootPackageFileManager;
use stdClass;
use Webmozart\Expression\Expr;
use Webmozart\Expression\Expression;
use Webmozart\Json\JsonValidator;
use Webmozart\Json\ValidationFailedException;

/**
 * An install target manager that stores the targets in the package file.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PackageFileInstallTargetManager implements InstallTargetManager
{
    /**
     * @var RootPackageFileManager
     */
    private $rootPackageFileManager;

    /**
     * @var InstallerManager
     */
    private $installerManager;

    /**
     * @var InstallTargetCollection
     */
    private $targets;

    /**
     * @var array
     */
    private $targetsData = array();

    public function __construct(RootPackageFileManager $rootPackageFileManager, InstallerManager $installerManager)
    {
        $this->rootPackageFileManager = $rootPackageFileManager;
        $this->installerManager = $installerManager;
    }

    /**
     * {@inheritdoc}
     */
    public function addTarget(InstallTarget $target)
    {
        $this->assertTargetsLoaded();

        if (!$this->installerManager->hasInstallerDescriptor($target->getInstallerName())) {
            throw NoSuchInstallerException::forInstallerName($target->getInstallerName());
        }

        $previousTargets = $this->targets->toArray();
        $previousData = $this->targetsData;

        $this->targets->add($target);
        $this->targetsData[$target->getName()] = $this->targetToData($target);

        try {
            $this->persistTargetsData();
        } catch (Exception $e) {
            $this->targets->replace($previousTargets);
            $this->targetsData = $previousData;

            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeTarget($targetName)
    {
        $this->removeTargets(Expr::same($targetName, InstallTarget::NAME));
    }

    /**
     * {@inheritdoc}
     */
    public function removeTargets(Expression $expr)
    {
        $this->assertTargetsLoaded();

        $previousTargets = $this->targets->toArray();
        $previousData = $this->targetsData;
        $save = false;

        foreach ($this->targets as $target) {
            if ($target->match($expr)) {
                $this->targets->remove($target->getName());
                unset($this->targetsData[$target->getName()]);
                $save = true;
            }
        }

        if (!$save) {
            return;
        }

        try {
            $this->persistTargetsData();
        } catch (Exception $e) {
            $this->targets->replace($previousTargets);
            $this->targetsData = $previousData;

            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clearTargets()
    {
        $this->removeTargets(Expr::valid());
    }

    /**
     * {@inheritdoc}
     */
    public function getTarget($targetName)
    {
        $this->assertTargetsLoaded();

        return $this->targets->get($targetName);
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        $this->assertTargetsLoaded();

        return $this->targets;
    }

    /**
     * {@inheritdoc}
     */
    public function findTargets(Expression $expr)
    {
        $this->assertTargetsLoaded();

        $targets = array();

        foreach ($this->targets as $target) {
            if ($target->match($expr)) {
                $targets[] = $target;
            }
        }

        return new InstallTargetCollection($targets);
    }

    /**
     * {@inheritdoc}
     */
    public function hasTarget($targetName)
    {
        $this->assertTargetsLoaded();

        return $this->targets->contains($targetName);
    }

    /**
     * {@inheritdoc}
     */
    public function hasTargets(Expression $expr = null)
    {
        $this->assertTargetsLoaded();

        if (!$expr) {
            return !$this->targets->isEmpty();
        }

        foreach ($this->targets as $target) {
            if ($target->match($expr)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultTarget($targetName)
    {
        $this->assertTargetsLoaded();

        $this->targets->setDefaultTarget($targetName);

        $this->persistTargetsData();
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultTarget()
    {
        $this->assertTargetsLoaded();

        return $this->targets->getDefaultTarget();
    }

    private function assertTargetsLoaded()
    {
        if (null !== $this->targets) {
            return;
        }

        $targetsData = $this->rootPackageFileManager->getExtraKey(AssetPlugin::INSTALL_TARGETS_KEY);

        if ($targetsData) {
            $jsonValidator = new JsonValidator();
            $errors = $jsonValidator->validate($targetsData, __DIR__.'/../../res/schema/install-targets-schema-1.0.json');

            if (count($errors) > 0) {
                throw new ValidationFailedException(sprintf(
                    "The extra key \"%s\" is invalid:\n%s",
                    AssetPlugin::INSTALL_TARGETS_KEY,
                    implode("\n", $errors)
                ));
            }
        }

        $this->targets = new InstallTargetCollection();
        $this->targetsData = (array) $targetsData;

        foreach ($this->targetsData as $targetName => $targetData) {
            $this->targets->add($this->dataToTarget($targetName, $targetData));

            if (isset($targetData->default) && $targetData->default) {
                $this->targets->setDefaultTarget($targetName);
            }
        }
    }

    private function persistTargetsData()
    {
        if ($this->targetsData) {
            $this->updateDefaultTargetData();

            $this->rootPackageFileManager->setExtraKey(AssetPlugin::INSTALL_TARGETS_KEY, (object) $this->targetsData);
        } else {
            $this->rootPackageFileManager->removeExtraKey(AssetPlugin::INSTALL_TARGETS_KEY);
        }
    }

    private function dataToTarget($targetName, stdClass $data)
    {
        return new InstallTarget(
            $targetName,
            $data->installer,
            $data->location,
            isset($data->{'url-format'}) ? $data->{'url-format'} : InstallTarget::DEFAULT_URL_FORMAT,
            isset($data->parameters) ? (array) $data->parameters : array()
        );
    }

    private function targetToData(InstallTarget $target)
    {
        $data = new stdClass();
        $data->installer = $target->getInstallerName();
        $data->location = $target->getLocation();

        if (InstallTarget::DEFAULT_URL_FORMAT !== ($urlFormat = $target->getUrlFormat())) {
            $data->{'url-format'} = $urlFormat;
        }

        if ($parameters = $target->getParameterValues()) {
            $data->parameters = (object) $parameters;
        }

        return $data;
    }

    private function updateDefaultTargetData()
    {
        $defaultTarget = $this->targets->isEmpty() ? null : $this->targets->getDefaultTarget()->getName();

        foreach ($this->targetsData as $targetName => &$data) {
            if ($targetName === $defaultTarget) {
                $data->default = true;
            } else {
                unset($data->default);
            }
        }
    }
}
