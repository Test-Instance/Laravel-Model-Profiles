<?php

namespace TestInstance\LaravelModelProfiles\Test;

use TestInstance\LaravelModelProfiles\Exceptions\InvalidProfileKeyException;
use TestInstance\LaravelModelProfiles\Traits\ProfileKeyTrait;
use TestInstance\LaravelModelProfiles\Traits\ProfileTrait;
use TestInstance\LaravelModelProfiles\Traits\HasProfile;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\App;
use ReflectionException;
use ReflectionClass;
use Tests\TestCase;
use Exception;

abstract class AbstractProfileTraitTest extends TestCase
{

    use WithFaker;

    /**
     * Setup the test environment.
     *
     * @return void
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    public abstract function getModel(): string;

    public function test_model_uses_has_profile_trait()
    {
        $this->assertContains(HasProfile::class, array_keys(class_uses($this->getModel())));
    }

    /**
     * @depends test_model_uses_has_profile_trait
     */
    public function test_model_does_not_use_profile_trait()
    {
        $this->assertNotContains(ProfileTrait::class, array_keys(class_uses($this->getModel())));
    }

    /**
     * @depends test_model_uses_has_profile_trait
     */
    public function test_model_does_not_use_profile_key_trait()
    {
        $this->assertNotContains(ProfileKeyTrait::class, array_keys(class_uses($this->getModel())));
    }

    /**
     * @depends test_model_uses_has_profile_trait
     */
    public function test_model_profile_uses_profile_trait()
    {
        $this->assertContains(ProfileTrait::class, array_keys(class_uses($this->getModel()::getProfileClass())));
    }

    /**
     * @depends test_model_profile_uses_profile_trait
     */
    public function test_model_profile_does_not_use_has_profile_trait()
    {
        $this->assertNotContains(HasProfile::class, array_keys(class_uses($this->getModel()::getProfileClass())));
    }

    /**
     * @depends test_model_profile_uses_profile_trait
     */
    public function test_model_profile_does_not_use_has_profile_key_trait()
    {
        $this->assertNotContains(ProfileKeyTrait::class, array_keys(class_uses($this->getModel()::getProfileClass())));
    }

    /**
     * @depends test_model_profile_uses_profile_trait
     */
    public function test_model_profile_key_uses_profile_key_trait()
    {
        $this->assertContains(ProfileKeyTrait::class, array_keys(class_uses($this->getModel()::getProfileKeyClass())));
    }

    /**
     * @depends test_model_profile_key_uses_profile_key_trait
     */
    public function test_model_profile_key_does_not_has_profile_trait()
    {
        $this->assertNotContains(HasProfile::class, array_keys(class_uses($this->getModel()::getProfileKeyClass())));
    }

    /**
     * @depends test_model_profile_key_uses_profile_key_trait
     */
    public function test_model_profile_key_does_not_use_profile_trait()
    {
        $this->assertNotContains(ProfileTrait::class, array_keys(class_uses($this->getModel()::getProfileKeyClass())));
    }

    /**
     * Ensure 'profile' is injected into $with attribute
     * @throws ReflectionException
     * @depends test_model_uses_has_profile_trait
     */
    public function test_with_contains_profile()
    {
        $class = new ReflectionClass($this->getModel());
        $method = $class->getProperty('with');
        $method->setAccessible(true);

        $modelClass = $this->getModel();
        $model = new $modelClass;

        $this->assertContains('profile', $method->getValue($model));
    }

    /**
     * Ensure 'profile' is injected into $with attribute
     * @depends test_model_profile_uses_profile_trait
     */
    public function test_profile_relationship_is_loaded()
    {
        $model = $this->getModel()::factory()
            ->has(($this->getModel()::getProfileClass())::factory()->count(10), 'profile')
            ->create()
            ->fresh();

        $this->assertTrue($model->relationLoaded('profile'));
        $this->assertNotEmpty($model->profile);
    }

    /**
     * @depends test_model_profile_uses_profile_trait
     */
    public function test_profile_profileKey_is_loaded()
    {
        $model = $this->getModel()::factory()
            ->has(($this->getModel()::getProfileClass())::factory()->count(10), 'profile')
            ->create()
            ->fresh();

        $profile = $model->profile()->first();

        $this->assertTrue($profile->relationLoaded('profileKey'));
        $this->assertNotNull($profile->profileKey);
    }

