---
title: Installation
weight: 20
description: Install Rich Editor Plus Code, publish its assets, add it to your fields and add its styles to the pages that show content.
---

## Install the package

```bash
composer require webwizardsusa/rich-editor-plus-code
php artisan filament:assets
```

The service provider is discovered automatically. `filament:assets` publishes the editor's JavaScript module, its
stylesheet and Shiki's grammar and theme files into `public/`. Run it again after every update of the package: the
module loads the grammar and theme files by hashed names, so a new module needs its own new files.

> [!TIP]
> Filament apps usually run `php artisan filament:upgrade` from Composer's `post-autoload-dump` script, which
> publishes the assets after every `composer install` and `composer update`.

## Add it to a field

```php
use Webwizardsusa\RichEditorPlus\RichEditorPlus;
use Webwizardsusa\RichEditorPlusCode\CodeBlockPlugin;

RichEditorPlus::make('content')->addPlugins([
    CodeBlockPlugin::make(),
]);
```

The plugin has the same id as the core code block plugin, `core/code-block`, and a plugin replaces any other with the
same id. So `addPlugins()` swaps the default code block for this one, and the field never has two.

To use it in every editor, register it as a default plugin from a service provider:

```php
use Webwizardsusa\RichEditorPlus\RichEditorPlus;
use Webwizardsusa\RichEditorPlusCode\CodeBlockPlugin;

public function boot(): void
{
    RichEditorPlus::registerDefaultPlugins([CodeBlockPlugin::class]);
}
```

With options, pass a closure, so the plugin is built when an editor needs it:

```php
RichEditorPlus::registerDefaultPlugins([
    fn (): CodeBlockPlugin => CodeBlockPlugin::make()->defaultLanguage('php'),
]);
```

Or set its options once for every instance, wherever it is created:

```php
CodeBlockPlugin::configureUsing(fn (CodeBlockPlugin $plugin) => $plugin->defaultLanguage('php'));
```

## Existing content

Blocks stored by the core code block plugin are the same HTML this plugin stores:

```html
<pre><code class="language-php">echo 'Hello';</code></pre>
```

They load unchanged, with their language. A block from the core plugin with no language is plain text.

If you remove the package later, the core code block reads its blocks too. It drops line numbers and highlighted
lines, and keeps the code and language.

## Add the styles to pages that show content

Rendered code blocks carry their colours in inline styles, so they need no highlighting stylesheet. The page still
needs a few rules for the dark theme, line numbers, highlighted lines and the copy button. Add the Blade component
once, in the `<head>` of the layout that shows the content:

```blade
<head>
    {{-- ... --}}
    <x-rich-editor-plus-code::assets />
</head>
```

It prints a `<style>` element and, by default, a small script for the copy button. Two props change it:

| Prop | Values | Default | What it does |
|---|---|---|---|
| `dark` | `class`, `media`, `none` | `class` | When the dark theme applies. `class`: under a `.dark` ancestor, as in Tailwind's and Filament's class strategy. `media`: when the visitor's system prefers dark (`prefers-color-scheme`). `none`: always use the light theme. |
| `copy` | `true`, `false` | `true` | Adds a "Copy" button to each block, shown on hover and focus. `false` leaves the button and the script out. |

```blade
<x-rich-editor-plus-code::assets dark="media" />
<x-rich-editor-plus-code::assets :copy="false" />
```

The copy button copies the code without its line numbers. It adds itself to blocks on page load and after Livewire's
`livewire:navigated` event, so it works with `wire:navigate`.

The editor itself doesn't need the component. Its styles come with the published assets.

Next, see [Rendering](usage/rendering.md) for rendering the content, and [Configuration](configuration.md) for the
options.
