<?php

namespace AshleyHindle\AiAutofill\Tests\Models;

use AshleyHindle\AiAutofill\AiAutofill;
use AshleyHindle\AiAutofill\Autofills\TagsCsv;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticleMixedAutofill extends Model
{
    use AiAutofill, HasFactory;

    protected $table = 'articles';

    protected $autofill = [
        'tagline' => 'ridiculous click-bait tagline', // simple string
        'seo_description', // local magic function?
        'tags' => TagsCsv::class, // Valid AiAutofill provided class
        'title' => Model::class, // Invalid class should be ignored
        'title_two' => HasFactory::class, // Invalid trait should be ignored
    ];

    public function autofillSeoDescription()
    {
        $bannedBrandsFromDatabase = ['Nike', 'Reebok', 'Umbro'];

        return 'Kick-ass SEO description not including any of these banned brands: '.implode(', ', $bannedBrandsFromDatabase);
    }

    protected $fillable = ['title', 'content', 'tagline', 'seo_description', 'tags'];
}
