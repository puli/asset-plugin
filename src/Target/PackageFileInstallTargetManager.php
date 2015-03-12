<?php

/*
 * This file is part of the puli/web-resource-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\WebResourcePlugin\Target;

use Puli\RepositoryManager\Api\Package\RootPackageFileManager;
use Puli\WebResourcePlugin\Api\Target\InstallTarget;
use Puli\WebResourcePlugin\Api\Target\InstallTargetCollection;
use Puli\WebResourcePlugin\Api\Target\InstallTargetManager;
use Puli\WebResourcePlugin\Api\WebResourcePlugin;
use RuntimeException;

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
     * @var InstallTargetCollection
     */
    private $targets;

    /**
     * @var array
     */
    private $targetsData = array();

    public function __construct(RootPackageFileManager $rootPackageFileManager)
    {
        $this->rootPackageFileManager = $rootPackageFileManager;
    }

    /**
     * {@inheritdoc}
     */
    public function addTarget(InstallTarget $target)
    {
        $this->assertTargetsLoaded();

        $this->targets->add($target);

        $this->targetsData[$target->getName()] = $this->targetToData($target);

        $this->persistTargetsData();
    }

    /**
     * {@inheritdoc}
     */
    public function removeTarget($targetName)
    {
        $this->assertTargetsLoaded();

        $this->targets->remove($targetName);

        if (isset($this->targetsData[$targetName])) {
            unset($this->targetsData[$targetName]);

            $this->persistTargetsData();
        }
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
    public function hasTarget($targetName)
    {
        $this->assertTargetsLoaded();

        return $this->targets->contains($targetName);
    }

    /**
     * {@inheritdoc}
     */
    public function hasTargets()
    {
        $this->assertTargetsLoaded();

        return !$this->targets->isEmpty();
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

        $targetsData = $this->rootPackageFileManager->getExtraKey(WebResourcePlugin::INSTALL_TARGETS_KEY, array());

        if (!is_array($targetsData)) {
            throw new RuntimeException(sprintf(
                'The extra key "%s" must contain an array. Got: %s',
                WebResourcePlugin::INSTALL_TARGETS_KEY,
                is_object($targetsData) ? get_class($targetsData) : gettype($targetsData)
            ));
        }

        $this->targets = new InstallTargetCollection();
        $this->targetsData = $targetsData;

        foreach ($this->targetsData as $targetName => $targetData) {
            $this->targets->add($this->dataToTarget($targetName, $targetData));

            if (isset($targetData['default'])) {
                $this->targets->setDefaultTarget($targetName);
            }
        }
    }

    private function persistTargetsData()
    {
        if ($this->targetsData) {
            $this->updateDefaultTargetData();

            $this->rootPackageFileManager->setExtraKey(WebResourcePlugin::INSTALL_TARGETS_KEY, $this->targetsData);
        } else {
            $this->rootPackageFileManager->removeExtraKey(WebResourcePlugin::INSTALL_TARGETS_KEY);
        }
    }

    private function dataToTarget($targetName, $targetData)
    {
        if (!isset($targetData['installer'])) {
            throw new RuntimeException(sprintf(
                'The "installer" key is missing for the install target "%s".',
                $targetName
            ));
        }

        if (!isset($targetData['location'])) {
            throw new RuntimeException(sprintf(
                'The "location" key is missing for the install target "%s".',
                $targetName
            ));
        }

        return new InstallTarget(
            $targetName,
            $targetData['installer'],
            $targetData['location'],
            isset($targetData['url-format'])
                ? $targetData['url-format']
                : InstallTarget::DEFAULT_URL_FORMAT,
            isset($targetData['parameters'])
                ? $targetData['parameters']
                : array()
        );
    }

    private function targetToData(InstallTarget $target)
    {
        $targetData = array(
            'installer' => $target->getInstallerName(),
            'location' => $target->getLocation(),
        );

        if (InstallTarget::DEFAULT_URL_FORMAT !== ($urlFormat = $target->getUrlFormat())) {
            $targetData['url-format'] = $urlFormat;
        }

        if ($parameters = $target->getParameterValues()) {
            $targetData['parameters'] = $parameters;

            return $targetData;
        }

        return $targetData;
    }

    private function updateDefaultTargetData()
    {
        $defaultTarget = $this->targets->isEmpty() ? null : $this->targets->getDefaultTarget()->getName();

        foreach ($this->targetsData as $targetName => &$targetData) {
            if ($targetName === $defaultTarget) {
                $targetData['default'] = true;
            } else {
                unset($targetData['default']);
            }
        }
    }
}
