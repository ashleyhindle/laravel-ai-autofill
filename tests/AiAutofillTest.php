<?php

use AshleyHindle\AiAutofill\Jobs\AiAutofillJob;
use AshleyHindle\AiAutofill\Tests\Models\ArticleAutofill;
use AshleyHindle\AiAutofill\Tests\Models\ArticleEmptyAutofill;
use AshleyHindle\AiAutofill\Tests\Models\ArticleExcludedAutofill;
use AshleyHindle\AiAutofill\Tests\Models\ArticleHiddenAutofill;
use AshleyHindle\AiAutofill\Tests\Models\ArticleMixedAutofill;
use AshleyHindle\AiAutofill\Tests\Models\ArticleNoAutofill;
use Illuminate\Support\Facades\Queue;

it('doesn\'t autofill with a missing autofill property', function () {
    Queue::fake();
    $article = ArticleNoAutofill::create(['title' => 'My Article']);
    $article->save();
    Queue::assertNothingPushed();
});

it('doesn\'t autofill with an empty autofill property', function () {
    Queue::fake();
    $article = ArticleEmptyAutofill::create(['title' => 'My Article']);
    $article->save();
    Queue::assertNothingPushed();
});

it('autofills on model creation', function () {
    Queue::fake();
    $article = ArticleAutofill::create(['title' => 'My Article']);
    $article->save();
    Queue::assertPushed(AiAutofillJob::class);
});

it('autofills with a mixed set of autofills model creation', function () {
    Queue::fake();
    $article = ArticleMixedAutofill::create(['title' => 'My Article']);
    $article->save();
    Queue::assertPushed(AiAutofillJob::class);
});

it('doesn\'t autofill when not dirty', function () {
    Queue::fake();
    $article = ArticleAutofill::create(['title' => 'My Article']);
    $article->save();
    Queue::assertPushed(AiAutofillJob::class);

    Queue::fake();
    $article->title = 'My Article';
    $article->save();
    Queue::assertNothingPushed();
});

it('passed excluded properties to the job', function () {
    Queue::fake();
    $content = '### MY CONTENT IS VERY EASY TO SPOT ###';
    $article = ArticleExcludedAutofill::create(['title' => 'My Article', 'content' => $content]);
    $article->save();
    Queue::assertPushed(function (AiAutofillJob $job) {
        return $job->autofill === ['tagline' => 'ridiculous click-bait tagline']
            && $job->autofillExclude === ['content'];
    });
});

it('defaults excluded properties to $hidden', function () {
    Queue::fake();
    $content = '### MY CONTENT IS VERY EASY TO SPOT ###';
    $article = ArticleHiddenAutofill::create(['title' => 'My Article', 'content' => $content]);
    $article->save();
    Queue::assertPushed(function (AiAutofillJob $job) {
        return $job->autofill === ['tagline' => 'ridiculous click-bait tagline']
            && $job->autofillExclude === ['content'];
    });
});
