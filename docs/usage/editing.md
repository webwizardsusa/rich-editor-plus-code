---
title: In the editor
weight: 10
description: Creating code blocks, the language, line numbers and highlight lines tools, their names, and keyboard behaviour.
---

## Creating a code block

- Type ```` ``` ```` and a space at the start of an empty paragraph. Add a language id first, as in
  ```` ```php ```` and a space, to start a block in that language. Three tildes (`~~~`) work too.
- Pick **Code block** from the slash menu or the block switcher.

Without a language in the shortcut, a new block gets the [default language](../configuration.md#default-language), or
plain text when there is none.

Inside a block, <kbd>Tab</kbd> and <kbd>Shift</kbd>+<kbd>Tab</kbd> indent and outdent by four spaces instead of moving
focus out of the editor.

The editor colours the code as you type. The first block in a language loads that language's grammar, so it shows
plain for a moment and is coloured when the grammar arrives.

## The code block tools

Place the cursor in a code block, and the toolbar shows three tools for it:

| Tool | Name | What it does |
|---|---|---|
| **Language** | `codeBlockLanguage` | A menu of the plugin's languages, with **Plain text** first. The button shows the current language's name. |
| **Line numbers** | `toggleCodeBlockLineNumbers` | Turns line numbers on or off for the block. Pressed while they are on. |
| **Highlight lines** | `highlightCodeLines` | Opens a modal that asks which lines to highlight, such as `2, 4-6`. |

The tools show with only a cursor in the block; you don't need to select text. They are in the floating toolbar
above the block, and in the block tools section of the fixed toolbar when the field has one.

See [Line numbers and highlighted lines](line-numbers-and-highlighted-lines.md) for what the last two store.

### Arranging the toolbar

The three are block tools: they belong to the code block, not to the formatting tools. A field's `toolbarTools()` and
`fixedToolbarTools()` lists place them as a group through the `blockTools` slot, which holds the active block's own
tools:

```php
use Webwizardsusa\RichEditorPlus\RichEditorPlus;

RichEditorPlus::make('content')
    ->fixedToolbar()
    ->fixedToolbarTools([['blockSwitcher'], ['blockTools'], ['more']]);
```

A list that names controls but leaves out `blockTools` hides them, along with every other block's tools.

> [!NOTE]
> Toolbar lists arrange formatting tools by name, but block tools only as a group. Listing `codeBlockLanguage`,
> `toggleCodeBlockLineNumbers` or `highlightCodeLines` in `toolbarTools()` doesn't place or reorder them. Their
> names are useful for CSS (each button has `data-rep-tool="{name}"`) and for [extending](../extending.md).

## The language menu

The menu lists **Plain text** and then the plugin's languages, in the order of
[`languages()`](../configuration.md#languages). Picking one changes the block's language and recolours it.

A block can hold a language that isn't in the list, such as one from Markdown. The menu then shows **Plain text** as
current, but the block keeps its language until you pick another, and rendered content is highlighted in it if Phiki
knows it.

Language aliases such as `js`, `ts`, `py`, `sh` or `yml` are kept as they are written. The editor colours them with
the grammar they stand for, and Phiki knows them too.
