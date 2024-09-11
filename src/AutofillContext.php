<?php

namespace AshleyHindle\AiAutofill;

use AshleyHindle\AiAutofill\Autofills\AutofillContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use ReflectionClass;

class AutofillContext
{
    public string $modelName;

    public array $modelProperties;

    public array $autofills;

    public array $autofillExclude;

    public int $count;

    public function __construct(public Model $model)
    {
        $this->modelName = class_basename($model);
        $this->modelProperties = $model->toArray();
        $this->exclude($model->getAutofillExclude());
        $this->autofill($model->getAutofill());

        return $this;
    }

    public function isValid(): bool
    {
        return ! empty($this->autofills);
    }

    public function autofill(array $autofills): AutofillContext
    {
        $autofills = $this->buildAutofillPrompts($autofills);
        $this->count = count($autofills);
        $this->autofills = $autofills;

        return $this;
    }

    public function exclude(array $excludes): AutofillContext
    {
        $modelProperties = $this->modelProperties;
        foreach ($excludes as $property) {
            unset($modelProperties[$property]);
        }

        $this->autofillExclude = $excludes;
        $this->modelProperties = $modelProperties;

        return $this;
    }

    public function jsonModelProperties(): string
    {
        return json_encode($this->modelProperties);
    }

    public function jsonAutofillProperties(): string
    {
        return json_encode($this->autofills);
    }

    public function buildAutofillPrompts(array $autofills): array
    {
        $context = [];

        foreach ($autofills as $property => $promptType) {
            $prompt = '';
            if (is_string($promptType) && (trait_exists($promptType) || class_exists($promptType))) { // 'Autofill Contract' compatible class
                // TODO: Reflect on the class to see if it implements the 'prompt' function, if it does call it and add to context
                $class = new ReflectionClass($promptType);
                if ($class->implementsInterface(AutofillContract::class)) {
                    $prompt = call_user_func($promptType . '::prompt', $this->model);
                }
            } elseif (is_numeric($property)) { // local function, numerical index
                $methodName = 'autofill' . Str::studly($promptType);
                if (method_exists($this->model, $methodName)) {
                    $property = $promptType;
                    $prompt = call_user_func([$this->model, $methodName]);
                }
            } elseif (is_string($promptType)) {
                $prompt = $promptType;
            }

            $context[$property] = $prompt;
        }

        return array_filter($context);
    }
}
