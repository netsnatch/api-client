<?php

namespace BaseApiClient\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ApiException extends \RuntimeException implements HttpExceptionInterface
{
    private $statusCode;

    public function __construct($message = null, $statusCode, $code = 0)
    {
        $this->statusCode = $statusCode;

        parent::__construct($message, $code);
    }

    /**
     * Returns response headers.
     *
     * @return array Response headers
     */
    public function getHeaders()
    {
        return [];
    }

    /**
     * Returns the status code.
     *
     * @return int An HTTP response status code
     */
    public function getStatusCode()
    {
        return $this->statusCode ?: 500;
    }
}