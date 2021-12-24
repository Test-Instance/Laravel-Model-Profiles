<?php

namespace TestInstance\LaravelModelProfiles\Traits;

use TestInstance\LaravelModelProfiles\Exceptions\InvalidProfileKeyException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Exception;
use Closure;

/**
 * @property Collection $profile
 *
 * @mixin Model
 */
trait HasProfile
{

    /**
     * @var array
     */
    private static array $profileKeys = [];

    public function initializeHasProfile(): void
    {
        array_push($this->with, 'profile');
    }

    /**
     * @throws Exception
     */
    public static function loadProfileKeys(): void
    {
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

            $modelForeignKey = self::convertNameToForeignKey($model->getTable()) . '_'
                . self::convertNameToForeignKey($model->getForeignKey());
            $table->foreign($model->getForeignKey(), $modelForeignKey . '_foreign')
                ->references('id')
                ->on($model->getTable())
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $profileKeyForeignKey = self::convertNameToForeignKey($profileKeyModel->getTable()) . '_'
                . self::convertNameToForeignKey($profileKeyModel->getForeignKey());
            $table->foreign($profileKeyModel->getForeignKey(), $profileKeyForeignKey . '_foreign')
                ->references('id')
                ->on($profileKeyModel->getTable())
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
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
     * @param string $name
     * @return string
     */
    private static function convertNameToForeignKey(string $name): string
    {
        $foreignKey = '';
        $words = explode('_', $name);
        foreach ($words as $w) {
            $foreignKey .= substr($w, 0, 1);
        }

        return $foreignKey;
    }


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
     *
     * @return void
     * @throws InvalidProfileKeyException
     */
    public function setProfileValue(string $profileKeyName, mixed $value): void
    {
        if (is_null($value)) {
            $this->deleteProfileValue($profileKeyName);
            return;
        }

        if (is_object($value) && method_exists($value, 'getKeyName')) {
            $value = $value->{$value->getKeyName()};
        }

        $profile = $this->profile->firstWhere('profileKey.name', $profileKeyName);

        if (is_null($profile)) {
            $profileKey = static::profileKeys()->firstWhere('name', $profileKeyName);
            if (is_null($profileKey)) {
                throw new InvalidProfileKeyException($profileKeyName . ' on ' . static::class);
            }

            $profileClass = static::getProfileClass();
            $profileKeyClass = static::getProfileKeyClass();
            $profileKeyModel = new $profileKeyClass();

            $profileClass::unguard();
            $profile = new $profileClass([
                $this->getForeignKey() => $this->{$this->getKeyName()},
                $profileKeyModel->getForeignKey() => $profileKey->id,
                'value' => $value
            ]);
            $profileClass::reguard();
        } else {
            $newProfile = $profile->replicate(['deleted_at_unique']);
            $newProfile->value = $value;
            if ($profile->value == $newProfile->value) {
                return;
            }
            $profile = $newProfile;

            $profileKey = $this->profile->search(fn($item) => $item->profileKey->name == $profileKeyName);
            if (!is_null($profileKey)) {
                $this->profile->get($profileKey)->delete();
                $this->profile->forget($profileKey);
            }
        }

        $profile->save();

        if ($this->timestamps) {
            $fillable = $this->fillable;
            $this->fillable[] = 'updated_at';
            $this->update(['updated_at' => Carbon::now()]);
            $this->fillable = $fillable;
        }

        $this->profile->push($profile);
    }

    /**
     * @param string $profileKeyName
     * @throws InvalidProfileKeyException
     */
    public function deleteProfileValue(string $profileKeyName): void
    {
        $profileKey = static::profileKeys()->firstWhere('name', $profileKeyName);
        if (is_null($profileKey)) {
            throw new InvalidProfileKeyException($profileKeyName . ' on ' . static::class);
        }

        $key = $this->profile->search(fn($profile) => $profile->profileKey->is($profileKey));

        if ($key === false) {
            return;
        }

        $this->profile->get($key)->delete();
        $this->profile->forget($key);
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param string $key
     * @return mixed
     * @throws InvalidProfileKeyException
     */
    public function __get($key)
    {
        if (static::profileKeys()->contains('name', $key)) {
            return $this->getProfileValue($key);
        }

        return $this->getAttribute($key);
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
    public function __unset($key)
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
        Builder              $query,
        Closure|string|array $column,
        mixed                $operator = null,
        mixed                $value = null,
        string               $boolean = 'and'
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
