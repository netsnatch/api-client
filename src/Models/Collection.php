<?php

namespace BaseApiClient\Models;

use Exception;
use ArrayAccess;
use ArrayIterator;
use JsonSerializable;
use IteratorAggregate;
use BaseApiClient\Arr;
use BaseApiClient\Transport\Response;
use BaseApiClient\Exceptions\InvalidModelException;

class Collection implements JsonSerializable, ArrayAccess, IteratorAggregate
{
    /**
     * The items in the collection
     *
     * @var array
     */
    private $items = [];

    /**
     * Stores the meta object
     *
     * @var array
     */
    public $meta = [];

    /**
     * Response instance
     *
     * @var Response
     */
    private $response;

    /**
     * Create a new model instance.
     *
     * @param  array|Response $items
     * @param  string         $model
     *
     * @throws InvalidModelException|Exception
     */
    public function __construct($items, $model)
    {
        // Ensure model exists
        if (class_exists($model) === false) {
            throw new InvalidModelException;
        }

        // Get items and response instance
        if ($items instanceof Response) {
            $this->response = $items;
            $this->meta = $items->meta ?: [];
            $this->items = $items->data;
        }
        else if (is_array($items)) {
            $this->response = null;
            $this->items = $items;
        }
        else {
            throw new Exception("{$items} needs to be an instance of Transport\\Response or an array.");
        }

        // Transform the raw collection data to models
        $this->items = $this->buildCollectionModels($this->items, $model);
    }

    /**
     * Get total number of items.
     *
     * @return int
     */
    public function total()
    {
        $total = $this->getMeta('pagination.total', null);

        if (is_null($total)) {
            $this->setMeta('pagination.total', $total = count($this->items));
        }

        return $total;
    }

    /**
     * Determine if there are more items in the data source.
     *
     * @return bool
     */
    public function hasMorePages()
    {
        return $this->getMeta('pagination.current_page', 0) < $this->getMeta('pagination.total_pages', 0);
    }

    /**
     * Get all items from the collection
     *
     * @return array
     */
    public function all()
    {
        return $this->items;
    }

    /**
     * Get an item from the meta data using "dot" notation.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function getMeta($key, $default = null)
    {
        return Arr::get($this->meta, $key, $default);
    }

    /**
     * Set an item in the meta data using "dot" notation.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return mixed
     */
    public function setMeta($key, $value)
    {
        return Arr::set($this->meta, $key, $value);
    }

    /**
     * Transform each raw item into a model
     *
     * @param  array  $items
     * @param  string $model
     *
     * @return array
     */
    private function buildCollectionModels(array $items, $model)
    {
        $collection = [];

        foreach ($items as $item) {
            $collection[] = new $model($item);
        }

        return $collection;
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
        return array_key_exists($key, $this->meta) ? $this->meta[$key] : null;
    }

    /**
     * Convert the collection to an array
     *
     * @return array
     */
    public function toArray()
    {
        $items = [];

        foreach ($this->items as $item) {
            $items[] = $item->toArray();
        }

        return [
            'data' => $items,
            'meta' => $this->meta,
        ];
    }

    /**
     * Convert the collection to JSON
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
     * Convert the collection to its string representation
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * Determine if the given item exists.
     *
     * @param  mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->items[$offset]);
    }

    /**
     * Get the value for a given offset.
     *
     * @param  mixed $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->items[$offset];
    }

    /**
     * Set the value for a given offset.
     *
     * @param  mixed $offset
     * @param  mixed $value
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->items[$offset] = $value;
    }

    /**
     * Unset the value for a given offset.
     *
     * @param  mixed $offset
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->items[$offset]);
    }

    /**
     * Make the collection items iteratable
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }
}