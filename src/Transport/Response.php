<?php

namespace BaseApiClient\Transport;

use BaseApiClient\HttpClient\Curl;

class Response
{
    /**
     * Contains the raw response
     *
     * @var string
     */
    private $response;

    /**
     * Http code.
     *
     * @var int
     */
    private $http_code;

    /**
     * Create a new response instance
     *
     * @param  string $response
     * @param  Curl   $httpClient
     */
    public function __construct($response, Curl $httpClient)
    {
        $this->response = $response;
        $this->http_code = $httpClient->getHttpCode();

        if (is_string($response)) {
            $this->response = $this->decodeString($response);
        }
    }

    /**
     * Decode the string to an array
     *
     * @param  string $response
     *
     * @return array
     */
    private function decodeString($response)
    {
        return json_decode($response, true);
    }

    /**
     * Get the response code from the request
     *
     * @return int
     */
    public function getResponseCode()
    {
        return $this->http_code;
    }

    /**
     * Return the requested key data
     *
     * @param  string $key
     *
     * @return array|null
     */
    public function __get($key)
    {
        return $this->__isset($key) ? $this->response[$key] : null;
    }

    /**
     * Set requested key data
     *
     * @param  string $key
     * @param  string $value
     */
    public function __set($key, $value)
    {
        if ($this->__isset($key)) {
            $this->response[$key] = $value;
        }
    }

    /**
     * Return if the key is set
     *
     * @param  string $key
     *
     * @return boolean
     */
    public function __isset($key)
    {
        return isset($this->response[$key]);
    }
}