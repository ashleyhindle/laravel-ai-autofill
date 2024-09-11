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

            AiAutofillJob::dispatch(new AutofillContext($model));
        });
    }

    public function getAutofill(): array
    {
        return $this->autofill ?? [];
    }

    public function getAutofillExclude(): array
    {
        return $this->autofillExclude ?? $this->hidden ?? [];
    }
}
