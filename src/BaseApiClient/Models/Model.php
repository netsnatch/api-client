<?php

namespace BaseApiClient\Models;

use DateTime;
use Carbon\Carbon;
use JsonSerializable;
use BaseApiClient\Media;
use Illuminate\Support\Str;
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
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [];

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
            $method = 'set' . Str::studly($key) . 'Attribute';

            return $this->{$method}($value);
        }

        // If an attribute is listed as a "date", we'll convert it from a DateTime
        // instance into a form proper for storage on the database tables using
        // the connection grammar's date format. We will auto set the values.
        if ($value && in_array($key, $this->getDates())) {
            $value = $this->asDateTime($value);
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
     * Return a timestamp as DateTime object.
     *
     * @param  mixed $value
     * @return \Carbon\Carbon
     */
    protected function asDateTime($value)
    {
        // If the value is already a Carbon instance, we will just skip the rest of
        // these checks since they will be a waste of time, and hinder performance
        // when checking the field. We will just return the Carbon right away.
        if ($value instanceof Carbon) {
            //
        }

        // If this value is an integer, we will assume it is a UNIX timestamp's value
        // and format a Carbon object from this timestamp. This allows flexibility
        // when defining your date fields as they might be UNIX timestamps here.
        elseif (is_numeric($value)) {
            $date = new Carbon();
            return $date->setTimestamp($value);
        }

        // If the value is in simply year, month, day format, we will instantiate the
        // Carbon instances from that format. Again, this provides for simple date
        // fields on the database, while still supporting Carbonized conversion.
        elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value)) {
            return Carbon::createFromFormat('Y-m-d', $value);
        }

        // If the value is in simply hour, minute, second format, we will instantiate the
        // Carbon instances from that format.
        elseif (preg_match('/^(\d{2}):(\d{2}):(\d{2})$/', $value)) {
            return Carbon::createFromFormat('H:i:s', $value);
        }

        // If the value is in zulu format, we will instantiate the
        // Carbon instances from that format.
        elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})Z$/', $value)) {
            return Carbon::createFromFormat('Y-m-d\TH:i:s\Z', $value);
        }

        return new Carbon($value->format('Y-m-d H:i:s.u'), $value->getTimeZone());
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
            $method = 'get' . Str::studly($key) . 'Attribute';

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
        return method_exists($this, 'set' . Str::studly($key) . 'Attribute');
    }

    /**
     * Determine if a get mutator exists for an attribute.
     *
     * @param  string $key
     * @return bool
     */
    public function hasGetMutator($key)
    {
        return method_exists($this, 'get' . Str::studly($key) . 'Attribute');
    }

    /**
     * Get the attributes that should be converted to dates.
     *
     * @return array
     */
    public function getDates()
    {
        return $this->timestamps
            ? array_merge($this->dates, ['created_at', 'updated_at'])
            : $this->dates;
    }

    /**
     * Convert the model instance to an array
     *
     * @return array
     */
    public function toArray()
    {
        $attributes = $this->attributes;

        // If an attribute is a date, we will cast it to a string after converting it
        // to a DateTime / Carbon instance. This is so we will get some consistent
        // formatting while accessing attributes vs. arraying / JSONing a model.
        foreach ($this->getDates() as $key) {
            if (! isset($attributes[$key])) {
                continue;
            }

            if ($attributes[$key] instanceof DateTime) {
                $attributes[$key] = $attributes[$key]->format('Y-m-d\TH:i:s\Z');
            }
        }

        return $attributes;
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