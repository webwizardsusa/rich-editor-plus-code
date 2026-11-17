<?php

namespace Webwizardsusa\RichEditorPlusCode\Rendering;

use Composer\InstalledVersions;
use Illuminate\Support\Facades\Cache;
use Phiki\Grammar\Grammar;
use Phiki\Phast\ClassList;
use Phiki\Phiki;
use Phiki\Transformers\Decorations\LineDecoration;
use Webwizardsusa\RichEditorPlus\Plugins\Core\CodeBlockPlugin as CoreCodeBlockPlugin;
use Webwizardsusa\RichEditorPlusCode\CodeBlockPlugin;
use Webwizardsusa\RichEditorPlusCode\Support\LineRanges;

/**
 * Highlights the code blocks in rendered HTML with Phiki. Each `<pre><code>` the serializer wrote becomes Phiki's
 * `<pre>`, coloured with both themes, inside a `<div class="rep-code">`. Highlighted HTML is cached per block, keyed on
 * everything that changes it.
 *
 * @internal Used by {@see CodeBlockPlugin}.
 */
class CodeHighlighter
{
    /**
     * A code block as the serializer writes it. Code blocks hold only text, so the content has no tags of its own.
     */
    protected const string PATTERN = '#<pre\b([^>]*)><code\b([^>]*)>(.*?)</code></pre>#s';

    protected ?Phiki $phiki = null;

    /**
     * @param  array{light: string, dark: string}  $themes
     */
    public function __construct(
        protected array $themes,
        protected bool $cache = true,
        protected ?string $cacheStore = null,
        protected int $cacheSeconds = 2_592_000,
    ) {}

    public function transform(string $html): string
    {
        if (! str_contains($html, '<pre')) {
            return $html;
        }

        return (string) preg_replace_callback(self::PATTERN, function (array $matches): string {
            $language = CoreCodeBlockPlugin::normalizeLanguage(
                preg_match('/\bclass="[^"]*\blanguage-([^\s"]+)/', $matches[2], $class) === 1 ? html_entity_decode($class[1], ENT_QUOTES | ENT_HTML5) : null,
            );

            return $this->highlight(
                html_entity_decode($matches[3], ENT_QUOTES | ENT_HTML5),
                $language,
                str_contains($matches[1], 'data-line-numbers="true"'),
                LineRanges::parse(preg_match('/\bdata-highlight-lines="([^"]*)"/', $matches[1], $lines) === 1 ? $lines[1] : null),
            );
        }, $html);
    }

    /**
     * @param  list<array{int, int}>  $highlightedLines  1-based, inclusive.
     */
    public function highlight(string $code, ?string $language, bool $lineNumbers = false, array $highlightedLines = []): string
    {
        $grammar = $language !== null && $this->getPhiki()->environment()->grammars->has(strtolower($language))
            ? strtolower($language)
            : Grammar::Txt->value;

        $render = function () use ($code, $grammar, $lineNumbers, $highlightedLines): string {
            $output = $this->getPhiki()->codeToHtml($code, $grammar, $this->themes)->withGutter($lineNumbers);

            foreach ($highlightedLines as [$first, $last]) {
                // Phiki counts lines from 0.
                $output->decoration(new LineDecoration([$first - 1, $last - 1], (new ClassList)->add('highlighted')));
            }

            return $output->toString();
        };

        $highlighted = $this->cache
            ? Cache::store($this->cacheStore)->remember($this->cacheKey($code, $grammar, $lineNumbers, $highlightedLines), $this->cacheSeconds, $render)
            : $render();

        $attributes = $language !== null ? ' data-language="'.e($language).'"' : '';

        return "<div class=\"rep-code\"{$attributes}>{$highlighted}</div>";
    }

    /**
     * @param  list<array{int, int}>  $highlightedLines
     */
    protected function cacheKey(string $code, string $grammar, bool $lineNumbers, array $highlightedLines): string
    {
        return 'rich-editor-plus-code:'.hash('xxh128', serialize([
            $code,
            $grammar,
            $this->themes,
            $lineNumbers,
            $highlightedLines,
            InstalledVersions::getVersion('phiki/phiki'),
        ]));
    }

    protected function getPhiki(): Phiki
    {
        return $this->phiki ??= new Phiki;
    }
}
