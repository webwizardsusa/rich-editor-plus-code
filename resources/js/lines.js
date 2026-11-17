import { Plugin, PluginKey } from '@tiptap/pm/state'
import { Decoration, DecorationSet } from '@tiptap/pm/view'

export const linesPluginKey = new PluginKey('repCodeLines')

/**
 * `2,4-6` as `[first, last]` pairs (1-based, inclusive). Anything else gives none; the server normalizes stored values.
 */
export function parseLineRanges(lines) {
    if (typeof lines !== 'string') {
        return []
    }

    return lines
        .split(',')
        .map((part) => part.split('-').map((bound) => Number.parseInt(bound, 10)))
        .filter(([first, last = first]) => first >= 1 && last >= first)
        .map(([first, last = first]) => [first, Math.min(last, 10000)])
}

/**
 * Where each line of a code block starts, and its length, from the block's text.
 */
function lineSpans(node, pos) {
    const spans = []
    let from = pos + 1

    for (const line of node.textContent.split('\n')) {
        spans.push({ from, to: from + line.length })
        from += line.length + 1
    }

    return spans
}

function decorate(doc) {
    const decorations = []

    doc.descendants((node, pos) => {
        if (node.type.name !== 'codeBlock') {
            return
        }

        const spans = lineSpans(node, pos)

        if (node.attrs.lineNumbers) {
            decorations.push(Decoration.node(pos, pos + node.nodeSize, { class: 'rep-code-numbered' }))

            spans.forEach(({ from }, index) => {
                decorations.push(
                    Decoration.widget(
                        from,
                        () => {
                            const number = document.createElement('span')
                            number.className = 'rep-code-line-number'
                            number.textContent = String(index + 1)
                            number.contentEditable = 'false'

                            return number
                        },
                        { key: `line-number-${index + 1}`, side: -20, ignoreSelection: true },
                    ),
                )
            })
        }

        for (const [first, last] of parseLineRanges(node.attrs.highlightLines)) {
            spans.slice(first - 1, last).forEach(({ from, to }) => {
                // An empty line has no text to mark, so a widget stands in for its highlight.
                decorations.push(
                    to > from
                        ? Decoration.inline(from, to, { class: 'rep-code-highlighted' })
                        : Decoration.widget(from, () => Object.assign(document.createElement('span'), { className: 'rep-code-highlighted rep-code-empty-line' }), { side: 1 }),
                )
            })
        }

        return false
    })

    return DecorationSet.create(doc, decorations)
}

/**
 * Line numbers and highlighted lines in the editor, drawn as decorations from the block's `lineNumbers` and
 * `highlightLines` attributes.
 */
export function createLinesPlugin() {
    return new Plugin({
        key: linesPluginKey,
        state: {
            init: (_, { doc }) => decorate(doc),
            apply: (tr, decorations) => (tr.docChanged ? decorate(tr.doc) : decorations),
        },
        props: {
            decorations: (state) => linesPluginKey.getState(state),
        },
    })
}
