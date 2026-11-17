---
title: Introduction
weight: 10
description: What Rich Editor Plus Code adds to the editor, a short example, and its requirements.
---

Rich Editor Plus Code replaces the code block of [Rich Editor Plus](docs:rich-editor-plus/introduction) with one that
has:

- a **language picker**, with the current language's name on the toolbar button;
- **line numbers**, switched on per block;
- **highlighted lines**, such as `2, 4-6`, to draw attention to part of a snippet;
- **syntax colouring** as you type, with light and dark themes.

The editor colours code with [Shiki](https://shiki.style). Rendered content, such as a blog post, is highlighted on
the server with [Phiki](https://github.com/phikiphp/phiki), a PHP port of Shiki that uses the same grammars and
themes. The colours match, and the page that shows the content needs no JavaScript to highlight it.

```php
use Webwizardsusa\RichEditorPlus\RichEditorPlus;
use Webwizardsusa\RichEditorPlusCode\CodeBlockPlugin;

RichEditorPlus::make('content')->addPlugins([
    CodeBlockPlugin::make()->defaultLanguage('php'),
]);
```

Render the content with the same plugin, and add the styles to the page once:

```php
use Webwizardsusa\RichEditorPlus\Rendering\RichContentPlusRenderer;

$html = RichContentPlusRenderer::make($post->body)->plugins($plugins)->toHtml();
```

```blade
<head>
    <x-rich-editor-plus-code::assets />
</head>
```

## How it fits with the editor

The plugin keeps the core code block's id, `core/code-block`, so adding it replaces the default code block rather than
adding a second one. It stores blocks the way the core plugin does, `<pre><code class="language-php">`, so content
stored before keeps working, and content stored with it stays readable without it. Line numbers and highlighted lines
are two extra attributes on the `<pre>`.

Highlighting happens only when content is rendered for display. Stored HTML, Markdown and plain text output stay
plain.

## Where to go next

- [Installation](installation.md): install the package and add it to a field.
- [Configuration](configuration.md): languages, the default language, themes and caching.
- [Using code blocks](usage/_index.md): the editor tools, line numbers and highlighted lines, rendering and Markdown.
- [Extending](extending.md): other languages and themes, and changing the plugin from your own code.

## Requirements

- PHP 8.3 or later
- Rich Editor Plus 0.2 or later (`webwizardsusa/filament-rich-editor-plus`), with Filament 5
- Phiki 2.2 or later, installed with the package
