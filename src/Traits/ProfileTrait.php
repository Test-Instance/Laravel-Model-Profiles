<?php

namespace TestInstance\LaravelModelProfiles\Traits;

use TestInstance\LaravelModelProfiles\Database\Factories\ProfileFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin Model
 */
trait ProfileTrait
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory
     */
    public static function newFactory(): Factory
    {
        return ProfileFactory::new()->setModel(static::class);
    }

    /**
     * Get profile key class
     *
     * @return string
     */
    public static function getProfileKeyClass(): string
    {
        return static::class . 'Key';
    }

    /**
     * Boot profile trait on model.
     *
     * @return void
     */
    public static function bootProfileTrait(): void
    {
        static::retrieved(function ($profile) {
            $profile->casts = array_merge($profile->casts, ['value' => $profile->profileKey->type]);
            $type = $profile->profileKey->type;
            if (!str_starts_with($type, 'model')) {
                $profile->casts = array_merge($profile->casts, ['value' => $type]);
            } else {
                array_push($profile->with, 'model');
            }
        });

        static::saved(function ($profile) {
            $profile->casts = array_merge($profile->casts, ['value' => $profile->profileKey->type]);
            $type = $profile->profileKey->type;
            if (!str_starts_with($type, 'model')) {
                $profile->casts = array_merge($profile->casts, ['value' => $type]);
            } else {
                array_push($profile->with, 'model');
            }
        });
    }

    /**
     * Initialize profile trait.
     *
     * @return void
     */
    public function initializeProfileTrait(): void
    {
        array_push($this->with, 'profileKey');
    }

    /**
     * Return BelongsTo profile relation
     *
     * @return BelongsTo
     */
    public function profileKey(): BelongsTo
    {
        $class = static::getProfileKeyClass();
        $classInstance = new $class();

        return $this->belongsTo($class, $classInstance->getForeignKey());
    }

    /**
     * Return related model.
     *
     * @return HasOne
     */
    public function model(): HasOne
    {
        $key = array_search($this->profileKey->type, $this->casts);
        unset($this->casts[$key]);

        $datum = explode(':', $this->profileKey->type);
        $class = new $datum[1];
        return $this->hasOne($class, 'id', 'value');
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key): mixed
    {
        if ($key === 'value' && str_starts_with($this->profileKey->type, 'model')) {
            return $this->model;
        }

        return $this->getAttribute($key);
    }
}
