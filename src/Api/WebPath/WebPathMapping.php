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

use Puli\RepositoryManager\Assert\Assert;
use Rhumsaa\Uuid\Uuid;

/**
 * Maps Puli resources to a web path on an install target.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class WebPathMapping
{
    /**
     * The UUID field in {@link Expression} instances.
     */
    const UUID = 'uuid';

    /**
     * The glob field in {@link Expression} instances.
     */
    const GLOB = 'glob';

    /**
     * The target name field in {@link Expression} instances.
     */
    const TARGET_NAME = 'targetName';

    /**
     * The web path field in {@link Expression} instances.
     */
    const WEB_PATH = 'webPath';

    /**
     * @var Uuid
     */
    private $uuid;

    /**
     * @var string
     */
    private $glob;

    /**
     * @var string
     */
    private $targetName;

    /**
     * @var string
     */
    private $webPath;

    /**
     * Creates the mapping.
     *
     * @param string $glob       A glob for resources in the repository.
     * @param string $targetName The name of the install target.
     * @param string $webPath    The web path of the resource in the install
     *                           target.
     * @param Uuid   $uuid       The UUID of the mapping.
     */
    public function __construct($glob, $targetName, $webPath, Uuid $uuid = null)
    {
        Assert::stringNotEmpty($glob, 'The glob must be a non-empty string. Got: %s');
        Assert::stringNotEmpty($targetName, 'The target name must be a non-empty string. Got: %s');
        Assert::string($webPath, 'The web path must be a string. Got: %s');

        $this->uuid = $uuid ?: Uuid::uuid4();
        $this->glob = $glob;
        $this->targetName = $targetName;
        $this->webPath = '/'.trim($webPath, '/');
    }

    /**
     * Returns the UUID of the mapping.
     *
     * @return Uuid The UUID of the mapping.
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * Returns the glob for the resources in the repository.
     *
     * @return string The repository path.
     */
    public function getGlob()
    {
        return $this->glob;
    }

    /**
     * Returns the name of the install target.
     *
     * @return string The target name.
     */
    public function getTargetName()
    {
        return $this->targetName;
    }

    /**
     * Returns the path of the resource relative to the install target root.
     *
     * @return string The relative web path.
     */
    public function getWebPath()
    {
        return $this->webPath;
    }
}
