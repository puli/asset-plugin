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

use OutOfBoundsException;
use Puli\RepositoryManager\Assert\Assert;

/**
 * A target where resources can be installed.
 *
 * An install target has a name which identifies the target. Additionally, it
 * has a location string which represents the location where the resources will
 * be installed. The location can be a directory name, a URL or any other string
 * that can be interpreted by a {@link ResourceInstaller}.
 *
 * Parameters can be set on the target to pass additional information to the
 * installer that can not be obtained from the location string. Examples are
 * user names or passwords and other, similar connection settings.
 *
 * An install target also has a URL format. This format defines the format of
 * the URLs generated for resources installed in that target. For example, if
 * targets are installed directly in the public directory, then you will set the
 * URL format to "/%s". If resources are installed in the sub-directory
 * "resources", the proper URL format is "/resources/%s". If resources are
 * installed on another server or a CDN, you can set the URL format to the full
 * domain: "http://my.cdn.com/%s".
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class InstallTarget
{
    /**
     * The alias for the default target.
     */
    const DEFAULT_TARGET = 'default';

    /**
     * The default URL format.
     */
    const DEFAULT_URL_FORMAT = '/%s';

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $installerName;

    /**
     * @var string
     */
    private $location;

    /**
     * @var string
     */
    private $urlFormat;

    /**
     * @var string[]
     */
    private $parameters;

    /**
     * Creates a new install target.
     *
     * @param string $name          The name of the target.
     * @param string $installerName The name of the used installer.
     * @param string $location      The location where resources are installed.
     * @param string $urlFormat     The format of the generated resource URLs.
     *                              Include the placeholder "%s" for the resource
     *                              path relative to the target location.
     * @param array  $parameters    Additional parameters to be processed by the
     *                              resource installer or the URL generator.
     */
    public function __construct($name, $installerName, $location, $urlFormat = self::DEFAULT_URL_FORMAT, array $parameters = array())
    {
        Assert::string($name, 'The target name must be a string. Got: %s');
        Assert::notEmpty($name, 'The target name must not be empty.');
        Assert::notEq($name, self::DEFAULT_TARGET, 'The target name must not be "'.self::DEFAULT_TARGET.'".');
        Assert::string($installerName, 'The installer name must be a string. Got: %s');
        Assert::notEmpty($installerName, 'The installer name must not be empty.');
        Assert::string($location, 'The target location must be a string. Got: %s');
        Assert::notEmpty($location, 'The target location must not be empty.');
        Assert::string($urlFormat, 'The target URL format must be a string. Got: %s');
        Assert::notEmpty($urlFormat, 'The target URL format must not be empty.');

        $this->name = $name;
        $this->installerName = $installerName;
        $this->location = $location;
        $this->urlFormat = $urlFormat;
        $this->parameters = $parameters;
    }

    /**
     * Returns the target name.
     *
     * @return string The target name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the name of the used installer.
     *
     * @return string The installer name.
     */
    public function getInstallerName()
    {
        return $this->installerName;
    }

    /**
     * Returns the target location.
     *
     * The target location can be a directory name, a URL or any other string
     * that can be understood by a {@link ResourceInstaller}.
     *
     * @return string The target location.
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Returns the format of the generated resource URLs.
     *
     * The format contains the placeholder "%s" where the resource path relative
     * to the target location is inserted.
     *
     * @return string The URL format.
     */
    public function getUrlFormat()
    {
        return $this->urlFormat;
    }

    /**
     * Returns the value of the given parameter.
     *
     * @param string $name The parameter name.
     *
     * @return mixed The parameter value.
     *
     * @throws OutOfBoundsException If the parameter does not exist.
     */
    public function getParameter($name)
    {
        if (!isset($this->parameters[$name])) {
            throw new OutOfBoundsException(sprintf(
                'The target parameter "%s" does not exist. Did you forget to set it?',
                $name
            ));
        }

        return $this->parameters[$name];
    }

    /**
     * Returns the values of all parameters.
     *
     * @return string[] The parameter values indexed by the parameter names.
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Returns whether the target has a given parameter.
     *
     * @param string $name The parameter name.
     *
     * @return bool Returns `true` if the given parameter exists and `false`
     *              otherwise.
     */
    public function hasParameter($name)
    {
        return isset($this->parameters[$name]);
    }

    /**
     * Returns whether the target has any parameters.
     *
     * @return bool Returns `true` if any parameters are set for the target and
     *              `false` otherwise.
     */
    public function hasParameters()
    {
        return count($this->parameters) > 0;
    }
}
