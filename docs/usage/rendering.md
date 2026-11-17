---
title: Rendering
weight: 30
description: Render content with highlighted code blocks, what the HTML looks like, and how highlighting is cached.
---

## Render with the same plugin

Code blocks are highlighted when content is rendered for display with `toHtml()`. Render with the same plugins the
field uses, including `CodeBlockPlugin` with the same themes:

```php
use Webwizardsusa\RichEditorPlus\Rendering\RichContentPlusRenderer;
use Webwizardsusa\RichEditorPlus\Support\PluginRegistry;
use Webwizardsusa\RichEditorPlusCode\CodeBlockPlugin;

$plugins = PluginRegistry::mergePlugins(PluginRegistry::defaultPlugins(), [
    CodeBlockPlugin::make()->defaultLanguage('php'),
]);

$html = RichContentPlusRenderer::make($post->body)->plugins($plugins)->toHtml();
```

`plugins()` sets the whole list, so merge the plugin into the defaults as above, the same way the field's
`addPlugins()` does. A helper class that returns the list for both the field and the renderer keeps them in step.

If you [registered the plugin as a default](../installation.md#add-it-to-a-field), the renderer's default plugins
include it, and you can leave `plugins()` out:

```php
$html = RichContentPlusRenderer::make($post->body)->toHtml();
```

Render many documents at once with `many()`. The plugin highlights every block in the batch:

```php
$htmls = RichContentPlusRenderer::many($posts->pluck('body', 'id'))->plugins($plugins)->toHtml();
```

Then print the HTML unescaped, and add the [assets component](../installation.md#add-the-styles-to-pages-that-show-content)
to the page:

```blade
<x-rich-editor-plus-code::assets />

<article>
    {!! $html !!}
</article>
```

## The rendered HTML

Each code block becomes Phiki's `<pre>` inside a wrapper:

```html
<div class="rep-code" data-language="php">
    <pre class="phiki language-php github-light phiki-themes github-dark" style="...">
        <code>...</code>
    </pre>
</div>
```

- `rep-code` is the wrapper the assets component styles, and where the copy button goes.
- `data-language` holds the block's language. A plain text block has none.
- Phiki writes the light theme's colours as inline styles, and the dark theme's as `--phiki-dark-*` custom
  properties, which the assets component switches to in dark mode. No highlighting stylesheet is needed.
- With line numbers on, each line starts with a `line-number` element. Highlighted lines have the classes
  `line highlighted`.
- The code stays escaped: `<script>` in a block renders as text.

A language Phiki doesn't know, and a plain text block, render as plain text in the same markup.

### Styling

The assets component prints the base styles: spacing, rounded corners, horizontal scrolling, the line number gutter,
highlighted lines and the copy button. To change them, add your own rules for `.rep-code`, `.rep-code .phiki`,
`.rep-code .phiki .line.highlighted`, `.rep-code .phiki .line-number` and `.rep-code-copy` after the component.

## What isn't highlighted

Highlighting only happens for display. These stay as they were stored:

- `toStorableHtml()`, which gives `<pre><code class="language-php">`, for saving;
- `toArray()`, the document;
- `toMarkdown()` and `toFormat()`, see [Markdown](markdown.md);
- `toText()`.

> [!CAUTION]
> Don't store the output of `toHtml()`. It is for display only. Store what the field saves, or `toStorableHtml()`.

## Caching

Each highlighted block is cached for 30 days, in the default cache store unless you
[chose another](../configuration.md#caching). The key covers the code, its language, the themes, line numbers,
highlighted lines and the Phiki version, so edits, a theme change or a Phiki update get new entries. Rendering the same
block again, on any page, is a cache hit.

Turn caching off with `cache(false)`, for example in tests.
