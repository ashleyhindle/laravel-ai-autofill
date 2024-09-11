<?php

namespace AshleyHindle\AiAutofill\Providers;

use AshleyHindle\AiAutofill\AutofillContext;
use AshleyHindle\AiAutofill\AutofillResults;
use Illuminate\Support\Facades\Http;

class Ollama implements ProviderContract
{
    public string $url;

    public string $llmModel;

    public function __construct(?string $url = null, ?string $llmModel = null)
    {
        $this->url = $url ?? config('ai-autofill.providers.ollama.url');
        $this->llmModel = $llmModel ?? config('ai-autofill.providers.ollama.defaults.model');
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

        Respond only with valid JSON. Do not write anything extraneous.
PROMPT;
    }

    public function autofill(AutofillContext $context): AutofillResults|array
    {
        $results = new AutofillResults;

        $timeout = config('ai-autofill.providers.ollama.defaults.timeout', config('ai-autofill.defaults.timeout', 10));
        foreach ($context->autofills as $property => $prompt) {
            $response = Http::timeout($timeout)->post($this->url.'/api/chat', [
                'model' => $this->llmModel,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $this->prompt($context),
                    ],
                ],
                'stream' => false,
                'options' => [
                    'temperature' => config('ai-autofill.providers.ollama.defaults.temperature', 0.35),
                ],
            ]);

            $response = json_decode($response->json('message.content'), true, JSON_THROW_ON_ERROR);
            foreach ($context->autofills as $property => $prompt) {
                if (array_key_exists($property, $response)) {
                    $results[$property] = $response[$property];
                }
            }
        }

        return $results;
    }
}
