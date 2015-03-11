<?php

/*
 * This file is part of the puli/web-resource-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\WebResourcePlugin\Api\Installer;

use Exception;

/**
 * Thrown when an installer parameter was not found.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class NoSuchParameterException extends Exception
{
    /**
     * Creates an exception for a parameter name that was not found.
     *
     * @param string    $parameterName The parameter name.
     * @param int       $code          The exception code.
     * @param Exception $cause         The exception that caused this exception.
     *
     * @return static The created exception.
     */
    public static function forParameterName($parameterName, $code = 0, Exception $cause = null)
    {
        return new static(sprintf(
            'The installer parameter "%s" does not exist.',
            $parameterName
        ), $code, $cause);
    }
}
