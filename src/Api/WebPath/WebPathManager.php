<?php

/*
 * This file is part of the puli/web-resource-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\WebResourcePlugin\Api\WebPath;

use Rhumsaa\Uuid\Uuid;
use Webmozart\Expression\Expression;

/**
 * Manages web path mappings.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface WebPathManager
{
    /**
     * Adds a web path mapping to the repository.
     *
     * @param WebPathMapping $mapping The web path mapping.
     */
    public function addWebPathMapping(WebPathMapping $mapping);

    /**
     * Removes a web path mapping from the repository.
     *
     * @param Uuid $uuid The UUID of the mapping.
     */
    public function removeWebPathMapping(Uuid $uuid);

    /**
     * Returns the web path mapping for a web path.
     *
     * @param Uuid $uuid The UUID of the mapping.
     *
     * @return WebPathMapping The corresponding web path mapping.
     *
     * @throws NoSuchWebPathMappingException If the web path is not mapped.
     */
    public function getWebPathMapping(Uuid $uuid);

    /**
     * Returns all web path mappings.
     *
     * @return WebPathMapping[] The web path mappings.
     */
    public function getWebPathMappings();

    /**
     * Returns all web path mappings matching the given expression.
     *
     * @param Expression $expr The search criteria.
     *
     * @return WebPathMapping[] The web path mappings matching the expression.
     */
    public function findWebPathMappings(Expression $expr);

    /**
     * Returns whether a web path is mapped.
     *
     * @param Uuid $uuid The UUID of the mapping.
     *
     * @return bool Returns `true` if the web path is mapped.
     */
    public function hasWebPathMapping(Uuid $uuid);

    /**
     * Returns whether a web path is mapped.
     *
     * You can optionally pass ane expression to check whether the manager has
     * bindings matching that expression.
     *
     * @param Expression $expr The search criteria.
     *
     * @return bool Returns `true` if the web path is mapped.
     */
    public function hasWebPathMappings(Expression $expr = null);

}