    /**
     * @depends test_model_profile_key_uses_profile_key_trait
     */
    public function test_profile_profileKey_contains_cast()
    {
        $profilable = $this->getModel()::factory()
            ->has(($this->getModel()::getProfileClass())::factory(), 'profile')
            ->create();

        $profile = $profilable->profile()->first();
        $castables = $profile->getCasts();

        $this->assertArrayHasKey('value', $castables);
        $this->assertEquals($profile->profileKey->type, $castables['value']);
    }

    /**
     * @depends test_model_profile_uses_profile_trait
     */
    public function test_load_profile_keys_method_only_testing()
    {
        App::shouldReceive('runningUnitTests')
            ->once()
            ->andReturn(false);

        $this->expectException(Exception::class);
        $this->getModel()::loadProfileKeys();

    }

    /**
     * @depends test_get_profile_value_method_invalid_key
     */
    public function test_isset_profile_value_false()
    {
        $model = $this->getModel()::factory()
            ->create()
            ->fresh();

        $profileKey = ($this->getModel()::getProfileClass())::factory()
            ->make()
            ->profileKey;

        $this->assertFalse(isset($model->{$profileKey->name}));
    }

    /**
     * @depends test_get_profile_value_method_invalid_key
     */
    public function test_isset_attribute_value()
    {
        $model = $this->getModel()::factory()
            ->create()
            ->fresh();

        $this->assertTrue(isset($model->{$model->getKeyName()}));
    }

    /**
     * @depends test_isset_attribute_value
     */
    public function test_isset_profile_value_true()
    {
        $model = $this->getModel()::factory()
            ->has(($this->getModel()::getProfileClass())::factory(), 'profile')
            ->create()
            ->fresh();
        $this->getModel()::loadProfileKeys();

        $profileKey = $model->profile()->first()->profileKey;

        $this->assertTrue(isset($model->{$profileKey->name}));
    }

    /**
     * @depends test_isset_profile_value_true
     */
    public function test_delete_profile_value()
    {
        $model = $this->getModel()::factory()
            ->has(($this->getModel()::getProfileClass())::factory(), 'profile')
            ->create()
            ->fresh();
        $this->getModel()::loadProfileKeys();

        $profileKey = $model->profile()->first()->profileKey;

        $this->assertTrue(isset($model->{$profileKey->name}));
        $model->deleteProfileValue($profileKey->name);
        $this->assertFalse(isset($model->{$profileKey->name}));
    }

    /**
     * @depends test_delete_profile_value
     */
    public function test_delete_profile_value_null()
    {
        $model = $this->getModel()::factory()
            ->create()
            ->fresh();

        $profileKey = ($this->getModel()::getProfileKeyClass())::factory()
            ->create();

        $this->getModel()::loadProfileKeys();

        $model->deleteProfileValue($profileKey->name);
        $this->assertFalse(isset($model->{$profileKey->name}));
    }

    /**
     * @depends test_isset_profile_value_true
     */
    public function test_delete_profile_value_invalid_key()
    {
        $model = $this->getModel()::factory()
            ->create()
            ->fresh();

        $this->expectException(InvalidProfileKeyException::class);
        $model->deleteProfileValue('iDontExist');
    }

    /**
     * @depends test_delete_profile_value
     */
    public function test_unset_profile_value()
    {
        $model = $this->getModel()::factory()
            ->has(($this->getModel()::getProfileClass())::factory(), 'profile')
            ->create()
            ->fresh();
        $this->getModel()::loadProfileKeys();

        $profileKey = $model->profile()->first()->profileKey;

        $this->assertTrue(isset($model->{$profileKey->name}));
        unset($model->{$profileKey->name});
        $this->assertFalse(isset($model->{$profileKey->name}));
    }

