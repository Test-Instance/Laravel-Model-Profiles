<?php

namespace TestInstance\LaravelModelProfiles\Traits;

use Illuminate\Database\Eloquent\Model;
use TestInstance\LaravelModelProfiles\Database\Factories\ProfileKeyFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @mixin Model
 */
trait ProfileKeyTrait
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory
     */
    public static function newFactory(): Factory
    {
        return ProfileKeyFactory::new()->setModel(static::class);
    }

    /**
     *  Append to the model fillable property.
     *
     * @return void
     */
    public function initializeProfileKeyTrait(): void
    {
        $this->fillable = array_merge($this->fillable, ['name', 'type']);
    }
}
