<?php

namespace AshleyHindle\AiAutofill;

use Illuminate\Database\Eloquent\Model;

class AiAutofillObserver
{
    public function saved(Model $model): void
    {
        dd($model, $model->autofill, $model->autofillExclude);
    }
}
