<?php

namespace AshleyHindle\AiAutofill\Tests\Models;

use AshleyHindle\AiAutofill\AiAutofill;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticleEmptyAutofill extends Model
{
    use AiAutofill, HasFactory;

    protected $table = 'articles';

    protected $autofill = [];

    protected $fillable = ['title'];
}
