<?php

/*
 * This file is part of the puli/asset-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\AssetPlugin\Api\UrlGenerator;

/**
 * Generates URLs for Puli resources.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface ResourceUrlGenerator
{
    /**
     * Generates a URL for a Puli resource.
     *
     * Depending on the configuration of the resource, the returned URL is
     * either relative to the current domain or absolute.
     *
     * If you pass the current URL, the returned URL is relative to the passed
     * URL.
     *
     * @param string $repositoryPath The repository path of the resource.
     * @param null   $currentUrl     The current URL.
     *
     * @return string The URL of the resource.
     *
     * @throws CannotGenerateUrlException If the URL cannot be generated.
     */
    public function generateUrl($repositoryPath, $currentUrl = null);
}
