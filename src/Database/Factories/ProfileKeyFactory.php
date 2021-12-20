<?php

namespace TestInstance\LaravelModelProfiles\Database\Factories;

use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProfileKeyFactory extends StickyModelFactory
{

    /**
     * Define the model's default state.
     *
     * @return string[]
     * @throws Exception
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->lexify(str_repeat('?', random_int(10, 40))),
            'type' => 'string',
        ];
    }

    /**
     * Indicate that the type is integer.
     *
     * @return Factory
     */
    public function integer(): Factory
    {
        return $this->state(function () {
            return [
                'type' => 'integer',
            ];
        });
    }

    /**
     * Indicate that the type is float.
     *
     * @return Factory
     */
    public function float(): Factory
    {
        return $this->state(function () {
            return [
                'type' => 'float',
            ];
        });
    }

    /**
     * Indicate that the type is boolean.
     *
     * @return Factory
     */
    public function boolean(): Factory
    {
        return $this->state(function () {
            return [
                'type' => 'boolean',
            ];
        });
    }

    /**
     * Indicate that the type is object.
     *
     * @return Factory
     */
    public function object(): Factory
    {
        return $this->state(function () {
            return [
                'type' => 'object',
            ];
        });
    }

    /**
     * Indicate that the type is array.
     *
     * @return Factory
     */
    public function array(): Factory
    {
        return $this->state(function () {
            return [
                'type' => 'array',
            ];
        });
    }

    /**
     * Indicate that the type is collection.
     *
     * @return Factory
     */
    public function collection(): Factory
    {
        return $this->state(function () {
            return [
                'type' => 'collection',
            ];
        });
    }

    /**
     * Indicate that the type is date.
     *
     * @return Factory
     */
    public function date(): Factory
    {
        return $this->state(function () {
            return [
                'type' => 'date',
            ];
        });
    }

    /**
     * Indicate that the type is datetime.
     *
     * @return Factory
     */
    public function datetime(): Factory
    {
        return $this->state(function () {
            return [
                'type' => 'datetime',
            ];
        });
    }

    /**
     * Indicate that the type is timestamp.
     *
     * @return Factory
     */
    public function timestamp(): Factory
    {
        return $this->state(function () {
            return [
                'type' => 'timestamp',
            ];
        });
    }

    /**
     * Indicate that the type is encrypted.
     *
     * @return Factory
     */
    public function encrypted(): Factory
    {
        return $this->state(function () {
            return [
                'type' => 'encrypted',
            ];
        });
    }

    /**
     * Indicate that the type is an encrypted array.
     *
     * @return Factory
     */
    public function encryptedArray(): Factory
    {
        return $this->state(function () {
            return [
                'type' => 'encrypted:array',
            ];
        });
    }

    /**
     * Indicate that the type is an encrypted collection.
     *
     * @return Factory
     */
    public function encryptedCollection(): Factory
    {
        return $this->state(function () {
            return [
                'type' => 'encrypted:collection',
            ];
        });
    }

    /**
     * Indicate that the type is an encrypted json.
     *
     * @return Factory
     */
    public function encryptedJson(): Factory
    {
        return $this->state(function () {
            return [
                'type' => 'encrypted:json',
            ];
        });
    }

    /**
     * Indicate that the type is an encrypted object.
     *
     * @return Factory
     */
    public function encryptedObject(): Factory
    {
        return $this->state(function () {
            return [
                'type' => 'encrypted:object',
            ];
        });
    }
}
