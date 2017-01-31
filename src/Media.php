<?php

namespace BaseApiClient;

class Media
{
    /**
     * Media values.
     *
     * @var bool
     */
    public $hasMedia = false;

    /**
     * Media values.
     *
     * @var array
     */
    protected $media = [];

    /**
     * Create a new Media instance.
     *
     * @param array $media
     */
    function __construct($media = [])
    {
        $this->media = $media;
        $this->hasMedia = !empty($this->media);
    }

    /**
     * Return the url to a file upload.
     *
     * @param string $size
     * @param mixed $default
     *
     * @return string
     */
    public function url($size = 'original', $default = '')
    {
        return array_get($this->media, $size, $default);
    }
}