/**
 * The grammars and themes the editor can colour with, each loaded the first time a block needs it. They cover
 * `CodeBlockPlugin::DEFAULT_LANGUAGES` and a set of popular themes; Vite builds each into a chunk of its own. A language
 * or theme outside these still renders highlighted on the server (Phiki knows them all), but shows plain in the editor.
 */
export const languages = {
    bash: () => import('shiki/langs/bash.mjs'),
    blade: () => import('shiki/langs/blade.mjs'),
    c: () => import('shiki/langs/c.mjs'),
    cpp: () => import('shiki/langs/cpp.mjs'),
    csharp: () => import('shiki/langs/csharp.mjs'),
    css: () => import('shiki/langs/css.mjs'),
    diff: () => import('shiki/langs/diff.mjs'),
    dockerfile: () => import('shiki/langs/dockerfile.mjs'),
    go: () => import('shiki/langs/go.mjs'),
    graphql: () => import('shiki/langs/graphql.mjs'),
    html: () => import('shiki/langs/html.mjs'),
    java: () => import('shiki/langs/java.mjs'),
    javascript: () => import('shiki/langs/javascript.mjs'),
    json: () => import('shiki/langs/json.mjs'),
    jsx: () => import('shiki/langs/jsx.mjs'),
    kotlin: () => import('shiki/langs/kotlin.mjs'),
    markdown: () => import('shiki/langs/markdown.mjs'),
    php: () => import('shiki/langs/php.mjs'),
    python: () => import('shiki/langs/python.mjs'),
    ruby: () => import('shiki/langs/ruby.mjs'),
    rust: () => import('shiki/langs/rust.mjs'),
    scss: () => import('shiki/langs/scss.mjs'),
    sql: () => import('shiki/langs/sql.mjs'),
    swift: () => import('shiki/langs/swift.mjs'),
    tsx: () => import('shiki/langs/tsx.mjs'),
    typescript: () => import('shiki/langs/typescript.mjs'),
    vue: () => import('shiki/langs/vue.mjs'),
    xml: () => import('shiki/langs/xml.mjs'),
    yaml: () => import('shiki/langs/yaml.mjs'),
}

/**
 * Other names content may use for those languages, such as `js` from a Markdown fence.
 */
export const aliases = {
    'c#': 'csharp',
    'c++': 'cpp',
    cs: 'csharp',
    docker: 'dockerfile',
    htm: 'html',
    js: 'javascript',
    kt: 'kotlin',
    md: 'markdown',
    py: 'python',
    rb: 'ruby',
    rs: 'rust',
    sh: 'bash',
    shell: 'bash',
    ts: 'typescript',
    yml: 'yaml',
    zsh: 'bash',
}

export const themes = {
    'catppuccin-latte': () => import('shiki/themes/catppuccin-latte.mjs'),
    'catppuccin-mocha': () => import('shiki/themes/catppuccin-mocha.mjs'),
    dracula: () => import('shiki/themes/dracula.mjs'),
    'github-dark': () => import('shiki/themes/github-dark.mjs'),
    'github-dark-default': () => import('shiki/themes/github-dark-default.mjs'),
    'github-dark-dimmed': () => import('shiki/themes/github-dark-dimmed.mjs'),
    'github-light': () => import('shiki/themes/github-light.mjs'),
    'github-light-default': () => import('shiki/themes/github-light-default.mjs'),
    'material-theme-darker': () => import('shiki/themes/material-theme-darker.mjs'),
    'material-theme-lighter': () => import('shiki/themes/material-theme-lighter.mjs'),
    'min-dark': () => import('shiki/themes/min-dark.mjs'),
    'min-light': () => import('shiki/themes/min-light.mjs'),
    nord: () => import('shiki/themes/nord.mjs'),
    'one-dark-pro': () => import('shiki/themes/one-dark-pro.mjs'),
    'one-light': () => import('shiki/themes/one-light.mjs'),
    'solarized-dark': () => import('shiki/themes/solarized-dark.mjs'),
    'solarized-light': () => import('shiki/themes/solarized-light.mjs'),
    'vitesse-dark': () => import('shiki/themes/vitesse-dark.mjs'),
    'vitesse-light': () => import('shiki/themes/vitesse-light.mjs'),
}

const plainText = ['text', 'txt', 'plaintext', 'plain']

/**
 * The language id the editor stores for a name from content: aliases resolved, plain text as null. Must match
 * `CodeBlockPlugin::parseLanguage()` for the names that matter.
 */
export function normalizeLanguage(language) {
    if (typeof language !== 'string' || !/^[A-Za-z0-9_+#-]{1,32}$/.test(language)) {
        return null
    }

    return plainText.includes(language.toLowerCase()) ? null : language
}

/**
 * The grammar to colour a language with, or null when the editor has none.
 */
export function grammarFor(language) {
    const key = language?.toLowerCase()

    if (!key) {
        return null
    }

    const name = aliases[key] ?? key

    return name in languages ? name : null
}
