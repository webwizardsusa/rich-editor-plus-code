import { textblockTypeInputRule } from '@tiptap/core'
import CodeBlock, { backtickInputRegex, tildeInputRegex } from '@tiptap/extension-code-block'
import { createCodeHighlightPlugin } from './highlighter'
import { createLinesPlugin } from './lines'
import { normalizeLanguage } from './languages'

/**
 * TipTap's code block with the attributes `CodeBlockPlugin` stores on `<pre>` (`data-line-numbers`,
 * `data-highlight-lines`), Tab indenting inside the block, and Shiki colouring.
 *
 * The default language only goes to blocks made here (the commands and the ``` input rule). Parsed content keeps what
 * it has, so a stored plain text block stays plain text.
 */
export function createCodeBlock(options) {
    const defaultLanguage = normalizeLanguage(options.defaultLanguage)

    return CodeBlock.extend({
        addAttributes() {
            return {
                language: {
                    default: null,
                    parseHTML: (element) =>
                        normalizeLanguage(
                            [...(element.firstElementChild?.classList ?? [])]
                                .find((className) => className.startsWith('language-'))
                                ?.slice('language-'.length),
                        ),
                    rendered: false,
                },
                lineNumbers: {
                    default: false,
                    parseHTML: (element) => element.getAttribute('data-line-numbers') === 'true',
                    renderHTML: (attributes) => (attributes.lineNumbers ? { 'data-line-numbers': 'true' } : {}),
                },
                highlightLines: {
                    default: null,
                    parseHTML: (element) => element.getAttribute('data-highlight-lines') || null,
                    renderHTML: (attributes) => (attributes.highlightLines ? { 'data-highlight-lines': attributes.highlightLines } : {}),
                },
            }
        },

        addCommands() {
            return {
                ...this.parent?.(),
                setCodeBlock:
                    (attributes) =>
                    ({ commands }) =>
                        commands.setNode(this.name, { language: defaultLanguage, ...attributes }),
                toggleCodeBlock:
                    (attributes) =>
                    ({ commands }) =>
                        commands.toggleNode(this.name, 'paragraph', { language: defaultLanguage, ...attributes }),
                setCodeBlockLanguageLanguage:
                    (language) =>
                    ({ commands }) =>
                        commands.updateAttributes(this.name, { language: normalizeLanguage(language) }),
                toggleCodeBlockLineNumbers:
                    () =>
                    ({ editor, commands }) =>
                        commands.updateAttributes(this.name, { lineNumbers: !editor.getAttributes(this.name).lineNumbers }),
            }
        },

        addInputRules() {
            return [backtickInputRegex, tildeInputRegex].map((find) =>
                textblockTypeInputRule({
                    find,
                    type: this.type,
                    getAttributes: (match) => ({ language: normalizeLanguage(match[1]) ?? defaultLanguage }),
                }),
            )
        },

        addProseMirrorPlugins() {
            return [...(this.parent?.() ?? []), createCodeHighlightPlugin(options), createLinesPlugin()]
        },
    }).configure({
        enableTabIndentation: true,
        tabSize: 4,
    })
}
