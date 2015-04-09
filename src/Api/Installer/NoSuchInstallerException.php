<?php

/*
 * This file is part of the puli/asset-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\AssetPlugin\Api\Installer;

use Exception;

/**
 * Thrown when an installer was not found.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class NoSuchInstallerException extends Exception
{
    /**
     * Creates an exception for an installer name that was not found.
     *
     * @param string    $installerName The installer name.
     * @param Exception $cause         The exception that caused this exception.
     *
     * @return static The created exception.
     */
    public static function forInstallerName($installerName, Exception $cause = null)
    {
        return new static(sprintf(
            'The installer "%s" does not exist.',
            $installerName
        ), 0, $cause);
    }
}
