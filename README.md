# Rich Editor Plus Code

[![Latest version on Packagist](https://img.shields.io/packagist/v/webwizardsusa/rich-editor-plus-code.svg)](https://packagist.org/packages/webwizardsusa/rich-editor-plus-code)
[![Tests](https://github.com/webwizardsusa/rich-editor-plus-code/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/webwizardsusa/rich-editor-plus-code/actions/workflows/tests.yml)
[![Total downloads](https://img.shields.io/packagist/dt/webwizardsusa/rich-editor-plus-code.svg)](https://packagist.org/packages/webwizardsusa/rich-editor-plus-code/stats)
[![PHP version](https://img.shields.io/packagist/dependency-v/webwizardsusa/rich-editor-plus-code/php.svg)](https://packagist.org/packages/webwizardsusa/rich-editor-plus-code)
[![Rich Editor Plus version](https://img.shields.io/packagist/dependency-v/webwizardsusa/rich-editor-plus-code/webwizardsusa/filament-rich-editor-plus.svg?label=Rich%20Editor%20Plus)](https://github.com/webwizardsusa/filament-rich-editor-plus)
[![License](https://img.shields.io/packagist/l/webwizardsusa/rich-editor-plus-code.svg)](https://github.com/webwizardsusa/rich-editor-plus-code/blob/main/LICENSE.md)

Code blocks for [Rich Editor Plus](https://github.com/webwizardsusa/filament-rich-editor-plus), with a language
picker, line numbers and highlighted lines. The editor colours code with [Shiki](https://shiki.style) as you type, and
rendered content is highlighted on the server with [Phiki](https://github.com/phikiphp/phiki), so the colours match
and the page that shows the content needs no JavaScript to highlight it. The plugin takes the place of the core code
block, and content stored before keeps working.

Requires PHP 8.3+ and Rich Editor Plus 0.2+ (Filament 5).

```bash
composer require webwizardsusa/rich-editor-plus-code
php artisan filament:assets
```

```php
use Webwizardsusa\RichEditorPlusCode\CodeBlockPlugin;

RichEditorPlus::make('content')->addPlugins([
    CodeBlockPlugin::make()->defaultLanguage('php'),
]);
```

Render with the same plugin, and add the styles once to the layout that shows the content:

```blade
<x-rich-editor-plus-code::assets />
```

## Documentation

The full documentation is in the [`docs` folder](https://github.com/webwizardsusa/rich-editor-plus-code/tree/main/docs).

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md).
