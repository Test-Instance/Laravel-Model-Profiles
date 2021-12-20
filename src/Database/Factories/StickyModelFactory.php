<?php

namespace TestInstance\LaravelModelProfiles\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

abstract class StickyModelFactory extends Factory
{
    /**
     * Create a new instance of the factory builder with the given mutated properties.
     *
     * @param  array  $arguments
     * @return StickyModelFactory
     */
    protected function newInstance(array $arguments = []): StickyModelFactory
    {
        $instance = new static(...array_values(array_merge([
            'count' => $this->count,
            'states' => $this->states,
            'has' => $this->has,
            'for' => $this->for,
            'afterMaking' => $this->afterMaking,
            'afterCreating' => $this->afterCreating,
            'connection' => $this->connection,
        ], $arguments)));

        if ($this->model !== null) {
            $instance->setModel($this->model);
        }

        return $instance;
    }

    /**
     * @param string $model
     * @return StickyModelFactory
     */
    public function setModel(string $model): StickyModelFactory
    {
        $this->model = $model;
        return $this;
    }
}
