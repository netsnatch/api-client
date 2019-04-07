<?php

namespace BaseApiClient\HttpClient;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class Curl
{
    /**
     * Last request http status.
     *
     * @var int
     **/
    protected $http_code = 200;
    
    /**
     * Connect timeout in milliseconds
     * @var int
     */
    protected $connect_timeout = 2500;
    
    /**
     * Execute timeout in milliseconds
     * @var int
     */
    protected $timeout = 4000;

    /**
     * Last request error string.
     *
     * @var string
     **/
    protected $errors = null;

    /**
     * Array containing headers from last performed request.
     *
     * @var array
     */
    private $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
    ];

    public function __construct(array $config = [])
    {
        if (isset($config['connect_timeout'])) {
            $this->connect_timeout = $config['connect_timeout'];
        }
        
        if (isset($config['timeout'])) {
            $this->timeout = $config['timeout'];
        }
    }
    
    /**
     * Add multiple headers to request.
     *
     * @param array $values
     */
    public function setHeaders(array $values)
    {
        foreach ($values as $key => $value) {
            $this->setHeader($key, $value);
        }
    }

    /**
     * Add header to request.
     *
     * @param string $key
     * @param string $value
     */
    public function setHeader($key, $value)
    {
        $this->headers[$key] = "{$key}: {$value}";
    }

    /**
     * Execute the curl request
     *
     * @param  string $method
     * @param  string $url
     * @param  array  $parameters
     * @param  array  $headers
     *
     * @return array
     */
    public function execute($method, $url, array $parameters = [], array $headers = [])
    {
        $this->errors = null;

        $curl = curl_init();

        // Merge global and request headers
        $headers = array_merge(array_values($this->headers), $headers);

        // Set options
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => $this->connect_timeout,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_HEADER => 1,
            CURLINFO_HEADER_OUT => 1,
            CURLOPT_VERBOSE => 1,
        ]);

        // Setup method specific options
        switch ($method)
        {
            case 'PUT':
            case 'PATCH':
            case 'POST':
                curl_setopt_array($curl, [
                    CURLOPT_CUSTOMREQUEST => $method,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $this->build_json_for_curl($parameters),
                ]);
                break;

            case 'DELETE':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;

            default:
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
                break;
        }

        // Make request
        curl_setopt($curl, CURLOPT_HEADER, true);
        $response = curl_exec($curl);

        // Set HTTP response code
        $this->http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // Set errors if there are any
        if (curl_errno($curl)) {
            $this->errors = curl_error($curl);
        }

        // Parse body
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        curl_close($curl);

        return [$body, $this->parseHeaders($header)];
    }

    /**
     * Build http query that will be cUrl compliant.
     *
     * @param array $params
     *
     * @return string
     */
    protected function build_json_for_curl($params)
    {
        $params = array_map(function($item) {
            return ($item instanceof UploadedFile)
                ? $this->base64EncodeImage($item)
                : $item;
        }, $params);

        return json_encode($params);
    }

    /**
     * Base64 encode a file.
     *
     * Not a fan of this, but it will work for now at least.
     * Look into implementing something like what Twitter has
     * for uploading media.
     *
     * https://dev.twitter.com/rest/reference/post/media/upload
     *
     * @param UploadedFile $file
     *
     * @return array
     */
    protected function base64EncodeImage(UploadedFile $file)
    {
        $data = file_get_contents($file->getRealPath());

        return [
            'name' => $file->getClientOriginalName(),
            'base64' => base64_encode($data),
        ];
    }

    /**
     * Check if the curl request ended up with errors
     *
     * @return bool
     */
    public function hasErrors()
    {
        return is_null($this->errors) === false;
    }

    /**
     * Get curl errors
     *
     * @return string
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get last curl HTTP code.
     *
     * @return int
     */
    public function getHttpCode()
    {
        return $this->http_code;
    }

    /**
     * Parse string headers into array
     *
     * @param string $headers
     *
     * @return array
     */
    private function parseHeaders($headers)
    {
        $result = [];

        foreach (explode("\n", $headers) as $row) {
            $header = explode(':', $row, 2);

            if (count($header) == 2) {
                $result[$header[0]] = trim($header[1]);
            }
            else {
                $result[] = $header[0];
            }
        }

        return $result;
    }
}