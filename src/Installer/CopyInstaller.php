<?php

/*
 * This file is part of the puli/web-resource-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\WebResourcePlugin\Installer;

use Puli\Repository\Api\Resource\Resource;
use Puli\Repository\FilesystemRepository;
use Puli\WebResourcePlugin\Api\Installation\InstallationParams;
use Puli\WebResourcePlugin\Api\Installation\InvalidMappingException;
use Puli\WebResourcePlugin\Api\Installer\ResourceInstaller;
use Webmozart\PathUtil\Path;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class CopyInstaller implements ResourceInstaller
{
    /**
     * @var bool
     */
    private $symlinks;

    public function __construct($symlinks = false)
    {
        $this->symlinks = $symlinks;
    }

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
        $subPath = $params->getWebPath().'/'.Path::makeRelative($resource->getRepositoryPath(), $params->getBasePath());

        if (!file_exists($targetPath)) {
            mkdir($targetPath, 0777, true);
        }

        $repoPath = '/'.trim($subPath, '/');

        $filesystemRepo = new FilesystemRepository($targetPath, $this->symlinks);

        if ('/' !== $repoPath) {
            $filesystemRepo->remove($repoPath);
        }

        $filesystemRepo->add($repoPath, $resource);
    }
}
