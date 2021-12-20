<?php

namespace TestInstance\LaravelModelProfiles\Database\Migrations;

use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Support\Str;
use ReflectionClass;

class ProfileFileHandler extends MigrationCreator
{

    /**
     * @param $modelPath
     */
    public function makeFiles($modelPath): void
    {
        $this->createProfileMigration($modelPath, ...$this->buildMigrationParameters($modelPath));

        $reflection = new ReflectionClass($modelPath);
        $path = $reflection->getNamespaceName();

        $this->createProfileModel($modelPath, $path, $path);
        $this->createProfileKeyModel($modelPath, $path, $path);
    }

    /**
     * @param $modelPath
     * @param $name
     * @param $model
     * @param $table
     * @param $path
     *
     * @return string
     */
    public function createProfileMigration($modelPath, $name, $model, $table, $path): string
    {
        $this->ensureMigrationDoesntAlreadyExist($name, $path);

        $stub = file_get_contents($this->customStubPath . '/migration.create.stub');

        $path = $this->getPath($name, $path);

        $this->files->ensureDirectoryExists(dirname($path));

        $this->files->put(
            $path, $this->populateMigrationStub($modelPath, $name, $model, $stub)
        );

        $this->firePostCreateHooks($table);

        return $path;
    }

    /**
     * @param $modelPath
     *
     * @return array
     */
    private function buildMigrationParameters($modelPath): array
    {
        $model = basename($modelPath);
        $name = strtolower($model) . '_profile';

        return [
            'name' => $name,
            'path' => config('profiles.migration_path') ?? 'database/migrations',
            'model' => $model,
            'table' => $name . 's'
        ];
    }

    /**
     * @param $modelPath
     * @param $name
     * @param $model
     * @param $stub
     *
     * @return string
     */
    private function populateMigrationStub($modelPath, $name, $model, $stub): string
    {
        return str_replace([
            '{{model}}',
            '{{modelPath}}',
            '{{table}}'
        ], [
            $model,
            $modelPath,
            Str::studly($name)
        ], $stub);
    }

    /**
     * @param $modelPath
     * @param $path
     *
     * @return void
     */
    private function createProfileKeyModel($modelPath, $namespace, $path): void
    {
        $name = basename($modelPath) . 'ProfileKey';

        $this->createModel($path, $name, $namespace, 'profile_key');
    }

    /**
     * @param $modelPath
     * @param $namespace
     *
     * @return void
     */
    private function createProfileModel($modelPath, $namespace, $path): void
    {
        $name = basename($modelPath) . 'Profile';

        $this->createModel($path, $name, $namespace,'profile');
    }

    /**
     * @param $path
     * @param $name
     * @param $stub
     *
     * @return void
     */
    private function createModel($path, $name, $namespace, $stub): void
    {
        $stub = file_get_contents($this->customStubPath . "/$stub.create.stub");

        $path = $this->getModelPath($name, $path);

        $this->files->ensureDirectoryExists(dirname($path));

        $this->files->put(
            $path, $this->populateModelStub($name, $namespace, $stub)
        );
    }

    /**
     * @param $name
     * @param $path
     *
     * @return string
     */
    protected function getModelPath($name, $path): string
    {
        return $path.'/'.$name.'.php';
    }

    /**
     * @param $name
     * @param $path
     * @param $stub
     *
     * @return string
     */
    private function populateModelStub($name, $path, $stub): string
    {
        return str_replace([
            '{{model}}',
            '{{namespace}}',
        ], [
            $name,
            $path,
        ], $stub);
    }
}
