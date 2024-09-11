<?php

use AshleyHindle\AiAutofill\AutofillContext;
use AshleyHindle\AiAutofill\Jobs\AiAutofillJob;
use AshleyHindle\AiAutofill\Tests\Models\ArticleAutofill;
use AshleyHindle\AiAutofill\Tests\Models\ArticleExcludedAutofill;
use AshleyHindle\AiAutofill\Tests\Models\ArticleMixedAutofill;
use AshleyHindle\AiAutofill\Tests\Models\ArticleNoAutofill;
use Illuminate\Support\Facades\Queue;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Resources\Chat;
use OpenAI\Responses\Chat\CreateResponse;

it('calls the OpenAI API once with the correct parameters', function () {
    OpenAI::fake([
        CreateResponse::fake([
            'choices' => [
                ['message' => ['content' => '{"tagline":"ridiculous click-bait tagline"}']],
            ],
        ]),
    ]);

    $article = new ArticleAutofill(['title' => 'Howdy']);
    $article->saveQuietly();
    $context = new AutofillContext($article);
    AiAutofillJob::dispatch($context);

    OpenAI::assertSent(Chat::class, function (string $method, array $parameters): bool {
        return $method === 'create' &&
            $parameters['model'] === 'gpt-4o-mini' &&
            $parameters['response_format']['type'] === 'json_schema' &&
            str_contains($parameters['messages'][0]['content'], 'Howdy') &&
            str_contains($parameters['messages'][0]['content'], 'ridiculous click-bait tagline');
    });
});

it('handles MIXED parameters beautifully', function () {
    Queue::fake();

    $article = new ArticleMixedAutofill(['title' => 'Howdy']);
    $article->save();
    $context = new AutofillContext($article);

    $jobs = Queue::pushedJobs();
    $job = reset($jobs)[0]['job'];
    expect($job->context->autofills)->toBe([
        'tagline' => 'ridiculous click-bait tagline',
        'seo_description' => 'Kick-ass SEO description not including any of these banned brands: Nike, Reebok, Umbro',
        'tags' => 'CSV of up to 5 unique lowercase tags using only letters, numbers, and hyphens (i.e. tag-1, tag-2, tag3). Only return the most relevant - you do not need to use all 5. Banned tags: tag-1, tag-2, tag3',
    ]);
});

it('calls the OpenAI API, without sharing excluded properties in the prompt', function () {
    OpenAI::fake([
        CreateResponse::fake([
            'choices' => [
                ['message' => ['content' => '{"tagline":"ridiculous click-bait tagline"}']],
            ],
        ]),
    ]);

    $content = '### MY CONTENT IS VERY EASY TO SPOT ###';
    $article = new ArticleExcludedAutofill(['title' => 'Howdy', 'content' => $content]);
    $article->saveQuietly();
    $context = new AutofillContext($article);
    AiAutofillJob::dispatch($context);

    OpenAI::assertSent(Chat::class, function (string $method, array $parameters) use ($content): bool {
        return $method === 'create' &&
            $parameters['model'] === 'gpt-4o-mini' &&
            $parameters['response_format']['type'] === 'json_schema' &&
            str_contains($parameters['messages'][0]['content'], 'Howdy') &&
            str_contains($parameters['messages'][0]['content'], 'ridiculous click-bait tagline') &&
            ! str_contains($parameters['messages'][0]['content'], $content);
    });
});

it('does not call the OpenAI API if there is a missing or empty autofill property', function () {
    OpenAI::fake([
        CreateResponse::fake([
            'choices' => [
                ['message' => ['content' => '{"tagline":"ridiculous click-bait tagline"}']],
            ],
        ]),
    ]);

    $article = new ArticleNoAutofill(['title' => 'Howdy']);
    $article->saveQuietly();

    AiAutofillJob::dispatch(new AutofillContext($article));

    OpenAI::assertNotSent(Chat::class, function (string $method, array $parameters): bool {
        return $method === 'create';
    });
});