    /**
     * @depends test_delete_profile_value
     */
    public function test_unset_attribute_value()
    {
        $model = $this->getModel()::factory()
            ->has(($this->getModel()::getProfileClass())::factory(), 'profile')
            ->create()
            ->fresh();
        $this->getModel()::loadProfileKeys();

        $this->assertTrue(isset($model->{$model->getKeyName()}));
        unset($model->{$model->getKeyName()});
        $this->assertFalse(isset($model->{$model->getKeyName()}));
    }

    /**
     * @depends test_isset_profile_value_true
     */
    public function test_set_profile_value_method()
    {
        $profileKeyClass = $this->getModel()::getProfileKeyClass();
        $profileKey = $profileKeyClass::factory()->create();
        $this->getModel()::loadProfileKeys();

        $model = $this->getModel()::factory()
            ->create()
            ->fresh();

        $this->assertFalse(isset($model->{$profileKey->name}));
        $model->setProfileValue($profileKey->name, $this->faker->lexify(str_repeat('?', random_int(10, 40))));
        $this->assertTrue(isset($model->{$profileKey->name}));
    }

    /**
     * @depends test_set_profile_value_method
     */
    public function test_set_profile_value_method_invalid_key()
    {
        $model = $this->getModel()::factory()
            ->create()
            ->fresh();

        $this->expectException(InvalidProfileKeyException::class);
        $model->setProfileValue('iDontExist', $this->faker->lexify(str_repeat('?', random_int(10, 40))));
    }

    /**
     * @depends test_set_profile_value_method
     */
    public function test_set_profile_value_method_history()
    {
        $model = $this->getModel()::factory()
            ->has(($this->getModel()::getProfileClass())::factory()->count(10), 'profile')
            ->create()
            ->fresh();
        $this->getModel()::loadProfileKeys();

        $profile = $model->profile->first();

        $model->setProfileValue($profile->profileKey->name, $this->faker->lexify(str_repeat('?', random_int(10, 40))));

        $this->assertNotEquals($model->getProfileValue($profile->profileKey->name), $profile->value);
    }

    /**
     * @depends test_set_profile_value_method
     */
    public function test_set_profile_value_method_history_duplicate_value()
    {
        $model = $this->getModel()::factory()
            ->has(($this->getModel()::getProfileClass())::factory()->count(10), 'profile')
            ->create()
            ->fresh();
        $this->getModel()::loadProfileKeys();

        $profile = $model->profile->first();

        $model->setProfileValue($profile->profileKey->name, $profile->value);

        $this->assertEquals($model->getProfileValue($profile->profileKey->name), $profile->value);
    }

    /**
     * @depends test_set_profile_value_method
     */
    public function test_set_profile_value_method_history_duplicate_value_after_change()
    {
        $model = $this->getModel()::factory()
            ->has(($this->getModel()::getProfileClass())::factory()->count(10), 'profile')
            ->create()
            ->fresh();
        $this->getModel()::loadProfileKeys();

        $profile = $model->profile->first();

        $model->setProfileValue($profile->profileKey->name, $this->faker->lexify(str_repeat('?', random_int(10, 40))));
        $model->setProfileValue($profile->profileKey->name, $profile->value);

        $this->assertEquals($model->getProfileValue($profile->profileKey->name), $profile->value);
    }

    /**
     * @depends test_isset_profile_value_true
     */
    public function test_set_profile_value_magic()
    {
        $profileKeyClass = $this->getModel()::getProfileKeyClass();
        $profileKey = $profileKeyClass::factory()->create();
        $this->getModel()::loadProfileKeys();

        $model = $this->getModel()::factory()
            ->create()
            ->fresh();

        $this->assertFalse(isset($model->{$profileKey->name}));
        $model->{$profileKey->name} = $this->faker->lexify(str_repeat('?', random_int(10, 40)));
        $this->assertTrue(isset($model->{$profileKey->name}));
    }

    /**
     * @depends test_isset_profile_value_true
     */
    public function test_set_attribute_value()
    {
        $profileKeyClass = $this->getModel()::getProfileKeyClass();
        $profileKey = $profileKeyClass::factory()->create();
        $this->getModel()::loadProfileKeys();

        $model = $this->getModel()::factory()
            ->create()
            ->fresh();

        $oldValue = $model->{$model->getKeyName()};

        $model->{$model->getKeyName()} = $oldValue + 1;
        $this->assertNotEquals($oldValue, $model->{$model->getKeyName()});
    }

