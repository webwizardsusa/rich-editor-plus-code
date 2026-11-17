---
title: Configuration
weight: 30
description: Choose the languages on offer, the default language, the light and dark themes, and how highlighted blocks are cached.
---

`CodeBlockPlugin` is configured with chained methods:

```php
use Webwizardsusa\RichEditorPlusCode\CodeBlockPlugin;

CodeBlockPlugin::make()
    ->languages(['php' => 'PHP', 'sql' => 'SQL', 'bash' => 'Bash'])
    ->defaultLanguage('php')
    ->themes(light: 'github-light', dark: 'github-dark')
    ->cache(store: 'redis');
```

Use the same configuration for the field and for rendering, so the editor and the rendered page agree. A shared
method that returns the plugin, or `configureUsing()` (see [Installation](installation.md#add-it-to-a-field)), keeps
them in step.

## Languages

```php
CodeBlockPlugin::make()->languages(['php' => 'PHP', 'sql' => 'SQL']);
CodeBlockPlugin::make()->languages(['php', 'sql']);
```

`languages()` sets the languages the picker offers, as `id => label`, or as a list of ids, which are then their own
labels. The ids are Shiki and Phiki language ids, such as `php`, `javascript` or `bash`. The order you give is the
order of the menu, after **Plain text**, which is always first.

An id must be 1 to 32 letters, digits, `_`, `+`, `-` or `#`, because it ends up in the `language-*` class. Anything
else throws an `InvalidArgumentException`.

The list only limits the picker. Content may hold other languages, from Markdown, pasted HTML or older content. Those
blocks keep their language and render highlighted if Phiki knows it. In the editor, the picker shows **Plain text** as
current for them.

### The default list

Without `languages()`, the picker offers `CodeBlockPlugin::DEFAULT_LANGUAGES`, a list of common languages that both
Shiki and Phiki know, and that the editor can colour:

| Id | Label | Id | Label |
|---|---|---|---|
| `bash` | Bash | `kotlin` | Kotlin |
| `blade` | Blade | `markdown` | Markdown |
| `c` | C | `php` | PHP |
| `cpp` | C++ | `python` | Python |
| `csharp` | C# | `ruby` | Ruby |
| `css` | CSS | `rust` | Rust |
| `diff` | Diff | `scss` | SCSS |
| `dockerfile` | Dockerfile | `sql` | SQL |
| `go` | Go | `swift` | Swift |
| `graphql` | GraphQL | `tsx` | TSX |
| `html` | HTML | `typescript` | TypeScript |
| `java` | Java | `vue` | Vue |
| `javascript` | JavaScript | `xml` | XML |
| `json` | JSON | `yaml` | YAML |
| `jsx` | JSX | | |

To add to it rather than replace it, merge the constant:

```php
CodeBlockPlugin::make()->languages([...CodeBlockPlugin::DEFAULT_LANGUAGES, 'toml' => 'TOML']);
```

A language outside this list renders highlighted on the server but shows as plain text in the editor. See
[Extending](extending.md#languages-and-themes-the-editor-doesnt-know).

### Plain text

Plain text is stored as a block with no language. When content is read, `text`, `txt`, `plaintext` and `plain` (in
any case) count as no language too.

## Default language

```php
CodeBlockPlugin::make()->defaultLanguage('php');
```

The language a new code block starts with, whether it is made from the slash menu, the block switcher or by typing
```` ``` ```` and a space. Without one, new blocks are plain text. It only affects new blocks: stored blocks keep the
language they have, so a plain text block stays plain text.

The default must be one of the plugin's languages, or `defaultLanguage()` throws an `InvalidArgumentException`. Call
`languages()` first when you set both. Pass `null` to go back to plain text.

## Themes

```php
CodeBlockPlugin::make()->themes(light: 'one-light', dark: 'one-dark-pro');
```

The Shiki and Phiki themes for light and dark mode. The default is `github-light` and `github-dark`. Both themes are
used at once: rendered code carries the colours of both, and the page's dark mode setting picks one (see the `dark`
prop in [Installation](installation.md#add-the-styles-to-pages-that-show-content)). In the editor, Filament's dark mode
picks one.

The editor can colour with these themes:

`catppuccin-latte`, `catppuccin-mocha`, `dracula`, `github-dark`, `github-dark-default`, `github-dark-dimmed`,
`github-light`, `github-light-default`, `material-theme-darker`, `material-theme-lighter`, `min-dark`, `min-light`,
`nord`, `one-dark-pro`, `one-light`, `solarized-dark`, `solarized-light`, `vitesse-dark`, `vitesse-light`.

Any other theme Phiki knows works for rendered content. The editor then uses `github-light` or `github-dark` in its
place and logs a warning in the browser console.

> [!WARNING]
> `themes()` doesn't check the names. A theme Phiki doesn't know makes rendering throw a
> `Phiki\Exceptions\UnrecognisedThemeException`, so check the spelling against
> [Phiki's themes](https://github.com/phikiphp/phiki).

## Caching

Highlighting a block takes time, so rendered blocks are cached by default, in Laravel's default cache store, for 30
days. Each block has its own entry, keyed on its code, language, themes, line numbers, highlighted lines and the
installed Phiki version. An edited block gets a new entry, and a Phiki update doesn't serve old output. Old entries
expire on their own.

```php
CodeBlockPlugin::make()->cache(store: 'redis');   // another store
CodeBlockPlugin::make()->cache(false);            // no caching
```

`cache(bool $condition = true, ?string $store = null)`: the store is a name from `config/cache.php`, or `null` for the
default store. Turning caching off is useful in tests.

## Translations

The labels (the tools, the highlight lines modal, **Plain text**, and the copy button's **Copy** and **Copied**) are in
the `rich-editor-plus-code::plugin` translation file. Publish it to change them or add a language:

```bash
php artisan vendor:publish --tag=rich-editor-plus-code-translations
```

## All methods

| Method | Default | Notes |
|---|---|---|
| `languages(array $languages)` | `DEFAULT_LANGUAGES` | `id => label`, or a list of ids. Throws on an invalid id. |
| `defaultLanguage(?string $language)` | `null` (plain text) | Must be one of the languages. |
| `themes(string $light, string $dark)` | `github-light`, `github-dark` | Shiki/Phiki theme names. |
| `cache(bool $condition = true, ?string $store = null)` | on, default store | 30 days per block. |
| `getLanguages()`, `getDefaultLanguage()`, `getThemes()` | | Read the settings back. |
| `CodeBlockPlugin::parseLanguage(?string $language)` | | Static. A language name as the plugin stores it: `null` for plain text names and invalid names. |
