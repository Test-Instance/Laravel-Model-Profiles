<?php

namespace TestInstance\LaravelModelProfiles\Traits;

use TestInstance\LaravelModelProfiles\Exceptions\InvalidProfileKeyException;
use Carbon\Carbon;
use Closure;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;

/**
 * @mixin Model
 */
trait HasProfile
{

    /**
     * @var array
     */
    private static array $profileKeys = [];

    /**
     * Injects profile into model's with property to eager load it
     */
    public function initializeHasProfile(): void
    {
        array_push($this->with, 'profile');
    }

    /**
     * @throws Exception
     *
     * @return void
     */
    public static function loadProfileKeys(): void
    {
        if (!App::runningUnitTests()) {
            throw new Exception('loadProfileKeys is for testing only.');
        }

        static::$profileKeys[static::class] = (static::getProfileKeyClass())::get();
    }

    /**
     * @return Collection
     */
    public static function profileKeys(): Collection
    {
        if (!isset(static::$profileKeys[static::class])) {
            static::$profileKeys[static::class] = (static::getProfileKeyClass())::get();
        }

        return static::$profileKeys[static::class];
    }

    /**
     * @return string
     */
    public static function getProfileClass(): string
    {
        return static::class . 'Profile';
    }

    /**
     * @return string
     */
    public static function getProfileKeyClass(): string
    {
        return static::getProfileClass() . 'Key';
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    // @codeCoverageIgnoreStart
    public static function up(): void
    {
        $model = new static();
        $profileClass = static::getProfileClass();
        $profileModel = new $profileClass();
        $profileKeyClass = static::getProfileKeyClass();
        $profileKeyModel = new $profileKeyClass();

        Schema::create($profileKeyModel->getTable(), function (Blueprint $table) {
            $table->id();
            $table->string('name')
                ->unique()
                ->index();
            $table->string('type');
            $table->timestamps();
        });

        Schema::create($profileModel->getTable(), function (Blueprint $table) use (
            $profileModel,
            $model,
            $profileKeyModel
        ) {
            $table->id();
            $table->foreignId($model->getForeignKey());
            $table->foreignId($profileKeyModel->getForeignKey());
            $table->string('value');
            $table->timestamps();
            $table->softDeletes();
            $table->boolean('deleted_at_unique')
                ->virtualAs('CASE WHEN deleted_at IS NULL THEN 1 ELSE null END');

            $table->unique([
                'deleted_at_unique',
                $model->getForeignKey(),
                $profileKeyModel->getForeignKey()
            ], $profileModel->getTable() . '_deleted_unique');

            $table->foreign($model->getForeignKey(), $model->getForeignKey() . '_foreign')
                ->references($model->getKeyName())
                ->on($model->getTable());
            $table->foreign($profileKeyModel->getForeignKey(), $profileKeyModel->getForeignKey() . '_foreign')
                ->references($profileKeyModel->getKeyName())
                ->on($profileKeyModel->getTable());
        });
    }
    // @codeCoverageIgnoreEnd

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    // @codeCoverageIgnoreStart
    public static function down(): void
    {
        $profileClass = static::getProfileClass();
        $profileKeyClass = static::getProfileKeyClass();
        Schema::dropIfExists((new $profileClass())->getTable());
        Schema::dropIfExists((new $profileKeyClass())->getTable());
    }
    // @codeCoverageIgnoreEnd

    /**
     * @return HasMany
     */
    public function profile(): HasMany
    {
        return $this->hasMany(static::getProfileClass())->with('profileKey');
    }

    /**
     * @param string $profileKeyName
     * @return mixed
     * @throws InvalidProfileKeyException
     */
    public function getProfileValue(string $profileKeyName): mixed
    {
        $profileKey = static::profileKeys()->firstWhere('name', $profileKeyName);
        if (is_null($profileKey)) {
            throw new InvalidProfileKeyException($profileKeyName . ' on ' . static::class);
        }

        $profile = $this->profile->firstWhere('profileKey.name', $profileKeyName);
        if (is_null($profile)) {
            return null;
        }

        return $profile->value;
    }

    /**
     * @param string $profileKeyName
     * @param mixed $value
     * @return void
     * @throws InvalidProfileKeyException
     */
    public function setProfileValue(string $profileKeyName, mixed $value): void
    {
        if (is_null($value)) {
            $this->deleteProfileValue($profileKeyName);
            return;
        }

        if (is_a($value, Model::class)) {
            $value = $value->{$value->getKeyName()};
        }

        $profile = $this->profile->firstWhere('profileKey.name', $profileKeyName);
        $oldProfileKey = null;

        if (is_null($profile)) {
            $profileKey = static::profileKeys()->firstWhere('name', $profileKeyName);
            if (is_null($profileKey)) {
                throw new InvalidProfileKeyException($profileKeyName . ' on ' . static::class);
            }

            $profileClass = static::getProfileClass();
            $profileKeyClass = static::getProfileKeyClass();
            $profileKeyModel = new $profileKeyClass();

            $profile = new $profileClass();
            $profile->{$this->getForeignKey()} = $this->id;
            $profile->{$profileKeyModel->getForeignKey()} = $profileKey->id;
        } else {
            if ($profile->getRawAttribute('value') == $value) {
                return;
            }

            $oldProfileKey = $this->profile->search(function ($item) use ($profileKeyName) {
                return $item->profileKey->name == $profileKeyName;
            });

            $newProfile = $profile->replicate(['deleted_at_unique']);
            $profile->delete();
            $profile = $newProfile;
        }

        $profile->value = $value;
        $profile->save();

        $fillable = $this->fillable;
        $this->fillable[] = 'updated_at';
        $this->update(['updated_at' => Carbon::now()]);
        $this->fillable = $fillable;

        if (!is_null($oldProfileKey)) {
            $this->profile->get($oldProfileKey)->delete();
            $this->profile->forget($oldProfileKey);
        }

        $this->profile->push($profile);
    }

    /**
     * @param string $profileKeyName
     * @return void
     * @throws InvalidProfileKeyException
     */
    public function deleteProfileValue(string $profileKeyName): void
    {
        $profileKey = static::profileKeys()->firstWhere('name', $profileKeyName);
        if (is_null($profileKey)) {
            throw new InvalidProfileKeyException($profileKeyName . ' on ' . static::class);
        }

        $key = $this->profile->search(function ($profile) use ($profileKey) {
            return $profile->profileKey->is($profileKey);
        });

        if ($key === false) {
            return;
        }

        $this->profile->get($key)->delete();
        $this->profile->forget($key);
    }

    /**
     * Dynamically set attributes on the model.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     * @throws Exception|InvalidProfileKeyException
     */
    public function __set($key, $value)
    {
        if (static::profileKeys()->contains('name', $key)) {
            $this->setProfileValue($key, $value);
            return;
        }

        $this->setAttribute($key, $value);
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param string $key
     * @return mixed
     * @throws InvalidProfileKeyException
     */
    public function __get($key): mixed
    {
        if (static::profileKeys()->contains('name', $key)) {
            return $this->getProfileValue($key);
        }

        return $this->getAttribute($key);
    }


    /**
     * Determine if an attribute or relation exists on the model.
     *
     * @param string $key
     * @return bool
     * @throws InvalidProfileKeyException
     */
    public function __isset($key): bool
    {
        if (static::profileKeys()->contains('name', $key)) {
            return $this->getProfileValue($key) !== null;
        }

        return $this->offsetExists($key);
    }

    /**
     * Unset an attribute on the model.
     *
     * @param string $key
     * @return void
     * @throws InvalidProfileKeyException
     */
    public function __unset($key): void
    {
        if (static::profileKeys()->contains('name', $key)) {
            $this->deleteProfileValue($key);
            return;
        }

        $this->offsetUnset($key);
    }

    /**
     * @param Builder $query
     * @param Closure|string|array $column
     * @param mixed|null $operator
     * @param mixed|null $value
     * @param string $boolean
     * @return Builder
     * @throws InvalidProfileKeyException
     */
    public function scopeWhereProfile(
        Builder $query,
        Closure|string|array $column,
        mixed $operator = null,
        mixed $value = null,
        string $boolean = 'and'
    ): Builder
    {
        $profileKey = static::profileKeys()->firstWhere('name', $column);
        if (is_null($profileKey)) {
            throw new InvalidProfileKeyException($column . ' on ' . static::class);
        }

        return $query->whereHas('profile', function (Builder $query) use (
            $profileKey,
            $operator,
            $value,
            $boolean
        ) {
            $query->where($profileKey->getForeignKey(), $profileKey->id)
                ->where('value', $operator, $value, $boolean);
        });
    }

    /**
     * @param Builder $query
     * @param string $column
     * @param mixed $values
     * @param string $boolean
     * @param bool $not
     * @return Builder
     * @throws InvalidProfileKeyException
     */
    public function scopeWhereProfileIn(
        Builder $query,
        string $column,
        mixed $values,
        string $boolean = 'and',
        bool $not = false
    ): Builder
    {
        $profileKey = static::profileKeys()->firstWhere('name', $column);
        if (is_null($profileKey)) {
            throw new InvalidProfileKeyException($column . ' on ' . static::class);
        }

        return $query->whereHas('profile', function (Builder $query) use (
            $profileKey,
            $values,
            $boolean,
            $not
        ) {
            $query->where($profileKey->getForeignKey(), $profileKey->id)
                ->whereIn('value', $values, $boolean, $not);
        });
    }
}
