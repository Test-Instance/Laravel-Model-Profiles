<?php

namespace TestInstance\LaravelModelProfiles\Database\Factories;

use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;

class ProfileFactory extends StickyModelFactory
{

    /**
     * @return string
     */
    private function getProfileKeyForeignKey(): string
    {
        $profileKeyClass = $this->model::getProfileKeyClass();
        $model = new $profileKeyClass();
        return $model->getForeignKey();
    }

    /**
     * @return ProfileKeyFactory
     */
    private function getProfileKeyFactory(): ProfileKeyFactory
    {
        $profileKeyClass = $this->model::getProfileKeyClass();
        return $profileKeyClass::factory();
    }

    /**
     * Define the model's default state.
     *
     * @return string[]
     * @throws Exception
     */
    public function definition(): array
    {
        return [
            $this->getProfileKeyForeignKey() => $this->getProfileKeyFactory(),
            'value' => $this->faker->lexify(str_repeat('?', random_int(10, 40))),
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
                $this->getProfileKeyForeignKey() => $this->getProfileKeyFactory()->integer(),
                'value' => $this->faker->randomNumber(5, false)
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
                $this->getProfileKeyForeignKey() => $this->getProfileKeyFactory()->float(),
                'value' => $this->faker->randomFloat()
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
                $this->getProfileKeyForeignKey() => $this->getProfileKeyFactory()->boolean(),
                'value' => $this->faker->boolean
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
                $this->getProfileKeyForeignKey() => $this->getProfileKeyFactory()->object(),
                'value' => $this->randomObject()
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
                $this->getProfileKeyForeignKey() => $this->getProfileKeyFactory()->array(),
                'value' => $this->randomArray()
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
                $this->getProfileKeyForeignKey() => $this->getProfileKeyFactory()->collection(),
                'value' => $this->randomCollection()
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
                $this->getProfileKeyForeignKey() => $this->getProfileKeyFactory()->date(),
                'value' => $this->faker->date
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
                $this->getProfileKeyForeignKey() => $this->getProfileKeyFactory()->datetime(),
                'value' => $this->faker->dateTime
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
                $this->getProfileKeyForeignKey() => $this->getProfileKeyFactory()->timestamp(),
                'value' => $this->faker->unixTime
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
                $this->getProfileKeyForeignKey() => $this->getProfileKeyFactory()->encrypted(),
                'value' => Crypt::encrypt($this->faker->lexify(str_repeat('?', random_int(10, 40))))
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
                $this->getProfileKeyForeignKey() => $this->getProfileKeyFactory()->encryptedArray(),
                'value' => Crypt::encrypt($this->randomArray())
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
                $this->getProfileKeyForeignKey() => $this->getProfileKeyFactory()->encryptedCollection(),
                'value' => Crypt::encrypt($this->randomCollection())
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
                $this->getProfileKeyForeignKey() => $this->getProfileKeyFactory()->encryptedObject(),
                'value' => Crypt::encrypt($this->randomObject())
            ];
        });
    }

    /**
     * Returns an array containing random data
     *
     * @return array
     * @throws Exception
     */
    private function randomArray(): array
    {
        $arr = range(1, 100);
        $arr = array_merge($arr, str_split($this->faker->lexify(str_repeat('?', random_int(10, 40)))));
        shuffle($arr);
        return array_chunk($arr, random_int(5, count($arr)));
    }

    /**
     * Returns a Collection containing random data
     *
     * @return Collection
     */
    private function randomCollection(): Collection
    {
        return collect($this->randomArray());
    }

    /**
     * Returns an object containing random data
     *
     * @return object
     * @throws Exception
     */
    private function randomObject(): object
    {
        return (object) $this->randomArray();
    }
}
