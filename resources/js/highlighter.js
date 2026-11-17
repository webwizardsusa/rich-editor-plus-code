import { createHighlightPlugin } from 'prosemirror-highlight'
import { createParser } from 'prosemirror-highlight/shiki'
import { createHighlighterCore } from 'shiki/core'
import { createJavaScriptRegexEngine } from 'shiki/engine/javascript'
import { grammarFor, languages, themes } from './languages'

const fallbackThemes = { light: 'github-light', dark: 'github-dark' }

/**
 * Shiki colouring for code blocks, as ProseMirror decorations, so the document itself never changes. The highlighter
 * and each grammar load on first use; while one loads, the block shows plain and is coloured once it arrives.
 * Colours use both themes: `color` for light and `--shiki-dark` for dark, picked by the stylesheet.
 */
export function createCodeHighlightPlugin(options) {
    const themeNames = Object.fromEntries(
        Object.entries(fallbackThemes).map(([mode, fallback]) => {
            const name = options.themes?.[mode]

            if (name && !(name in themes)) {
                console.warn(`[rich-editor-plus-code] The editor has no [${name}] theme, so it uses [${fallback}].`)
            }

            return [mode, name in themes ? name : fallback]
        }),
    )

    let highlighter = null
    let highlighterPromise = null
    let parser = null
    const loading = new Map()
    const failed = new Set()

    const loadHighlighter = () =>
        (highlighterPromise ??= createHighlighterCore({
            themes: [themes[themeNames.light](), themes[themeNames.dark]()],
            langs: [],
            engine: createJavaScriptRegexEngine(),
        }).then((loaded) => {
            highlighter = loaded
        }))

    const loadGrammar = (grammar) => {
        if (!loading.has(grammar)) {
            loading.set(
                grammar,
                highlighter.loadLanguage(languages[grammar]()).catch((error) => {
                    // Without this, the block would ask for the grammar again on every refresh.
                    failed.add(grammar)
                    console.error(`[rich-editor-plus-code] Could not load the [${grammar}] grammar.`, error)
                }),
            )
        }

        return loading.get(grammar)
    }

    return createHighlightPlugin({
        languageExtractor: (node) => grammarFor(node.attrs.language) ?? undefined,
        parser: (parserOptions) => {
            if (!parserOptions.language || failed.has(parserOptions.language)) {
                return []
            }

            if (!highlighter) {
                return loadHighlighter()
            }

            if (!highlighter.getLoadedLanguages().includes(parserOptions.language)) {
                return loadGrammar(parserOptions.language)
            }

            parser ??= createParser(highlighter, { themes: themeNames, defaultColor: 'light' })

            return parser(parserOptions)
        },
    })
}
