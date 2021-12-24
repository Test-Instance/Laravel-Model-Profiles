<?php

namespace TestInstance\LaravelModelProfiles;

use TestInstance\LaravelModelProfiles\Console\Commands\MakeModelProfile;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Support\ServiceProvider;

class ProfileServiceProvider extends ServiceProvider
{

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfig();
    }

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

    private function mergeConfig(): void
    {
        $this->mergeConfigFrom($this->getConfigPath(), 'profiles');
    }

    /**
     *  Register the profile config
     *
     * @return void
     */
    private function registerConfig(): void
    {
        $this->publishes([
            $this->getConfigPath() => config_path('profiles'),
        ]);
    }

    /**
     * Register the commands
     *
     * @return void
     */
    private function registerCommands(): void
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
    private function registerProfileCreator(): void
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

    private function getConfigPath()
    {
        return __DIR__ . '/../config/profiles.php';
    }
}
