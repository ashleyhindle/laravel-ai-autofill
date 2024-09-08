<?php

namespace AshleyHindle\AiAutofill\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use AshleyHindle\AiAutofill\AiAutofill;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ArticleNoAutofill extends Model
{
    use AiAutofill, HasFactory;
    protected $table = 'articles';

    protected $fillable = ['title', 'content', 'tagline', 'seo_description', 'tags'];
}
