<?php

namespace BaseApiClient\Endpoints;

use BaseApiClient\Transport\Request;

class Endpoint
{
    /**
     * Instance of the request class
     *
     * @var Request
     */
    protected $request;

    /**
     * Create a new model instance
     *
     * @param  Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
}