---
title: Extending
weight: 50
description: Use languages and themes beyond the editor's built-in set, and add to the code block plugin from your own code.
---

## Languages and themes the editor doesn't know

The two halves of the package highlight code differently:

- **Rendering** uses Phiki, which ships with over 200 grammars and about 60 themes. Any language id or theme
  name Phiki knows works in rendered content.
- **The editor** uses Shiki in the browser, but only with the grammars and themes listed in the package's
  `resources/js/languages.js`. Each one is built into a separate file of `dist/`, loaded the first time a block needs
  it. The list covers [`DEFAULT_LANGUAGES`](configuration.md#the-default-list) and the
  [themes the editor can colour with](configuration.md#themes).

So a language outside the editor's list, such as `toml`, is offered by the picker if you add it with `languages()`,
stored and rendered highlighted, but shows as plain text while editing:

```php
use Webwizardsusa\RichEditorPlusCode\CodeBlockPlugin;

CodeBlockPlugin::make()->languages([...CodeBlockPlugin::DEFAULT_LANGUAGES, 'toml' => 'TOML']);
```

A theme outside the editor's list renders as configured, and the editor falls back to `github-light` or
`github-dark` for that mode, with a warning in the browser console.

### Adding a grammar or theme to the editor

The editor's list is fixed when the package is built, and there is no option to add to it from an app. To colour
another language or theme in the editor, change the list and rebuild the package's assets, for example in a fork:

1. Add the grammar or theme to the maps in `resources/js/languages.js`, using Shiki's module for it:

   ```js
   export const languages = {
       // ...
       toml: () => import('shiki/langs/toml.mjs'),
   }

   export const themes = {
       // ...
       'tokyo-night': () => import('shiki/themes/tokyo-night.mjs'),
   }
   ```

   Other names for a language, such as `yml` for `yaml`, go in the `aliases` map, so the editor colours them with the
   right grammar.

2. Install the package's dev dependencies and build, from the package folder:

   ```bash
   npm install
   npm run build
   ```

3. Publish the new build from the app:

   ```bash
   php artisan filament:assets
   ```

The service provider registers every `dist/chunks-*.js` file it finds, so new grammar and theme files are published
without further changes. Use an id Phiki also knows, so the server highlights the language too.

### What Phiki can't do here

The plugin creates its own Phiki instance, so you can't register a custom grammar or theme with Phiki for it. Use
the grammars and themes Phiki ships with. `themes()` refuses a theme name Phiki doesn't know.

## Adding to the plugin

The plugin keeps the core code block's plugin id, `core/code-block`. Its JavaScript extension has an id of its own,
`webwizardsusa/code-block`, in the constant `CodeBlockPlugin::JS_EXTENSION`.

| To change | Use | With the id |
|---|---|---|
| The PHP side: tools, actions, block types, JS modules | `RichEditorPlus::extendPlugin()` | `CodeBlockPlugin::class` or `'core/code-block'` |
| The TipTap extension in the browser | `extendExtension()` in a JS module | `'webwizardsusa/code-block'` |

### From PHP

`RichEditorPlus::extendPlugin()` takes the plugin's id or class and a callback that receives a `PluginExtension`. Call
it from a service provider's `boot()`:

```php
use Acme\CodeTools\CodeToolsServiceProvider;
use Webwizardsusa\RichEditorPlus\RichEditorPlus;
use Webwizardsusa\RichEditorPlus\Support\PluginExtension;
use Webwizardsusa\RichEditorPlus\Support\Tool;
use Webwizardsusa\RichEditorPlusCode\CodeBlockPlugin;

RichEditorPlus::extendPlugin(CodeBlockPlugin::class, fn (PluginExtension $extension) => $extension
    ->addTool(
        Tool::make('selectCodeBlockContent')
            ->label('Select code')
            ->icon('mdi-select-all')
            ->command('selectCodeBlockContent')
            ->block()
            ->nodes(['codeBlock'])
            ->showWithCursor()
            ->group('code')
            ->order(40),
    )
    ->addModule(CodeToolsServiceProvider::moduleUrl()));
```

Here `CodeToolsServiceProvider` stands for the service provider of a package of your own that ships a JS module with
`RegistersPluginAssets` (see the [example plugin](docs:rich-editor-plus-example/creating-your-plugin)). Passing the
class resolves it to its id, `core/code-block`. Because both this plugin and the core one have that id,
the extension applies to whichever code block a field uses.

`PluginExtension` also has `addTools()`, `addActions()`, `addBlockType()`, `addBlockTypes()`, `addNodes()`,
`addMarks()` and `addGlobalAttributes()`.

The package's own tools use the `code` group with orders 10, 20 and 30, so an order of 40 places a new tool after
them.

### From JavaScript

A module added with `addModule()` is loaded with the plugin. Its default export receives the editor's API, including
`extendExtension()`, which changes the TipTap extension another plugin registered:

```js
export default function ({ extendExtension }) {
    extendExtension('webwizardsusa/code-block', (extension) =>
        extension.extend({
            addCommands() {
                return {
                    ...this.parent?.(),
                    selectCodeBlockContent:
                        () =>
                        ({ state, commands }) => {
                            const { $from } = state.selection

                            if ($from.parent.type.name !== this.name) {
                                return false
                            }

                            return commands.setTextSelection({ from: $from.start(), to: $from.end() })
                        },
                }
            },

            addKeyboardShortcuts() {
                return {
                    ...this.parent?.(),
                    'Mod-Alt-l': () => this.editor.commands.toggleCodeBlockLineNumbers(),
                }
            },
        }),
    )
}
```

The decorator receives the extension the plugin's factory built and returns the one to use. Its second argument is
`{ options, tiptap }`: the plugin's options (`languages`, `defaultLanguage`, `themes`) and the shared TipTap modules.
Keep calling `this.parent?.()` in anything you override, so the plugin's own commands, attributes and ProseMirror
plugins stay in place.

> [!IMPORTANT]
> The server keeps only the attributes the PHP node declares. An attribute you add to the node in JavaScript is lost
> when the content is saved unless the server knows it too, for example through `addGlobalAttributes()`. See the [Rich Editor Plus documentation](docs:rich-editor-plus/introduction) for global
> attributes and for building and serving a JS module.

### Subclassing

You can also extend `CodeBlockPlugin` and override its methods, such as `getTools()`. Keep the id, so the subclass
still replaces the core code block, and use your subclass for both the field and rendering.

## The highlight lines action

The **Highlight lines** tool opens the Filament action `highlightCodeLines` (`HighlightLinesAction::NAME`), registered
by the plugin's `getActions()`. In the browser, the package registers its arguments with
`registerActionArguments('highlightCodeLines', …)`, so the modal starts with the block's current lines. It is a
compact example of a tool that asks for input, if you are writing a plugin of your own; see the
[example plugin](docs:rich-editor-plus-example/next-steps).
