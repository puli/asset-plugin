<?php

/*
 * This file is part of the puli/asset-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\AssetPlugin\Installer;

use Puli\AssetPlugin\Api\Installation\InstallationParams;
use Puli\AssetPlugin\Api\Installer\ResourceInstaller;
use Puli\Repository\Api\Resource\FilesystemResource;
use Puli\Repository\Api\Resource\Resource;
use Puli\Repository\FilesystemRepository;
use Webmozart\PathUtil\Path;

/**
 * Installs resources via a local filesystem copy.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class CopyInstaller implements ResourceInstaller
{
    /**
     * @var bool
     */
    protected $symlinks = false;

    /**
     * {@inheritdoc}
     */
    public function validateParams(InstallationParams $params)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function installResource(Resource $resource, InstallationParams $params)
    {
        $targetPath = Path::makeAbsolute($params->getTargetLocation(), $params->getRootDirectory());

        if (!file_exists($targetPath)) {
            mkdir($targetPath, 0777, true);
        }

        $repoPath = $params->getWebPathForResource($resource);
        $parameterValues = $params->getParameterValues();
        $relative = !isset($parameterValues['relative']) || $parameterValues['relative'];
        $filesystemRepo = new FilesystemRepository($targetPath, $this->symlinks, $relative);

        if ('/' === $repoPath) {
            foreach ($resource->listChildren() as $child) {
                $name = $child->getName();

                // If the resource is not attached, the name is empty
                if (!$name && $child instanceof FilesystemResource) {
                    $name = Path::getFilename($child->getFilesystemPath());
                }

                if ($name) {
                    $filesystemRepo->remove($repoPath.'/'.$name);
                }
            }
        } else {
            $filesystemRepo->remove($repoPath);
        }

        $filesystemRepo->add($repoPath, $resource);
    }
}
