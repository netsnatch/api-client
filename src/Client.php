<?php

namespace BaseApiClient;

use BaseApiClient\HttpClient\Curl;
use BaseApiClient\Transport\Request;
use BaseApiClient\Exceptions\InvalidEndpointException;

abstract class Client
{
    /**
     * Namespace for the endpoints
     *
     * @var string
     */
    protected $endpointNamespace;

    /**
     * A reference to the request class which travels
     * through the application
     *
     * @var Transport\Request
     */
    public $request;

    /**
     * A array containing the cached endpoints
     *
     * @var array
     */
    private $cachedEndpoints = [];

    /**
     * Create a new client instance.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->request = new Request($config['domain'], new Curl());

        // Add client secret header
        if(isset($config['secret'])) {
            $this->request->setHeader('X-Client-Secret', $config['secret']);
        }
    }

    /**
     * Get an API endpoint.
     *
     * @param string $endpoint
     *
     * @return mixed
     * @throws Exceptions\InvalidEndpointException
     */
    public function getEndpoint($endpoint)
    {
        // Create studly class name
        $endpoint = Helpers::studly($endpoint);

        $class = "\\{$this->endpointNamespace}\\{$endpoint}";

        // Check if an instance has already been initiated
        if (isset($this->cachedEndpoints[$endpoint]) === false) {
            if (!class_exists($class)) {
                throw new InvalidEndpointException;
            }

            $this->cachedEndpoints[$endpoint] = new $class($this->request);
        }

        return $this->cachedEndpoints[$endpoint];
    }

    /**
     * Get an API endpoint
     *
     * @param string $endpoint
     *
     * @return mixed
     * @throws Exceptions\InvalidEndpointException
     */
    public function __get($endpoint)
    {
        return $this->getEndpoint($endpoint);
    }
}
