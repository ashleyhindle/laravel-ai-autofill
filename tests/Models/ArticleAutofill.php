<?php

namespace AshleyHindle\AiAutofill\Tests\Models;

use AshleyHindle\AiAutofill\AiAutofill;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticleAutofill extends Model
{
    use AiAutofill, HasFactory;

    protected $table = 'articles';

    protected $autofill = ['tagline' => 'ridiculous click-bait tagline'];

    protected $fillable = ['title', 'content', 'tagline', 'seo_description', 'tags'];
}
