<?php

namespace BaseApiClient\Models;

use JsonSerializable;
use BaseApiClient\Media;
use BaseApiClient\Helpers;
use BaseApiClient\Transport\Response;

class Model implements JsonSerializable
{
    /**
     * The name associated with the model.
     *
     * @var string
     */
    protected $name;

    /**
     * Media associated with this model.
     *
     * @var array
     */
    protected $media = [];

    /**
     * The model's attributes
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Create a new model instance.
     *
     * @param  mixed  $attributes
     */
    public function __construct($attributes = null)
    {
        if ($attributes instanceof Response) {
            $this->fill($attributes->data);
        }
        else if (is_array($attributes)) {
            $this->fill($attributes);
        }
    }

    /**
     * Fill the attributes
     *
     * @param  array $attributes
     *
     * @return void
     */
    private function fill(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }
    }

    /**
     * Set a given attribute on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function setAttribute($key, $value)
    {
        // First we will check for the presence of a mutator for the set operation
        // which simply lets the developers tweak the attribute as it is set.
        if ($this->hasSetMutator($key)) {
            $method = 'set' . Helpers::studly($key) . 'Attribute';

            return $this->{$method}($value);
        }

        // If an attribute is listed as media, we'll convert it to the correct
        // media object.
        if (in_array($key, $this->media)) {
            $value = new Media($value);
        }

        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Get an attribute from the $attributes array.
     *
     * @param  string $key
     *
     * @return mixed
     */
    public function getAttribute($key)
    {
        $value = $this->getAttributeValue($key);

        // First we will check for the presence of a mutator for the set operation
        // which simply lets the developers tweak the attribute as it is set.
        if ($this->hasGetMutator($key)) {
            $method = 'get' . Helpers::studly($key) . 'Attribute';

            return $this->{$method}($value);
        }

        return $value;
    }

    /**
     * Get an attribute from the $attributes array.
     *
     * @param  string $key
     *
     * @return mixed
     */
    protected function getAttributeValue($key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }
    }

    /**
     * Determine if a set mutator exists for an attribute.
     *
     * @param  string $key
     * @return bool
     */
    public function hasSetMutator($key)
    {
        return method_exists($this, 'set' . Helpers::studly($key) . 'Attribute');
    }

    /**
     * Determine if a get mutator exists for an attribute.
     *
     * @param  string $key
     * @return bool
     */
    public function hasGetMutator($key)
    {
        return method_exists($this, 'get' . Helpers::studly($key) . 'Attribute');
    }

    /**
     * Convert the model instance to an array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->attributes;
    }

    /**
     * Convert the model instance to JSON
     *
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->toArray(), true);
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Get the model's attribute
     *
     * @param  string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Set the model's attribute
     *
     * @param  string $key
     * @param  mixed  $value
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Check if the model's attribute is set
     *
     * @param $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        return array_key_exists($key, $this->attributes);
    }

    /**
     * Convert the model to its string representation
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }
}