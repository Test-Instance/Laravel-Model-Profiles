<p align="center"><a href="https://testinstance.co.uk" target="_blank"><img src="https://raw.githubusercontent.com/Test-Instance/Laravel-Model-Profiles/master/.github/images/testinstancebanner.png" width="200"></a></p>

<p align="center">
<a href="https://packagist.org/packages/testinstance/laravel-model-profiles"><img src="https://poser.pugx.org/testinstance/laravel-model-profiles/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/testinstance/laravel-model-profiles"><img src="https://poser.pugx.org/testinstance/laravel-model-profiles/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/testinstance/laravel-model-profiles"><img src="https://poser.pugx.org/testinstance/laravel-model-profiles/license.svg" alt="License"></a>
</p>

## Laravel Model Profiles

<p>
Laravel Model Profiles allows the extension of conventional normalization forms where scaling is seen to be completed via rows representing value with data types and relational keys rather than additional tables and key based relations.

Note - ONLY COMPATIBLE WITH <a href="https://packagist.org/packages/laravel/framework">LARAVEL FRAMEWORK</a>
</p>

<hr/>

## Setup

- (version 1) 
Your models must live within `App/Models`

- (version 2 onward) [WIP]
Set your model path and migration path within the `app/config/profiles.php` file. These tell Laravel Model Profiles where to inject new profile models & migrations via creation.
Default is `App/Models` & `database/migrations`

<hr/>

## Usage

### Installation

`composer require testinstance/laravel-model-profiles`

> We will use 'User.php' model as an example.

### Make Model Profile

In order to use Laravel Model Profiles you must use the 'use HasProfile' trait on your selected model class. 'use TestInstance\LaravelModelProfiles\Traits\HasProfile;'.

Run `php artisan make:profile ModelScope\ModelClassName`
- example: `php artisan make:profile User\User`. This will create a profile for the User.php Model.

You will see that two new models and a migration file are created:
- `App/Models/User/UserProfile.php`
- `App/Models/User/UserProfileKey.php`
- `database/migrations/{timestamp}_user_profile.php`

Run `php artisan migrate`

You will see that the `user_profiles` table is created with:
- id
- user_id
- user_profile_key_id
- value
- created_at
- updated_at
- deleted_at
- deleted_at_unique

You will see that the `user_profile_keys` table is created with:
- id
- name
- type
- created_at
- updated_at

### Interactions

#### Retrieve by profile value

````
//'color' is the name of the profileKey related to the model - 'user_profile_keys.name'
//'blue' is the value of the profile related to the model - 'user_profiles.value'
$users = User::whereProfile('color', 'blue')->get();
```` 

`$users` will be a collection of `users` which have the profile value of `blue` for the `profile_key` name of `color`

`whereProfile` & `whereProfileIn` are a direct pass-through to the native Laravel `where` & `whereIn` so can be used as such. (Including searching for multiple parameters as an array: `whereProfile(['color' => 'blue', 'anotherProfileKey' => 'anotherProfileValue'])`).

The profileKey type will dictate which data type it relates to and will be casted as such. The available types are listed in the Laravel documentation <a href="https://laravel.com/docs/8.x/eloquent-mutators#attribute-casting">here</a>. Model casting in profile is an extension and will be documented after its full release. 

#### Save profile value

To save a profile value you can simple use the following format:
`````
$user->color = 'blue';
$user->save();
`````

### Eagerloading

Every model with a profile will eagerload its profile. Profile attributes can be accessed the same way model attributes are.
- example: `$user->color` will return `blue` even if the attribute 'color' does not exist as a column on user.

### Notes

- [x] Mass Assignment
> Updating a model with profile attributes loaded will not be affected by profile.

- [ ] Model Casting
> Casting model profile type to another model is [WIP]

- [ ] Unit Tests
> Unit tests exist for profile, however model specific tests do not. These are [WIP]. These can be created manually in the current version

<hr/>

## Dependencies

- Laravel Framework: "^8.75" (Recommended)
- PHP:               "^8.0"

<hr/>

## Security

Please notify us of any security issues or concerns via [developers@testinstance.co.uk](mailto:developers@testinstance.co.uk)

<hr/>

## License

Laravel Model Profiles is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Authors

<a href="https://github.com/Ballard-dev">Ballard-dev</a>
<a href="https://github.com/KieranFYi">KieranFYI</a>
