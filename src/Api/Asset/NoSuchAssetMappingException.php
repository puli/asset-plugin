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

use Exception;
use Rhumsaa\Uuid\Uuid;

/**
 * Thrown when an asset mapping was not found.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class NoSuchAssetMappingException extends Exception
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
            'The asset mapping "%s" does not exist.',
            $uuid->toString()
        ), $code, $cause);
    }
}
