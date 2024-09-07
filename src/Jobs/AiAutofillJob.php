<?php

namespace AshleyHindle\AiAutofill\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use OpenAI\Laravel\Facades\OpenAI;

class AiAutofillJob implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Model $model, protected array $autofill, protected array $autofillExclude = []) {}

    public function handle()
    {
        if (empty($this->autofill)) {
            return;
        }

        $count = count($this->autofill);
        $jsonAutofill = json_encode($this->autofill);
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
        {$jsonAutofill}
AUTOFILL_PROMPT;

        $schemaProperties = [];
        foreach ($this->autofill as $property => $prompt) {
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
                        'required' => array_keys($this->autofill),
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
        foreach ($this->autofill as $property => $prompt) {
            if (array_key_exists($property, $response)) {
                $this->model->{$property} = trim($response[$property], " \r\n\t\"'");
            }
        }

        $this->model->saveQuietly();
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping(self::class . ':' . $this->model->{$this->model->getKeyName()}))
                ->expireAfter(40)
                ->releaseAfter(40)
                ->dontRelease(),
        ];
    }
}
