import '../css/index.css'
import { createCodeBlock } from './code-block'

/**
 * The module `CodeBlockPlugin` points `JsExtension::module()` at. Rich Editor Plus imports it once per page, before it
 * builds an editor, and calls this with its shared API. The `registerExtension()` id matches
 * `CodeBlockPlugin::JS_EXTENSION`, and the factory gets the options from `CodeBlockPlugin::getJsExtension()`.
 *
 * The `@tiptap/*` imports resolve to the editor's own TipTap and ProseMirror through the `sharedTiptap()` Vite plugin,
 * so this bundle holds only this package's code, prosemirror-highlight and Shiki. Shiki's grammars and themes are
 * chunks of their own, loaded when a block first needs them.
 */
export default function ({ registerExtension, registerActionArguments }) {
    registerExtension('webwizardsusa/code-block', (options) => createCodeBlock(options))

    // The highlight lines modal starts from the lines of the block the cursor is in.
    registerActionArguments('highlightCodeLines', (editor) => ({
        highlightLines: editor.getAttributes('codeBlock').highlightLines ?? null,
    }))
}

// The editor imports the module once, so a changed module can only take effect on a fresh page.
if (import.meta.hot) {
    import.meta.hot.accept(() => window.location.reload())
}
