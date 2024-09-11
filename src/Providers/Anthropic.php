<?php

namespace AshleyHindle\AiAutofill\Providers;

use AshleyHindle\AiAutofill\AutofillContext;
use AshleyHindle\AiAutofill\Autofills;
use AshleyHindle\AiAutofill\AutofillResults;
use Illuminate\Support\Facades\Http;

class Anthropic implements ProviderContract
{
    public string $apiKey;
    public string $llmModel;

    public function __construct(string $apiKey = null, string $llmModel = null)
    {
        $this->apiKey = $apiKey ?? config('ai-autofill.providers.anthropic.api_key');
        $this->llmModel = $llmModel ?? config('ai-autofill.providers.anthropic.defaults.model');
    }

    public function prompt(AutofillContext $context): string
    {
        return <<<PROMPT
        Return a JSON dictionary with {$context->count} keys for the properties & generation prompt provided below for this data model: {$context->modelName}.

        The keys in the response must be the properties. The values must be your generated string.

        You should use the CONTEXT to help generate the values.

        Each value returned must be the value for the property and nothing else. The values should be strings, unless the property prompt specifically requests JSON.

        ### CONTEXT ###
        {$context->jsonModelProperties()}

        ### PROPERTIES & PROMPT JSON ###
        {$context->jsonAutofillProperties()}
PROMPT;
    }

    public function autofill(AutofillContext $context): AutofillResults|array
    {
        $results = new AutofillResults();

        $timeout = config('ai-autofill.defaults.timeout', 10);
        $response = Http::timeout($timeout)
            ->withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                // 'system' => $this->prompt($context),
                'model' => $this->llmModel,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $this->prompt($context)
                    ]
                ],
                'stream' => false,
                'temperature' => config('ai-autofill.providers.anthropic.defaults.temperature', 0.35),
                'max_tokens' => config('ai-autofill.providers.anthropic.defaults.max_tokens', 1024),
            ]);

        $response = json_decode($response->json('content.0.text'), true, JSON_THROW_ON_ERROR);
        foreach ($context->autofills as $property => $prompt) {
            if (array_key_exists($property, $response)) {
                $results[$property] = $response[$property];
            }
        }

        return $results;
    }
}
