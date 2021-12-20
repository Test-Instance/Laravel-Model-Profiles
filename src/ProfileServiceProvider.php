<?php

namespace TestInstance\LaravelModelProfiles;

use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use TestInstance\LaravelModelProfiles\Console\Commands\MakeModelProfile;

class ProfileServiceProvider extends BaseServiceProvider
{

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerConfig();

        $this->registerCommands();

        $this->registerProfileCreator();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        //
    }

    /**
     *  Register the profile config
     *
     * @return void
     */
    protected function registerConfig(): void
    {
        $this->publishes([
            __DIR__.'/../config/profiles.php' => config_path('profiles.php'),
        ], 'config');

        $this->mergeConfigFrom(
            __DIR__.'/../config/profiles.php', 'profiles'
        );
    }

    /**
     * Register the commands
     *
     * @return void
     */
    protected function registerCommands(): void
    {
        $this->commands([
            'make.profile'
        ]);
    }

    /**
     * Register the migration creator
     *
     * @return void
     */
    protected function registerProfileCreator(): void
    {
        $this->app->singleton('profile.creator', function ($app) {
            return new MigrationCreator($app['files'], __DIR__ . '/../stubs');
        });

        $this->app->singleton('make.profile', function ($app) {
            $creator = $app['profile.creator'];
            $composer = $app['composer'];

            return new MakeModelProfile($creator, $composer);
        });
    }
}
