<?php

/*
 * This file is part of the puli/web-resource-plugin package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\WebResourcePlugin\Api\Installation\Installer;

use Exception;

/**
 * Thrown when an installer parameter is missing.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class MissingParameterException extends Exception
{
    /**
     * Creates an exception for a parameter name that is missing.
     *
     * @param string    $parameterName The parameter name.
     * @param string    $installerName The installer name.
     * @param int       $code          The exception code.
     * @param Exception $cause         The exception that caused this exception.
     *
     * @return static The created exception.
     */
    public static function forParameterName($parameterName, $installerName, $code = 0, Exception $cause = null)
    {
        return new static(sprintf(
            'The installer parameter "%s" is required for the "%s" installer.',
            $parameterName,
            $installerName
        ), $code, $cause);
    }
}
