<?php

namespace AshleyHindle\AiAutofill\Autofills;

use Illuminate\Database\Eloquent\Model;

class TagsCsv implements AutofillContract
{
    public static function prompt(Model $model): string
    {
        return 'CSV of up to 5 unique lowercase tags using only letters, numbers, and hyphens (i.e. tag-1, tag-2, tag3). Only return the most relevant - you do not need to use all 5.

        Banned tags: tag-1, tag-2, tag3';
    }
}
