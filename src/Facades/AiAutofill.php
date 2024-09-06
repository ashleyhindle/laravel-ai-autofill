<?php

namespace AshleyHindle\AiAutofill\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \AshleyHindle\AiAutofill\AiAutofill
 */
class AiAutofill extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \AshleyHindle\AiAutofill\AiAutofill::class;
    }
}
