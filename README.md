# Rich Editor Plus Code

Code blocks for [Rich Editor Plus](https://github.com/webwizardsusa/filament-rich-editor-plus). This package adds a
language picker, line numbers and highlighted lines. The editor colours code with [Shiki](https://shiki.style) as
you type, and rendered content (a blog post, say) is highlighted on the server with [Phiki](https://github.com/phikiphp/phiki).
Phiki is a PHP port of Shiki, so the colours match, and the page that shows the content needs no JavaScript to
highlight it.

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

The plugin keeps the core code block's id, so it takes the place of the default one. Content stored before keeps
working: it's the same `<pre><code class="language-php">`.

## In the editor

Place the cursor in a code block. Its toolbar then shows three tools:

- **Language**: a menu of the plugin's languages, with **Plain text** first. The button shows the current language.
- **Line numbers**: toggles numbers for the block.
- **Highlight lines**: asks which lines to pick out, such as `2, 4-6`.

Tab and Shift+Tab indent inside a code block. Typing ```` ```php ```` and a space starts a PHP block.

```php
CodeBlockPlugin::make()
    ->languages(['php' => 'PHP', 'sql' => 'SQL', 'bash' => 'Bash'])  // or a list of ids: ['php', 'sql']
    ->defaultLanguage('php')                                         // new blocks only; without it, plain text
    ->themes(light: 'github-light', dark: 'github-dark');
```

The default list holds about 30 common languages (`CodeBlockPlugin::DEFAULT_LANGUAGES`). The editor loads a
language's grammar the first time a block uses it.

The editor can colour the languages in the default list, and these themes: `github-light`, `github-dark`,
`github-light-default`, `github-dark-default`, `github-dark-dimmed`, `one-light`, `one-dark-pro`, `vitesse-light`,
`vitesse-dark`, `min-light`, `min-dark`, `nord`, `dracula`, `catppuccin-latte`, `catppuccin-mocha`,
`material-theme-lighter`, `material-theme-darker`, `solarized-light` and `solarized-dark`. Any other language or theme
Phiki knows still renders highlighted. In the editor it shows plain, or in the GitHub themes.

## Showing code

Render with the same plugin, as you would any other:

```php
RichContentPlusRenderer::make($post->body)->plugins($plugins)->toHtml();
```

Each code block comes out as Phiki's `<pre>` inside `<div class="rep-code" data-language="php">`. Its colours for both
themes are in inline styles, so no highlighting stylesheet is needed. A language Phiki doesn't know renders as plain
text. Add the styles once, in the layout's `<head>`, for:

- the dark theme;
- line numbers and highlighted lines;
- the copy button.

```blade
<x-rich-editor-plus-code::assets />
<x-rich-editor-plus-code::assets dark="media" />     {{-- follow the visitor's system setting --}}
<x-rich-editor-plus-code::assets :copy="false" />    {{-- no copy button, and no script --}}
```

`dark` is `class` by default: the dark theme applies under a `.dark` ancestor, as in Tailwind's class strategy. Use
`media` to follow `prefers-color-scheme`, and `none` to always use the light theme.

Highlighting is cached per block in Laravel's default cache store for 30 days. The cache key covers the code,
language, themes and options, so an edit gets its own entry. Pick another store, or turn caching off:

```php
CodeBlockPlugin::make()->cache(store: 'redis');
CodeBlockPlugin::make()->cache(false);
```

Highlighting only happens for display (`toHtml()`). `toStorableHtml()`, Markdown and text stay as they were.

## Markdown

The options follow the language in the fence, and read back the same way:

````markdown
```php showLineNumbers {2,4-6}
echo 'Hello';
```
````

A block with options but no language gets `text` as its language word, which reads back as plain text.

## How blocks are stored

```html
<pre data-line-numbers="true" data-highlight-lines="2,4-6"><code class="language-php">echo 'Hello';</code></pre>
```

Highlighted lines are normalized when saved: sorted and merged, with at most 50 ranges, and nothing past line
10,000. Plain text has no language: `text`, `txt` and `plaintext` all read as none.

## Testing

```bash
composer test
```
