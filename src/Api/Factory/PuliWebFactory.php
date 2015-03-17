<?php

/*
 * This file is part of the puli/web-resource-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\WebResourcePlugin\Api\Factory;

use Puli\Discovery\Api\ResourceDiscovery;
use Puli\Factory\PuliFactory;
use Puli\WebResourcePlugin\Api\UrlGenerator\ResourceUrlGenerator;

/**
 * A Puli factory that is able to create resource URL generators.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface PuliWebFactory extends PuliFactory
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
