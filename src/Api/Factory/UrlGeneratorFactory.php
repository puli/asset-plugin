<?php

/*
 * This file is part of the puli/asset-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\AssetPlugin\Api\Factory;

use Puli\AssetPlugin\Api\UrlGenerator\ResourceUrlGenerator;
use Puli\Discovery\Api\ResourceDiscovery;
use Puli\Factory\PuliFactory;

/**
 * A factory for resource URL generators.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface UrlGeneratorFactory
{
    /**
     * Creates the URL generator.
     *
     * @param ResourceDiscovery $discovery The resource discovery to read from.
     *
     * @return ResourceUrlGenerator The created URL generator.
     */
    public function createUrlGenerator(ResourceDiscovery $discovery);
}