    /**
     * @depends test_isset_profile_value_true
     */
    public function test_set_profile_value_null()
    {
        $model = $this->getModel()::factory()
            ->has(($this->getModel()::getProfileClass())::factory()->count(10), 'profile')
            ->create()
            ->fresh();
        $this->getModel()::loadProfileKeys();

        $profileKey = $model->profile->first()->profileKey;

        $this->assertTrue(isset($model->{$profileKey->name}));
        $model->setProfileValue($profileKey->name, null);
        $this->assertFalse(isset($model->{$profileKey->name}));
    }

    /**
     * @depends test_model_profile_uses_profile_trait
     */
    public function test_get_profile_value_method()
    {
        $model = $this->getModel()::factory()
            ->has(($this->getModel()::getProfileClass())::factory()->count(10), 'profile')
            ->create()
            ->fresh();
        $this->getModel()::loadProfileKeys();

        $profile = $model->profile->first();

        $this->assertEquals($model->getProfileValue($profile->profileKey->name), $profile->value);
    }

    /**
     * @depends test_get_profile_value_method
     */
    public function test_get_profile_value_method_invalid_key()
    {
        $model = $this->getModel()::factory()
            ->create()
            ->fresh();

        $this->expectException(InvalidProfileKeyException::class);
        $model->getProfileValue('iDontExist');
    }

    /**
     * @depends test_get_profile_value_method
     */
    public function test_get_profile_value_method_unset_key()
    {
        $profileKeyClass = $this->getModel()::getProfileKeyClass();
        $profileKey = $profileKeyClass::factory()->create();
        $this->getModel()::loadProfileKeys();

        $model = $this->getModel()::factory()
            ->create()
            ->fresh();

        $this->assertNull($model->getProfileValue($profileKey->name));
    }

    /**
     * @depends test_isset_profile_value_true
     */
    public function test_get_profile_value_method_magic()
    {
        $model = $this->getModel()::factory()
            ->has(($this->getModel()::getProfileClass())::factory()->count(10), 'profile')
            ->create()
            ->fresh();
        $this->getModel()::loadProfileKeys();

        $profile = $model->profile->first();

        $this->assertEquals($model->{$profile->profileKey->name}, $profile->value);
    }

    /**
     * @depends test_get_profile_value_method
     */
    public function test_get_attribute_value()
    {
        $model = $this->getModel()::factory()
            ->has(($this->getModel()::getProfileClass())::factory()->count(10), 'profile')
            ->create()
            ->fresh();
        $this->getModel()::loadProfileKeys();

        $this->assertNotNull($model->{$model->getKeyName()});
    }

    public function test_where()
    {
        $baseModel = $this->getModel()::factory()
            ->create()
            ->fresh();

        $profile = ($this->getModel()::getProfileClass())::factory()
            ->create([
                $baseModel->getForeignKey() => $baseModel->id
            ]);

        $this->getModel()::loadProfileKeys();

        $model = $this->getModel()::whereProfile($profile->profileKey->name, $profile->value)->first();

        $this->assertEquals($baseModel->id, $model->id);
    }

    public function test_where_invalid_key()
    {
        $this->expectException(InvalidProfileKeyException::class);

        $this->getModel()::whereProfile(
            'iDontExist', $this->faker->lexify(str_repeat('?', random_int(10, 40)))
        )->first();
    }

    public function test_where_in()
    {
        $baseModel = $this->getModel()::factory()
            ->create()
            ->fresh();

        $profile = ($this->getModel()::getProfileClass())::factory()
            ->create([
                $baseModel->getForeignKey() => $baseModel->id
            ]);

        $this->getModel()::loadProfileKeys();

        $model = $this->getModel()::whereProfileIn($profile->profileKey->name, [$profile->value])->first();

        $this->assertEquals($baseModel->id, $model->id);
    }

    public function test_where_in_invalid_key()
    {
        $this->expectException(InvalidProfileKeyException::class);

        $this->getModel()::whereProfileIn('iDontExist', [
            $this->faker->lexify(str_repeat('?', random_int(10, 40)))
        ])->first();
    }
}
