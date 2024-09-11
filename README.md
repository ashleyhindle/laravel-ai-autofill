![](./example-article-seo.png)

# Autofill model properties with AI

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ashleyhindle/laravel-ai-autofill.svg?style=flat-square)](https://packagist.org/packages/ashleyhindle/laravel-ai-autofill)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/ashleyhindle/laravel-ai-autofill/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/ashleyhindle/laravel-ai-autofill/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/ashleyhindle/laravel-ai-autofill/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/ashleyhindle/laravel-ai-autofill/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/ashleyhindle/laravel-ai-autofill.svg?style=flat-square)](https://packagist.org/packages/ashleyhindle/laravel-ai-autofill)

This package listens to the `saved` model event, then adds a queued job that autofills the properties from either OpenAI, Anthropic or Ollamo, using 1 API query per model.

**Example:**
When this article is saved the 'tagline' property will be automatically filled by an AI generated string that's a 'ridiculous click-bait tagline'
```php
<?php
use AshleyHindle\AiAutofill\AiAutofill;
use Illuminate\Database\Eloquent\Model;

class Article extends Model {
    use AiAutofill;


    protected $autofill = ['tagline' => 'ridiculous click-bait tagline'];
}
```


## Installation
```bash
composer require ashleyhindle/laravel-ai-autofill
```

## Setup
```bash
php artisan ai-autofill:install
```

You'll then have a `config/ai-autofill.php` file that sets up your providers. You'll need to ensure you have the necessary `.env` variables setup.

The key ones are:
```yaml
OPENAI_API_KEY=
ANTHROPIC_API_KEY=
OLLAMA_URL=
OLLAMA_MODEL=
```

## Usage

### Model Trait Usage

Simply use the trait in your model, and add the `$autofill` array with the keys as the properties you want to autofill, and the values as the prompts you want to use to fill them.

The model name and model properties, except `$autofillExclude` properties, are provided to the LLM for context, so the prompts in `$autofill` can be very simple.

Example:
```php
<?php
use AshleyHindle\AiAutofill\AiAutofill;
use AshleyHindle\AiAutofill\Autofills\Tags;
use Illuminate\Database\Eloquent\Model;

class Article extends Model {
    use AiAutofill;

    protected $autofill = [
        'tagline' => 'ridiculous click-bait tagline', // simple string
        'tags' => Tags::class, // AiAutofill tested & provided prompt
        'seo_description' // local function
    ];

    protected $autofillExclude = ['authors_email']; // Won't be included in the prompt context

    public function autofillSeoDescription()
    {
        $bannedBrandsFromDatabase = ['Nike', 'Reebok', 'Umbro'];

        return 'Concise SEO description not including any of these brands: ' . implode(', ', $bannedBrandsFromDatabase);
    }
}
```

## Testing

```bash
composer test
```

## Credits
- [Ashley Hindle](https://github.com/ashleyhindle)
- [Kathryn Reeve](https://github.com/binarykitten)
- [Nik Spyratos](https://github.com/nikspyratos)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

### TODO
- [ ] Handle OpenAI failures more gracefully
- [ ] Add config file support
- [ ] Allow overriding system prompt
- [ ] Allow setting queue name and max attempts
- [ ] Enable prompt creation through PHP Attributes
