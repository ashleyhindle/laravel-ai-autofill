<?php

namespace AshleyHindle\AiAutofill\Jobs;

use AshleyHindle\AiAutofill\AutofillContext;
use AshleyHindle\AiAutofill\Providers\Anthropic;
use AshleyHindle\AiAutofill\Providers\Ollama;
use AshleyHindle\AiAutofill\Providers\OpenAi;
use Illuminate\Bus\Queueable as QueueableByBus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class AiAutofillJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, QueueableByBus, SerializesModels;

    public function __construct(public AutofillContext $context) {}

    public function handle()
    {
        $context = $this->context;
        if (! $context->isValid()) {
            return;
        }

        $model = $context->model;
        $providerName = config('ai-autofill.defaults.provider', 'openai');
        $provider = match ($providerName) {
            'ollama' => new Ollama,
            'anthropic' => new Anthropic,
            'openai' => new OpenAi,
            default => new OpenAi
        };

        $results = $provider->autofill($context);

        foreach ($context->autofills as $property => $prompt) {
            if ($results->has($property)) {
                $model->{$property} = trim($results[$property], " \r\n\t\"'");
            }
        }

        $model->saveQuietly();
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping(self::class.':'.$this->context->model->{$this->context->model->getKeyName()}))
                ->expireAfter(40)
                ->releaseAfter(40)
                ->dontRelease(),
        ];
    }
}
