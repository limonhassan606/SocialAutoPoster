<?php

namespace LimonHasan\SocialAutoPoster\Exceptions;

use Exception;

/**
 * Class SocialMediaException
 *
 * Custom exception class for social media API errors.
 */
class SocialMediaException extends Exception
{
    /**
     * Create a new social media exception instance.
     *
     * @param string $message The exception message.
     * @param int $code The exception code.
     * @param Exception|null $previous The previous exception.
     */
    public function __construct(string $message = "", int $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
