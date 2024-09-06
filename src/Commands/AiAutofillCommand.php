<?php

namespace AshleyHindle\AiAutofill\Commands;

use Illuminate\Console\Command;

class AiAutofillCommand extends Command
{
    public $signature = 'laravel-ai-autofill';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
