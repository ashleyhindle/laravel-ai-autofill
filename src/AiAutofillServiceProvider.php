<?php

namespace AshleyHindle\AiAutofill;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use AshleyHindle\AiAutofill\Commands\AiAutofillCommand;

class AiAutofillServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-ai-autofill')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_ai_autofill_table')
            ->hasCommand(AiAutofillCommand::class);
    }
}
