<?php


namespace WebRover\Framework\Kernel\Exception;


/**
 * Class TooManyRequestsHttpException
 * @package WebRover\Framework\Kernel\Exception
 */
class TooManyRequestsHttpException extends HttpException
{
    /**
     * @param int|string $retryAfter The number of seconds or HTTP-date after which the request may be retried
     * @param string $message The internal exception message
     * @param \Exception $previous The previous exception
     * @param int $code The internal exception code
     */
    public function __construct($retryAfter = null, $message = null, \Exception $previous = null, $code = 0)
    {
        $headers = [];
        if ($retryAfter) {
            $headers = ['Retry-After' => $retryAfter];
        }

        parent::__construct(429, $message, $previous, $headers, $code);
    }
}
