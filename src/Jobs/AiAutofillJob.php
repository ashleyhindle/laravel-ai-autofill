<?php

namespace AshleyHindle\AiAutofill\Jobs;

use AshleyHindle\AiAutofill\Autofills\AutofillContract;
use Illuminate\Bus\Queueable as QueueableByBus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use OpenAI\Laravel\Facades\OpenAI;
use ReflectionClass;

class AiAutofillJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, QueueableByBus, SerializesModels;

    public function __construct(public Model $model, public array $autofill = [], public array $autofillExclude = []) {}

    public function handle()
    {
        if (! isset($this->autofill) || empty($this->autofill)) {
            return;
        }

        $autofillContext = $this->buildAutofillContext();
        $jsonAutofillContext = json_encode($autofillContext);
        $count = count($autofillContext);
        $modelName = class_basename($this->model);
        $modelProperties = $this->model->toArray();
        foreach ($this->autofillExclude as $property) {
            unset($modelProperties[$property]);
        }
        $modelContext = json_encode($modelProperties);

        $systemPrompt = <<<AUTOFILL_PROMPT
        Return JSON matching the JSON schema provided, returning {$count} values for the property & prompt values provided by the user for this model: {$modelName}.

        You must use the CONTEXT to create the values.
        The values returned must be the value for the property and nothing else. The values should be strings, unless the prompt specifically requests JSON.

        ### CONTEXT ###
        {$modelContext}

        ### PROPERTIES & PROMPTS ###
        {$jsonAutofillContext}
AUTOFILL_PROMPT;

        $schemaProperties = [];
        foreach ($autofillContext as $property => $prompt) {
            $schemaProperties[$property] = [
                'type' => 'string',
                'description' => $prompt,
                'output' => 'string',
                'required' => ['output'],
                'additionalProperties' => false,
            ];
        }

        $result = OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'autofill',
                    'schema' => [
                        'type' => 'object',
                        'strict' => true,
                        'properties' => $schemaProperties,
                        'required' => array_keys($autofillContext),
                    ],
                ],
            ],
            'temperature' => 0.35,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
            ],
        ]);

        if ($result->choices[0]->finishReason === 'length') {
            // TODO: handle finish_reason length failure?
        }

        $message = $result->choices[0]->message;
        /*
        TODO: Find out why openai-php/laravel doesn't support refusals and support it

        if (isset($message->refusal)) {
            // TODO: handle refusal
        } elseif (! isset($message->content)) {
            // TODO: handle no content
        }
            */

        $response = json_decode($message->content, true);
        foreach ($autofillContext as $property => $prompt) {
            if (array_key_exists($property, $response)) {
                $this->model->{$property} = trim($response[$property], " \r\n\t\"'");
            }
        }

        $this->model->saveQuietly();
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping(self::class.':'.$this->model->{$this->model->getKeyName()}))
                ->expireAfter(40)
                ->releaseAfter(40)
                ->dontRelease(),
        ];
    }

    public function buildAutofillContext(): array
    {
        $context = [];

        foreach ($this->autofill as $property => $promptType) {
            $prompt = '';
            if (is_string($promptType) && (trait_exists($promptType) || class_exists($promptType))) { // 'Autofill Contract' compatible class
                // TODO: Reflect on the class to see if it implements the 'prompt' function, if it does call it and add to context
                $class = new ReflectionClass($promptType);
                if ($class->implementsInterface(AutofillContract::class)) {
                    $prompt = call_user_func($promptType.'::prompt', $this->model);
                }
            } elseif (is_numeric($property)) { // local function, numerical index
                $methodName = 'autofill'.Str::studly($promptType);
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
