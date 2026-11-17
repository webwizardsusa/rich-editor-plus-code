---
title: Markdown
weight: 40
description: How code blocks with a language, line numbers and highlighted lines are written to and read from Markdown fences.
---

When a field stores Markdown, or you export content with `toMarkdown()`, code blocks are written as fenced code
blocks. Line numbers and highlighted lines go after the language in the fence's info string, and are read back the
same way:

````markdown
```php showLineNumbers {2,4-6}
echo 'Hello';
```
````

- The first word is the language.
- `showLineNumbers` turns line numbers on.
- `{…}` holds the highlighted lines, normalized as in the editor (see
  [Line numbers and highlighted lines](line-numbers-and-highlighted-lines.md#how-lines-are-normalized)).

The plugin's Markdown rules apply wherever the editor uses Markdown, for example with a Markdown storage format:

```php
use Webwizardsusa\RichEditorPlus\Plugins\Markdown\MarkdownFormat;
use Webwizardsusa\RichEditorPlus\RichEditorPlus;
use Webwizardsusa\RichEditorPlusCode\CodeBlockPlugin;

RichEditorPlus::make('body')
    ->storeAs(MarkdownFormat::make())
    ->addPlugins([CodeBlockPlugin::make()]);
```

See the [Rich Editor Plus documentation](docs:rich-editor-plus/introduction) for Markdown storage and export.

## How blocks are written

| Block | Fence |
|---|---|
| Language only | ```` ```php ```` |
| Line numbers | ```` ```php showLineNumbers ```` |
| Highlighted lines `3,1` | ```` ```php {1,3} ```` |
| Both | ```` ```php showLineNumbers {2} ```` |
| No language, no options | ```` ``` ```` |
| No language, with options | ```` ```text showLineNumbers ```` |

A fence's first word is always its language, so a plain text block with options gets `text` as a placeholder. `text`
reads back as plain text, so the block round-trips unchanged.

Code that contains a run of backticks gets a longer fence, one backtick more than its longest run:

`````markdown
````md
```
x
```
````
`````

## How fences are read

Fences in Markdown, and in pasted Markdown, become code blocks with:

- the language from the first word, with `text`, `txt`, `plaintext` and `plain` read as plain text;
- line numbers on when the info string contains the word `showLineNumbers`;
- the highlighted lines from the first `{…}` group.

Other words in the info string are ignored.

Any language id works in a fence, including ones the picker doesn't offer and aliases such as `js`. The block keeps it,
and rendered content is highlighted in it if Phiki knows it.
