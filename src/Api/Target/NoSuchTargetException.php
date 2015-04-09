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

use Exception;

/**
 * Thrown when an install target was not found.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class NoSuchTargetException extends Exception
{
    /**
     * Creates an exception for a target name that was not found.
     *
     * @param string    $targetName The target name.
     * @param Exception $cause      The exception that caused this exception.
     *
     * @return static The created exception.
     */
    public static function forTargetName($targetName, Exception $cause = null)
    {
        return new static(sprintf(
            'The install target "%s" does not exist.',
            $targetName
        ), 0, $cause);
    }
}
