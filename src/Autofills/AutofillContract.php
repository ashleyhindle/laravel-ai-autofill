<?php

namespace AshleyHindle\AiAutofill\Autofills;

use Illuminate\Database\Eloquent\Model;

interface AutofillContract
{
    public static function prompt(Model $model): string;
}
