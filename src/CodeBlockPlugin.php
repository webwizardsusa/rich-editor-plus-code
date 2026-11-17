<?php

namespace Webwizardsusa\RichEditorPlusCode;

use DOMElement;
use InvalidArgumentException;
use Webwizardsusa\RichEditorPlus\Plugins\Contracts\TransformsRenderedHtml;
use Webwizardsusa\RichEditorPlus\Plugins\Core\CodeBlockPlugin as CoreCodeBlockPlugin;
use Webwizardsusa\RichEditorPlus\Plugins\Markdown\Contracts\ProvidesMarkdownRules;
use Webwizardsusa\RichEditorPlus\Plugins\Markdown\Serializing\MarkdownWriter;
use Webwizardsusa\RichEditorPlus\Plugins\Markdown\Serializing\NodeRule;
use Webwizardsusa\RichEditorPlus\Support\JsExtension;
use Webwizardsusa\RichEditorPlus\Support\Tool;
use Webwizardsusa\RichEditorPlus\Support\ToolMenuItem;
use Webwizardsusa\RichEditorPlusCode\Actions\HighlightLinesAction;
use Webwizardsusa\RichEditorPlusCode\Rendering\CodeHighlighter;
use Webwizardsusa\RichEditorPlusCode\Support\LineRanges;

/**
 * Code blocks with a language picker, line numbers and highlighted lines. The editor colours code with Shiki, and
 * rendered content is highlighted on the server with Phiki, using the same grammars and themes.
 *
 * It keeps the core plugin's id, so adding it replaces the default code block, and content stored before keeps
 * working. Add it with `->addPlugins([CodeBlockPlugin::make()])`, or everywhere with
 * `RichEditorPlus::registerDefaultPlugins([CodeBlockPlugin::class])`, and render with the same plugin.
 */
class CodeBlockPlugin extends CoreCodeBlockPlugin implements ProvidesMarkdownRules, TransformsRenderedHtml
{
    public const string JS_EXTENSION = 'webwizardsusa/code-block';

    /**
     * The languages offered when none are given: ids Shiki and Phiki both know, with their labels.
     *
     * @var array<string, string>
     */
    public const array DEFAULT_LANGUAGES = [
        'bash' => 'Bash',
        'blade' => 'Blade',
        'c' => 'C',
        'cpp' => 'C++',
        'csharp' => 'C#',
        'css' => 'CSS',
        'diff' => 'Diff',
        'dockerfile' => 'Dockerfile',
        'go' => 'Go',
        'graphql' => 'GraphQL',
        'html' => 'HTML',
        'java' => 'Java',
        'javascript' => 'JavaScript',
        'json' => 'JSON',
        'jsx' => 'JSX',
        'kotlin' => 'Kotlin',
        'markdown' => 'Markdown',
        'php' => 'PHP',
        'python' => 'Python',
        'ruby' => 'Ruby',
        'rust' => 'Rust',
        'scss' => 'SCSS',
        'sql' => 'SQL',
        'swift' => 'Swift',
        'tsx' => 'TSX',
        'typescript' => 'TypeScript',
        'vue' => 'Vue',
        'xml' => 'XML',
        'yaml' => 'YAML',
    ];

    /**
     * @var list<string>
     */
    protected const array PLAIN_TEXT = ['text', 'txt', 'plaintext', 'plain'];

    /**
     * @var array<string, string>
     */
    protected array $languages = self::DEFAULT_LANGUAGES;

    protected ?string $defaultLanguage = null;

    /**
     * @var array{light: string, dark: string}
     */
    protected array $themes = ['light' => 'github-light', 'dark' => 'github-dark'];

    protected bool $isCached = true;

    protected ?string $cacheStore = null;

    protected ?CodeHighlighter $highlighter = null;

