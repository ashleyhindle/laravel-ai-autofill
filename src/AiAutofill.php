<?php

namespace AshleyHindle\AiAutofill;

use AshleyHindle\AiAutofill\Jobs\AiAutofillJob;
use Illuminate\Database\Eloquent\Model;

trait AiAutofill
{
    public static function bootAiAutofill()
    {
        static::saved(function (Model $model) {
            if (! isset($model->autofill) || empty($model->autofill) || ! $model->isDirty()) {
                return;
            }

            AiAutofillJob::dispatch($model, $model->autofill, $model->autofillExclude ?? $model->hidden ?? []);
        });
    }
}
