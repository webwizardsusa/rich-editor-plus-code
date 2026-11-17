# Rich Editor Plus Code

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

The full documentation is at
[https://DOCS_SITE_URL/docs/rich-editor-plus-code](https://DOCS_SITE_URL/docs/rich-editor-plus-code).

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md).
