---
title: Line numbers and highlighted lines
weight: 20
description: How line numbers and highlighted lines are set, written, validated and stored, and the limits on them.
---

Both are per block. Line numbers are on or off; highlighted lines are a list of lines and ranges. They show in the
editor and in rendered content.

## Line numbers

The **Line numbers** tool turns them on or off for the block at the cursor. They are drawn beside the code, never part
of it: they aren't stored in the code, aren't selected with it, and the copy button on rendered pages leaves them out.

Stored as an attribute on the `<pre>`:

```html
<pre data-line-numbers="true"><code class="language-php">echo 'Hello';</code></pre>
```

A block without line numbers has no attribute.

## Highlighted lines

The **Highlight lines** tool opens a modal with one field, filled with the block's current lines. Enter line numbers
and ranges from 1, separated by commas:

```text
2, 4-6
```

Leave it empty to highlight none. Spaces don't matter. Input that isn't a list of numbers and ranges, such as `two`
or `2;4`, or that leaves no valid line, such as `0`, is refused with a validation message. The field takes at most 500
characters.

Stored as an attribute on the `<pre>`:

```html
<pre data-highlight-lines="2,4-6"><code class="language-php">...</code></pre>
```

In the editor, highlighted lines get a yellow background. In rendered content, each highlighted line has the
`highlighted` class, which the [assets component](../installation.md#add-the-styles-to-pages-that-show-content) styles
with a tinted background and a bar at the left edge.

## How lines are normalized

Lines are normalized whenever they are read, from the modal, from stored HTML or from Markdown, so stored content only
ever holds a clean list:

| Input | Stored |
|---|---|
| `2` | `2` |
| ` 4-6 , 2 ` | `2,4-6` (sorted, spaces removed) |
| `1-3,2-5,7` | `1-5,7` (overlapping ranges merged) |
| `1-2,3` | `1-3` (adjacent ranges merged) |
| `5-3,1` | `1` (a backwards range is dropped) |
| `0,2` | `2` (line 0 is dropped) |
| `1-999999` | `1-10000` (capped) |
| `two`, `1,`, empty | nothing |

A line past the end of the block is kept, and simply highlights nothing.

### Limits

The limits are constants on `Webwizardsusa\RichEditorPlusCode\Support\LineRanges`:

| Constant | Value | Effect |
|---|---|---|
| `LineRanges::MAX_LINE` | `10000` | The highest line a range can reach. A larger end is cut to it, and a range that starts past it is dropped. |
| `LineRanges::MAX_RANGES` | `50` | How many ranges a block keeps, after merging. Ranges past the 50th are dropped. |

They keep hostile input, such as `1-999999` or thousands of ranges, from making rendering loop over huge numbers.

### Using LineRanges yourself

`LineRanges` is the class the plugin uses, and you can call it to check or clean values of your own:

```php
use Webwizardsusa\RichEditorPlusCode\Support\LineRanges;

LineRanges::isValid('2, 4-6');     // true; blank also counts as valid
LineRanges::parse('4-6,2');        // [[2, 2], [4, 6]]: sorted, merged, 1-based and inclusive
LineRanges::normalize('4-6, 2');   // '2,4-6', or null for none
```

## Both together

```html
<pre data-line-numbers="true" data-highlight-lines="2,4-6"><code class="language-php">echo 'Hello';</code></pre>
```

In Markdown they are written after the language in the fence; see [Markdown](markdown.md).
