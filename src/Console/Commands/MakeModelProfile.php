<?php

namespace TestInstance\LaravelModelProfiles\Console\Commands;

use Illuminate\Database\Console\Migrations\MigrateMakeCommand;
use Illuminate\Database\Eloquent\Model;
use ReflectionException;
use TestInstance\LaravelModelProfiles\Database\Migrations\ProfileFileHandler;
use ReflectionClass;
use TestInstance\LaravelModelProfiles\Exceptions\InvalidModelException;
use TestInstance\LaravelModelProfiles\Exceptions\MalformedModelException;
use TestInstance\LaravelModelProfiles\Exceptions\ModelNotFoundException;
use TestInstance\LaravelModelProfiles\Traits\HasProfile;

class MakeModelProfile extends MigrateMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:profile {model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make Model Profile Migrations & Models';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct($creator, $composer)
    {
        $this->creator = $creator;
        $this->composer = $composer;

        parent::__construct($creator, $composer);
    }

    /**
     * Execute the console command.
     *
     * @return int
     * @throws ModelNotFoundException
     * @throws InvalidModelException
     * @throws MalformedModelException
     * @throws ReflectionException
     */
    public function handle(): int
    {
        $modelPath = config('profiles.model_path') . $this->argument('model');

        if(!class_exists($modelPath)) {
            throw new ModelNotFoundException("Cannot find $modelPath");
        }

        $model = new $modelPath();
        if(!$model instanceof Model) {
            throw new InvalidModelException("$modelPath is not an instance of Model");
        }

        if(!array_key_exists(HasProfile::class, (new ReflectionClass($modelPath))->getTraits())) {
            throw new MalformedModelException("HasProfile Has not been used in $modelPath");
        }

        if ($this->confirm("Are you sure you would like to create a profile for $modelPath?")) {
            $migrationHandler = new ProfileFileHandler(
                $this->getInaccessibleProperty($this->creator, 'files'),
                $this->getInaccessibleProperty($this->creator, 'customStubPath')
            );

            $migrationHandler->makeFiles($modelPath);

            return 1;
        }

        return 0;
    }

    /**
     * @param object $class
     * @param string $property
     *
     * @return mixed
     * @throws ReflectionException
     */
    private function getInaccessibleProperty(object $class, string $property): mixed
    {
        $reflectionClass = new ReflectionClass($class);
        $reflectionProperty = $reflectionClass->getProperty($property);
        $reflectionProperty->setAccessible(true);
        return $reflectionProperty->getValue($class);
    }
}
