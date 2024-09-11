<?php

namespace AshleyHindle\AiAutofill\Providers;

use AshleyHindle\AiAutofill\AutofillContext;
use AshleyHindle\AiAutofill\Autofills;
use AshleyHindle\AiAutofill\AutofillResults;
use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI as OpenAIFacade;

class OpenAi implements ProviderContract
{
    public string $apiKey;
    public string $llmModel;

    public function __construct(string $apiKey = null, string $llmModel = null)
    {
        $this->apiKey = $apiKey ?? config('ai-autofill.providers.openai.api_key');
        $this->llmModel = $llmModel ?? config('ai-autofill.providers.openai.defaults.model');
    }

    public function prompt(AutofillContext $context): string
    {
        return <<<PROMPT
        Return JSON matching the JSON schema provided, returning {$context->count} values for the property & prompt values provided by the user for this model: {$context->modelName}.

        You must use the CONTEXT to create the values.
        You must return a valid for each provided PROPERTY & PROMPT. Each value returned must be the value for the property and nothing else. The values should be strings, unless the property prompt specifically requests JSON.

        ### CONTEXT ###
        {$context->jsonModelProperties()}

        ### PROPERTIES & PROMPTS ###
        {$context->jsonAutofillProperties()}
PROMPT;
    }

    public function autofill(AutofillContext $context): AutofillResults|array
    {
        $results = new AutofillResults();


        $schemaProperties = [];
        foreach ($context->autofills as $property => $prompt) {
            $schemaProperties[$property] = [
                'type' => 'string',
                'description' => $prompt,
                'output' => 'string',
                'required' => ['output'],
                'additionalProperties' => false,
            ];
        }

        $jsonSchema = [
            'name' => 'autofill',
            'schema' => [
                'type' => 'object',
                'strict' => true,
                'properties' => $schemaProperties,
                'required' => array_keys($context->autofills),
            ],
        ];

        $result = OpenAIFacade::chat()->create([
            'model' => $this->llmModel ?? 'gpt-4o-mini',
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => $jsonSchema,
            ],
            'temperature' => config('ai-autofill.providers.openai.defaults.temperature', 0.35),
            'messages' => [
                ['role' => 'system', 'content' => $this->prompt($context)],
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
        foreach ($context->autofills as $property => $prompt) {
            if (array_key_exists($property, $response)) {
                $results[$property] = $response[$property];
            }
        }

        return $results;
    }
}
