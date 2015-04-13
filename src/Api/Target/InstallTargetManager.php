<?php

/*
 * This file is part of the puli/asset-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\AssetPlugin\Api\Target;

use Puli\AssetPlugin\Api\Installer\NoSuchInstallerException;
use Webmozart\Expression\Expression;

/**
 * Manages the targets where resources can be installed.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface InstallTargetManager
{
    /**
     * Adds an install target.
     *
     * If a target with the same name exists, the existing target is
     * overwritten.
     *
     * @param InstallTarget $target The target to add.
     *
     * @throws NoSuchInstallerException If the installer referred to by the
     *                                  target does not exist.
     */
    public function addTarget(InstallTarget $target);

    /**
     * Removes an install target.
     *
     * If the target does not exist, this method does nothing.
     *
     * @param string $targetName The name of the target.
     */
    public function removeTarget($targetName);

    /**
     * Removes all install targets matching the given expression.
     *
     * If no matching targets are found, this method does nothing.
     *
     * @param Expression $expr The search criteria.
     */
    public function removeTargets(Expression $expr);

    /**
     * Removes all install targets.
     *
     * If no targets are found, this method does nothing.
     */
    public function clearTargets();

    /**
     * Returns the install target with the given name.
     *
     * @param string $targetName The name of the target.
     *
     * @return InstallTarget The install target.
     *
     * @throws NoSuchTargetException If the target does not exist.
     */
    public function getTarget($targetName);

    /**
     * Returns all install targets.
     *
     * @return InstallTargetCollection The install targets.
     */
    public function getTargets();

    /**
     * Returns all install targets matching the given expression.
     *
     * @param Expression $expr The search criteria.
     *
     * @return InstallTargetCollection The install targets.
     */
    public function findTargets(Expression $expr);

    /**
     * Returns whether an install target exists.
     *
     * @param string $targetName The name of the target.
     *
     * @return bool Returns `true` if the target exists and `false` otherwise.
     */
    public function hasTarget($targetName);

    /**
     * Returns whether the manager has any targets.
     *
     * You can optionally pass an expression to check whether the manager has
     * targets matching that expression.
     *
     * @param Expression $expr The search criteria.
     *
     * @return bool Returns `true` if the manager has targets and `false`
     *              otherwise.
     */
    public function hasTargets(Expression $expr = null);

    /**
     * Sets the default target.
     *
     * By default, the first added target is the default target.
     *
     * @param string $targetName The name of the default target.
     *
     * @throws NoSuchTargetException If the target does not exist.
     */
    public function setDefaultTarget($targetName);

    /**
     * Returns the default target.
     *
     * By default, the first added target is the default target. The default
     * target can be changed with {@link setDefaultTarget()}.
     *
     * @return InstallTarget The default target.
     *
     * @throws NoSuchTargetException If the collection is empty.
     */
    public function getDefaultTarget();
}
