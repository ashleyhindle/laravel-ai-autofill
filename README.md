# Autofill model properties with AI

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ashleyhindle/laravel-ai-autofill.svg?style=flat-square)](https://packagist.org/packages/ashleyhindle/laravel-ai-autofill)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/ashleyhindle/laravel-ai-autofill/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/ashleyhindle/laravel-ai-autofill/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/ashleyhindle/laravel-ai-autofill/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/ashleyhindle/laravel-ai-autofill/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/ashleyhindle/laravel-ai-autofill.svg?style=flat-square)](https://packagist.org/packages/ashleyhindle/laravel-ai-autofill)

Simplest way to autofill model properties with OpenAI.

This package listens to the `saved` model event, then adds a queued job to autofill the properties from OpenAI.

Example:
```php
class Article extends Model {
    use AshleyHindle\AiAutofill\AiAutofill;

    protected $autofill = ['tagline' => 'a super click-baity obnoxious tagline'];
}
```

## Installation
```bash
composer require ashleyhindle/laravel-ai-autofill
```

## Requirements
You must already have the [openai-php/laravel](https://github.com/openai-php/laravel) package installed to use this package.

## Usage


### Model Trait Usage

Simply use the trait in your model, and add the `$autofill` array.
The keys are the properties you want to autofill, and the values are the prompts you want to use to fill them.

The whole model, minus `$autofillExclude` properties, is provided to the LLM for context, so the prompts in `$autofill` can be very simple.

```php
class MeetingNotes extends Model {
    use AshleyHindle\AiAutofill\AiAutofill;

    protected $autofill = [
        'summary' => 'executive summary',
        'action_items' => 'Flat JSON array of action items (e.g. ["Item 1", "Item 2", "Item 3"])'
    ];

    protected $autofillExclude = ['zoom_password'];
}
```

### Config Usage
Setting up autofill in `config/ai-autofill.php` is coming soon..

## Testing
Coming soon...

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
