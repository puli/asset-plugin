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

use Exception;
use Rhumsaa\Uuid\Uuid;

/**
 * Thrown when a web path mapping was not found.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class NoSuchWebPathMappingException extends Exception
{
    /**
     * Creates an exception for a UUID that was not found.
     *
     * @param Uuid      $uuid  The UUID of the mapping.
     * @param int       $code  The exception code.
     * @param Exception $cause The exception that caused this exception.
     *
     * @return static The created exception.
     */
    public static function forUuid(Uuid $uuid, $code = 0, Exception $cause = null)
    {
        return new static(sprintf(
            'The web path mapping "%s" does not exist.',
            $uuid->toString()
        ), $code, $cause);
    }
}
