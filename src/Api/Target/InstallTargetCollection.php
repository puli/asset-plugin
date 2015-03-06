<?php

/*
 * This file is part of the puli/web-resource-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\WebResourcePlugin\Api\Target;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use LogicException;

/**
 * A collection of {@link InstallTarget} instances.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class InstallTargetCollection implements IteratorAggregate, ArrayAccess, Countable
{
    /**
     * @var InstallTarget[]
     */
    private $targets = array();

    /**
     * @var InstallTarget
     */
    private $defaultTarget;

    /**
     * Creates the collection.
     *
     * @param InstallTarget[] $targets The targets to initially fill into the
     *                                 collection.
     */
    public function __construct(array $targets = array())
    {
        $this->merge($targets);
    }

    /**
     * Adds a target to the collection.
     *
     * @param InstallTarget $target The target to add.
     */
    public function add(InstallTarget $target)
    {
        $this->targets[$target->getName()] = $target;

        if (!$this->defaultTarget) {
            $this->defaultTarget = $target;
        }
    }

    /**
     * Returns the target with the given name.
     *
     * @param string $targetName The target name.
     *
     * @return InstallTarget The target.
     *
     * @throws NoSuchTargetException If the target does not exist.
     */
    public function get($targetName)
    {
        if (InstallTarget::DEFAULT_TARGET === $targetName) {
            return $this->getDefaultTarget();
        }

        if (!isset($this->targets[$targetName])) {
            throw NoSuchTargetException::forTargetName($targetName);
        }

        return $this->targets[$targetName];
    }

    /**
     * Removes a target from the collection.
     *
     * If the target does not exist, this method does nothing.
     *
     * @param string $targetName The target name.
     */
    public function remove($targetName)
    {
        if (InstallTarget::DEFAULT_TARGET === $targetName && $this->defaultTarget) {
            $targetName = $this->defaultTarget->getName();
        }

        unset($this->targets[$targetName]);

        if ($this->defaultTarget && $targetName === $this->defaultTarget->getName()) {
            $this->defaultTarget = $this->targets ? reset($this->targets) : null;
        }
    }

    /**
     * Returns whether a target exists.
     *
     * @param string $targetName The target name.
     *
     * @return bool Whether the target exists.
     */
    public function contains($targetName)
    {
        if (InstallTarget::DEFAULT_TARGET === $targetName) {
            return null !== $this->defaultTarget;
        }

        return isset($this->targets[$targetName]);
    }

    /**
     * Removes all targets from the collection.
     */
    public function clear()
    {
        $this->targets = array();
        $this->defaultTarget = null;
    }

    /**
     * Returns the names of all targets in the collection.
     *
     * @return string[] The target names.
     */
    public function getTargetNames()
    {
        return array_keys($this->targets);
    }

    /**
     * Replaces the collection contents with the given targets.
     *
     * @param InstallTarget[] $targets The install targets to set.
     */
    public function replace(array $targets)
    {
        $this->clear();
        $this->merge($targets);
    }

    /**
     * Merges the given targets into the collection.
     *
     * @param InstallTarget[] $targets The install targets to add.
     */
    public function merge(array $targets)
    {
        foreach ($targets as $target) {
            $this->add($target);
        }
    }

    /**
     * Returns whether the collection is empty.
     *
     * @return bool Returns `true` if the collection contains no targets and
     *              `false` otherwise.
     */
    public function isEmpty()
    {
        return 0 === count($this->targets);
    }

    /**
     * Returns the collection contents as array.
     *
     * @return InstallTarget[] The targets in the collection indexed by their
     *                         names.
     */
    public function toArray()
    {
        return $this->targets;
    }

    /**
     * Returns the default target of the collection.
     *
     * By default, the first added target is the default target. The default
     * target can be changed with {@link setDefaultTarget}.
     *
     * @return InstallTarget Returns the default target.
     *
     * @throws NoSuchTargetException If the collection is empty.
     */
    public function getDefaultTarget()
    {
        if (!$this->defaultTarget) {
            throw new NoSuchTargetException('Cannot get the default target of an empty collection.');
        }

        return $this->defaultTarget;
    }

    /**
     * Sets the default target of the collection.
     *
     * @param string $targetName The name of the default target.
     *
     * @throws NoSuchTargetException If the target does not exist.
     */
    public function setDefaultTarget($targetName)
    {
        $this->defaultTarget = $this->get($targetName);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($targetName)
    {
        return $this->contains($targetName);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($targetName)
    {
        return $this->get($targetName);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($key, $target)
    {
        if (null !== $key) {
            throw new LogicException('Keys are not accepted when setting a value by array access.');
        }

        $this->add($target);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($targetName)
    {
        $this->remove($targetName);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new ArrayIterator($this->targets);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->targets);
    }
}
