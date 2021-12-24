<?php

namespace TestInstance\LaravelModelProfiles\Profile;

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

        $this->createProfileModel($modelPath, $path);
        $this->createProfileKeyModel($modelPath, $path);

        $this->createProfileTest(...$this->buildTestParameters($modelPath));
    }

    /**
     * @param $modelPath
     * @param $name
     * @param $model
     * @param $table
     * @param $className
     * @param $path
     *
     * @return string
     */
    public function createProfileMigration($modelPath, $name, $model, $table, $className, $path): string
    {
        $this->ensureMigrationDoesntAlreadyExist($name, $path);

        $stub = file_get_contents($this->customStubPath . '/migration.create.stub');

        $path = $this->getPath($name, $path);

        $this->files->ensureDirectoryExists(dirname($path));

        $this->files->put(
            $path, $this->populateMigrationStub($modelPath, $model, $className, $stub)
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
        $name = strtolower(Str::snake($model)) . '_profile';
        $className = $model . 'Profile';

        return [
            'name' => $name,
            'path' => config('profiles.migration_path') ?? 'database/migrations',
            'model' => $model,
            'table' => $name,
            'className' => $className
        ];
    }

    /**
     * @param $modelPath
     * @param $model
     * @param $className
     * @param $stub
     *
     * @return string
     */
    private function populateMigrationStub($modelPath, $model, $className, $stub): string
    {
        return str_replace([
            '{{model}}',
            '{{modelPath}}',
            '{{className}}'
        ], [
            $model,
            $modelPath,
            $className
        ], $stub);
    }

    /**
     * @param $modelPath
     * @param $path
     *
     * @return void
     */
    private function createProfileKeyModel($modelPath, $path): void
    {
        $name = basename($modelPath) . 'ProfileKey';

        $this->createModel($path, $name, 'profile_key');
    }

    /**
     * @param $modelPath
     * @param $namespace
     *
     * @return void
     */
    private function createProfileModel($modelPath, $path): void
    {
        $name = basename($modelPath) . 'Profile';

        $this->createModel($path, $name,'profile');
    }

    /**
     * @param $path
     * @param $name
     * @param $stub
     *
     * @return void
     */
    private function createModel($path, $name, $stub): void
    {
        $stub = file_get_contents($this->customStubPath . "/$stub.create.stub");

        $namespace = $path;

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
            '{{name}}',
            '{{namespace}}',
        ], [
            $name,
            $path,
        ], $stub);
    }

    /**
     * @param $modelPath
     * @param $path
     * @param $model
     * @param $name
     * @param $stub
     *
     * @return void
     */
    protected function createProfileTest($modelPath, $path, $namespace, $model, $name): void
    {
        $stub = file_get_contents($this->customStubPath . "/test.create.stub");

        $path = $this->getModelPath($name, $path);

        $this->files->ensureDirectoryExists(dirname($path));

        $this->files->put(
            $path, $this->populateTestStub($modelPath, $namespace, $model, $name, $stub)
        );
    }

    /**
     * @param $modelPath
     *
     * @return array
     */
    protected function buildTestParameters($modelPath): array
    {
        $model = basename($modelPath);

        return [
            'modelPath' => $modelPath,
            'path' => (config('profiles.test_path') ?? 'tests/Unit/Models') . '/' . $model,
            'namespace' => 'Models\\' . $model,
            'model' => $model,
            'name' => $model . 'ProfileTest',
        ];
    }

    /**
     * @param $name
     * @param $namespace
     * @param $stub
     * @param $modelPath
     * @param $model
     *
     * @return string
     */
    private function populateTestStub($modelPath, $namespace, $model, $name, $stub): string
    {
        return str_replace([
            '{{name}}',
            '{{namespace}}',
            '{{modelPath}}',
            '{{model}}'
        ], [
            $name,
            $namespace,
            $modelPath,
            $model
        ], $stub);
    }
}
