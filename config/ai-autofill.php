<?php

return [
    'defaults' => [
        'retries' => 5,
        'timeout' => 10,
        'provider' => env('AI_AUTOFILL_PROVIDER', 'openai'),
    ],
    'providers' => [
        'openai' => [
            'defaults' => [
                'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
                'temperature' => 0.4,
            ],
            'api_key' => env('OPENAI_API_KEY'),
        ],
        'anthropic' => [
            'defaults' => [
                'model' => env('ANTHROPIC_MODEL', 'claude-3-5-sonnet-20240620'),
                'temperature' => 0.2,
                'max_tokens' => 1024,
            ],
            'api_key' => env('ANTHROPIC_API_KEY')
        ],
        'ollama' => [
            'defaults' => [
                'temperature' => 0.4,
                'timeout' => 20,
            ],
            'model' => env('OLLAMA_MODEL', 'llama3.1'),
            'url' => env('OLLAMA_URL', 'http://127.0.0.1:11434'),
        ]
    ],

];
