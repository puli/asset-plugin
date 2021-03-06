<?php

/*
 * This file is part of the puli/asset-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\AssetPlugin\UrlGenerator;

use Puli\AssetPlugin\Api\AssetPlugin;
use Puli\AssetPlugin\Api\Target\InstallTargetCollection;
use Puli\AssetPlugin\Api\UrlGenerator\CannotGenerateUrlException;
use Puli\AssetPlugin\Api\UrlGenerator\AssetUrlGenerator;
use Puli\Discovery\Api\Binding\ResourceBinding;
use Puli\Discovery\Api\ResourceDiscovery;
use Webmozart\Glob\Glob;

/**
 * A resource URL generator that uses a {@link ResourceDiscovery} as backend.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class DiscoveryUrlGenerator implements AssetUrlGenerator
{
    /**
     * @var ResourceDiscovery
     */
    private $discovery;

    /**
     * @var InstallTargetCollection
     */
    private $targets;

    /**
     * Creates the URL generator.
     *
     * @param ResourceDiscovery       $discovery The resource discovery.
     * @param InstallTargetCollection $targets   The available targets.
     */
    public function __construct(ResourceDiscovery $discovery, InstallTargetCollection $targets)
    {
        $this->discovery = $discovery;
        $this->targets = $targets;
    }

    /**
     * {@inheritdoc}
     */
    public function generateUrl($repositoryPath, $currentUrl = null)
    {
        $bindings = $this->discovery->findByPath($repositoryPath, AssetPlugin::BINDING_TYPE);
        $count = count($bindings);

        if (0 === $count) {
            throw new CannotGenerateUrlException(sprintf(
                'No web path mapping exists for path "%s".',
                $repositoryPath
            ));
        }

        // We can't prevent a resource to be mapped to more than one web path
        // For now, we'll just take the first one and make the user responsible
        // for preventing duplicates
        $url = $this->generateUrlForBinding(reset($bindings), $repositoryPath);

        if ($currentUrl) {
            // TODO use Url::makeRelative() once it exists
        }

        return $url;
    }

    private function generateUrlForBinding(ResourceBinding $binding, $repositoryPath)
    {
        $bindingPath = Glob::getStaticPrefix($binding->getQuery());
        $webBasePath = trim($binding->getParameterValue(AssetPlugin::PATH_PARAMETER), '/');
        $webPath = substr_replace($repositoryPath, $webBasePath, 0, strlen($bindingPath));

        $targetName = $binding->getParameterValue(AssetPlugin::TARGET_PARAMETER);

        if (!$this->targets->contains($targetName)) {
            throw new CannotGenerateUrlException(sprintf(
                'The target "%s" mapped for path "%s" does not exist.',
                $targetName,
                $repositoryPath
            ));
        }

        $target = $this->targets->get($targetName);

        return sprintf($target->getUrlFormat(), ltrim($webPath, '/'));
    }
}