    /**
     * The languages the picker offers, as `id => label` (or a list of ids, labelled with the id). Content may still
     * hold other languages, from Markdown or older content; they render if Phiki knows them.
     *
     * @param  array<string, string>|list<string>  $languages
     */
    public function languages(array $languages): static
    {
        $normalized = [];

        foreach ($languages as $id => $label) {
            [$id, $label] = is_int($id) ? [$label, $label] : [$id, $label];

            if (static::normalizeLanguage($id) !== $id) {
                throw new InvalidArgumentException("[{$id}] is not a valid language id.");
            }

            $normalized[$id] = $label;
        }

        $this->languages = $normalized;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getLanguages(): array
    {
        return $this->languages;
    }

    /**
     * The language a new code block starts with. Without one, new blocks are plain text.
     */
    public function defaultLanguage(?string $language): static
    {
        if ($language !== null && ! array_key_exists($language, $this->languages)) {
            throw new InvalidArgumentException("The default language [{$language}] is not one of the plugin's languages.");
        }

        $this->defaultLanguage = $language;

        return $this;
    }

    public function getDefaultLanguage(): ?string
    {
        return $this->defaultLanguage;
    }

    /**
     * The Shiki/Phiki themes for light and dark mode, such as `github-light` and `github-dark`.
     */
    public function themes(string $light, string $dark): static
    {
        $this->themes = ['light' => $light, 'dark' => $dark];
        $this->highlighter = null;

        return $this;
    }

    /**
     * @return array{light: string, dark: string}
     */
    public function getThemes(): array
    {
        return $this->themes;
    }

    /**
     * Whether highlighted blocks are kept in Laravel's cache, and in which store (the default one when null).
     */
    public function cache(bool $condition = true, ?string $store = null): static
    {
        $this->isCached = $condition;
        $this->cacheStore = $store;
        $this->highlighter = null;

        return $this;
    }

    public function getNodes(): array
    {
        $info = static fn (DOMElement $element): string => $element->firstElementChild?->getAttribute('data-info') ?? '';

        return [
            parent::getNodes()[0]
                ->attribute(
                    'language',
                    parse: static fn (DOMElement $element): ?string => static::parseLanguage(
                        preg_match('/(?:^|\s)language-(\S+)/', $element->firstElementChild?->getAttribute('class') ?? '', $matches) === 1 ? $matches[1] : null,
                    ),
                    render: static fn (): array => [],
                )
                ->attribute(
                    'lineNumbers',
                    parse: static fn (DOMElement $element): bool => $element->getAttribute('data-line-numbers') === 'true'
                        || preg_match('/(?:^|\s)showLineNumbers(?:\s|$)/', $info($element)) === 1,
                    render: static fn (array $attrs): array => ($attrs['lineNumbers'] ?? false) === true ? ['data-line-numbers' => 'true'] : [],
                    default: false,
                )
                ->attribute(
                    'highlightLines',
                    parse: static fn (DOMElement $element): ?string => LineRanges::normalize(
                        $element->hasAttribute('data-highlight-lines')
                            ? $element->getAttribute('data-highlight-lines')
                            : (preg_match('/\{([^}]*)\}/', $info($element), $matches) === 1 ? $matches[1] : null),
                    ),
                    render: static function (array $attrs): array {
                        $lines = LineRanges::normalize($attrs['highlightLines'] ?? null);

                        return $lines !== null ? ['data-highlight-lines' => $lines] : [];
                    },
                ),
        ];
    }

    public function getJsExtension(): JsExtension
    {
        return JsExtension::make(self::JS_EXTENSION, [
            'languages' => array_keys($this->languages),
            'defaultLanguage' => $this->defaultLanguage,
            'themes' => $this->themes,
        ])->module(RichEditorPlusCodeServiceProvider::moduleUrl());
    }

    public function getTools(): array
    {
        return [
            Tool::make('codeBlockLanguage')
                ->label(__('rich-editor-plus-code::plugin.tools.language'))
                ->icon('mdi-code-tags')
                ->currentLabel(__('rich-editor-plus-code::plugin.tools.language_current'))
                ->menu([
                    $this->languageItem(null, __('rich-editor-plus-code::plugin.plain_text')),
                    ...array_map($this->languageItem(...), array_keys($this->languages), $this->languages),
                ])
                ->block()
                ->nodes(['codeBlock'])
                ->showWithCursor()
                ->group('code')
                ->order(10),
            Tool::make('toggleCodeBlockLineNumbers')
                ->label(__('rich-editor-plus-code::plugin.tools.line_numbers'))
                ->icon('mdi-format-list-numbered')
                ->command('toggleCodeBlockLineNumbers')
                ->active('codeBlock', ['lineNumbers' => true])
                ->block()
                ->nodes(['codeBlock'])
                ->showWithCursor()
                ->group('code')
                ->order(20),
            Tool::make('highlightCodeLines')
                ->label(__('rich-editor-plus-code::plugin.tools.highlight_lines'))
                ->icon('mdi-format-line-style')
                ->action(HighlightLinesAction::NAME)
                ->block()
                ->nodes(['codeBlock'])
                ->showWithCursor()
                ->group('code')
                ->order(30),
        ];
    }

    public function getActions(): array
    {
        return [HighlightLinesAction::make()];
    }

    public function transformRenderedHtml(array $htmls): array
    {
        return array_map($this->getHighlighter()->transform(...), $htmls);
    }

    /**
     * A fence with the language and the options after it, as in ```` ```php showLineNumbers {2,4-6} ````, which the
     * Markdown parser hands back as the block's info string.
     */
    public function getMarkdownNodeRules(): array
    {
        return [
            'codeBlock' => NodeRule::using(static function (array $node, MarkdownWriter $writer): void {
                $code = implode('', array_map(static fn (array $text): string => (string) ($text['text'] ?? ''), $node['content'] ?? []));
                $attrs = is_array($node['attrs'] ?? null) ? $node['attrs'] : [];
                $lines = LineRanges::normalize($attrs['highlightLines'] ?? null);
                $language = static::normalizeLanguage($attrs['language'] ?? null);

                $info = implode(' ', array_filter([
                    // A fence's first word is its language, so options without one need a placeholder.
                    $language ?? (($attrs['lineNumbers'] ?? false) === true || $lines !== null ? 'text' : null),
                    ($attrs['lineNumbers'] ?? false) === true ? 'showLineNumbers' : null,
                    $lines !== null ? "{{$lines}}" : null,
                ]));

                $fence = str_repeat('`', max(3, static::longestBacktickRun($code) + 1));

                $writer->write($fence.$info."\n".($code === '' ? '' : $code."\n").$fence);
            }),
        ];
    }

    public function getMarkdownMarkRules(): array
    {
        return [];
    }

    /**
     * The language from a `language-*` class, with the names for plain text (`text`, `txt`, `plaintext`) read as none,
     * which is how the editor stores plain text.
     */
    public static function parseLanguage(?string $language): ?string
    {
        $language = static::normalizeLanguage($language);

        return in_array(strtolower((string) $language), self::PLAIN_TEXT, true) ? null : $language;
    }

    protected function languageItem(?string $id, string $label): ToolMenuItem
    {
        return ToolMenuItem::make($id ?? 'text')
            ->label($label)
            ->swatch('rep-code-language-swatch', $label)
            ->command('setCodeBlockLanguage', [$id])
            ->active('codeBlock', ['language' => $id])
            // Content can hold a language the picker doesn't offer, which then shows as plain text.
            ->fallback($id === null);
    }

    protected function getHighlighter(): CodeHighlighter
    {
        return $this->highlighter ??= new CodeHighlighter($this->themes, $this->isCached, $this->cacheStore);
    }

    protected static function longestBacktickRun(string $text): int
    {
        $longest = 0;

        foreach (preg_split('/[^`]+/', $text, flags: PREG_SPLIT_NO_EMPTY) ?: [] as $run) {
            $longest = max($longest, strlen($run));
        }

        return $longest;
    }
}
