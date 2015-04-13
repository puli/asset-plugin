<?php

/*
 * This file is part of the puli/asset-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\AssetPlugin\Api\Asset;

use Puli\AssetPlugin\Api\Target\NoSuchTargetException;
use Rhumsaa\Uuid\Uuid;
use Webmozart\Expression\Expression;

/**
 * Manages asset mappings.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface AssetManager
{
    /**
     * Flag: Override existing asset mappings in {@link addAssetMapping()}.
     */
    const OVERRIDE = 1;

    /**
     * Flag: Ignore if the target does not exist in {@link addAssetMapping()}.
     */
    const IGNORE_TARGET_NOT_FOUND = 2;

    /**
     * Adds an asset mapping to the repository.
     *
     * The mapping is added to the root package.
     *
     * @param AssetMapping $mapping The asset mapping.
     * @param int          $flags   A bitwise combination of the flag constants
     *                              in this class.
     *
     * @throws NoSuchTargetException If the target referred to by the mapping
     *                               does not exist.
     * @throws DuplicateAssetMappingException If a mapping with the same UUID
     *                                        exists already.
     */
    public function addRootAssetMapping(AssetMapping $mapping, $flags = 0);

    /**
     * Removes an asset mapping from the repository.
     *
     * The mapping is removed from the root package. If the UUID is not found,
     * this method does nothing.
     *
     * @param Uuid $uuid The UUID of the mapping.
     */
    public function removeRootAssetMapping(Uuid $uuid);

    /**
     * Removes all asset mappings matching the given expression.
     *
     * The mappings are removed from the root package. If no matching mappings
     * are found, this method does nothing.
     *
     * @param Expression $expr The search criteria.
     */
    public function removeRootAssetMappings(Expression $expr);

    /**
     * Removes all asset mappings from the repository.
     *
     * The mappings are removed from the root package. If no matching mappings
     * are found, this method does nothing.
     */
    public function clearRootAssetMappings();

    /**
     * Returns the asset mapping for a web path from the root package.
     *
     * @param Uuid $uuid The UUID of the mapping.
     *
     * @return AssetMapping The corresponding asset mapping.
     *
     * @throws NoSuchAssetMappingException If the web path is not mapped in the
     *                                     root package.
     */
    public function getRootAssetMapping(Uuid $uuid);

    /**
     * Returns all asset mappings in the root package.
     *
     * @return AssetMapping[] The asset mappings.
     */
    public function getRootAssetMappings();

    /**
     * Returns whether a web path is mapped in the root package.
     *
     * @param Uuid $uuid The UUID of the mapping.
     *
     * @return bool Returns `true` if the web path is mapped in the root package.
     */
    public function hasRootAssetMapping(Uuid $uuid);

    /**
     * Returns all asset mappings in the root package matching the given
     * expression.
     *
     * @param Expression $expr The search criteria.
     *
     * @return AssetMapping[] The asset mappings matching the expression.
     */
    public function findRootAssetMappings(Expression $expr);

    /**
     * Returns whether any web paths are mapped in the root package.
     *
     * You can optionally pass an expression to check whether the manager has
     * mappings matching that expression.
     *
     * @param Expression $expr The search criteria.
     *
     * @return bool Returns `true` if any web paths are mapped in the root
     *                      package.
     */
    public function hasRootAssetMappings(Expression $expr = null);

    /**
     * Returns the asset mapping for a web path.
     *
     * @param Uuid $uuid The UUID of the mapping.
     *
     * @return AssetMapping The corresponding asset mapping.
     *
     * @throws NoSuchAssetMappingException If the web path is not mapped.
     */
    public function getAssetMapping(Uuid $uuid);

    /**
     * Returns all asset mappings.
     *
     * @return AssetMapping[] The asset mappings.
     */
    public function getAssetMappings();

    /**
     * Returns all asset mappings matching the given expression.
     *
     * @param Expression $expr The search criteria.
     *
     * @return AssetMapping[] The asset mappings matching the expression.
     */
    public function findAssetMappings(Expression $expr);

    /**
     * Returns whether a web path is mapped.
     *
     * @param Uuid $uuid The UUID of the mapping.
     *
     * @return bool Returns `true` if the web path is mapped.
     */
    public function hasAssetMapping(Uuid $uuid);

    /**
     * Returns whether any web paths are mapped.
     *
     * You can optionally pass an expression to check whether the manager has
     * mappings matching that expression.
     *
     * @param Expression $expr The search criteria.
     *
     * @return bool Returns `true` if any web paths are mapped.
     */
    public function hasAssetMappings(Expression $expr = null);

}
