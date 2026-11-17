<?php

use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Support\Facades\Event;
use Webwizardsusa\RichEditorPlus\Plugins\Plugin;
use Webwizardsusa\RichEditorPlus\Rendering\RichContentPlusRenderer;
use Webwizardsusa\RichEditorPlus\Support\PluginRegistry;
use Webwizardsusa\RichEditorPlusCode\CodeBlockPlugin;
use Webwizardsusa\RichEditorPlusCode\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    PluginRegistry::flush();
    Plugin::flushConfigurations();
});

function renderCode(string $html, ?CodeBlockPlugin $plugin = null): string
{
    return RichContentPlusRenderer::make($html)
        ->plugins(PluginRegistry::mergePlugins(PluginRegistry::defaultPlugins(), [$plugin ?? CodeBlockPlugin::make()->cache(false)]))
        ->toHtml();
}

it('highlights a code block with both themes', function (): void {
    $html = renderCode('<p>Before</p><pre><code class="language-php">&lt;?php echo 1;</code></pre>');

    expect($html)->toStartWith('<p>Before</p><div class="rep-code" data-language="php"><pre class="phiki language-php github-light phiki-themes github-dark"')
        ->toContain('--phiki-dark-color')
        ->toContain('>echo</span>')
        ->not->toContain('<code class="language-php">');
});

it('keeps the code escaped', function (): void {
    $html = renderCode('<pre><code class="language-html">&lt;script&gt;alert(1)&lt;/script&gt;</code></pre>');

    expect($html)->not->toContain('<script>')
        ->toContain('&lt;')
        ->toContain('script');
});

it('adds line numbers and highlights lines', function (): void {
    $html = renderCode("<pre data-line-numbers=\"true\" data-highlight-lines=\"2-3\"><code class=\"language-php\">\$a = 1;\n\$b = 2;\n\$c = 3;\n\$d = 4;</code></pre>");

    expect(substr_count($html, 'class="line-number"'))->toBe(4)
        ->and(substr_count($html, 'class="line highlighted"'))->toBe(2);
});

it('renders a language without a grammar, or none, as plain text', function (string $code): void {
    $html = renderCode($code);

    expect($html)->toContain('<pre class="phiki github-light phiki-themes github-dark"')
        ->toContain('a &lt; b');
})->with([
    'unknown language' => ['<pre><code class="language-nosuchlanguage">a &lt; b</code></pre>'],
    'no language' => ['<pre><code>a &lt; b</code></pre>'],
]);

it('highlights every block in every document of a batch', function (): void {
    $html = RichContentPlusRenderer::many([
        'a' => '<pre><code class="language-php">$a;</code></pre><p>x</p><pre><code class="language-sql">select 1</code></pre>',
        'b' => '<p>No code</p>',
    ])->plugins(PluginRegistry::mergePlugins(PluginRegistry::defaultPlugins(), [CodeBlockPlugin::make()->cache(false)]))->toHtml();

    expect(substr_count($html['a'], '<div class="rep-code"'))->toBe(2)
        ->and($html['b'])->toBe('<p>No code</p>');
});

it('renders highlighted HTML that stays out of storage', function (): void {
    $html = '<pre><code class="language-php">$a;</code></pre>';

    expect(RichContentPlusRenderer::make($html)->plugins(PluginRegistry::mergePlugins(PluginRegistry::defaultPlugins(), [CodeBlockPlugin::make()]))->toStorableHtml())
        ->toBe($html);
});

it('caches each highlighted block', function (): void {
    Event::fake([CacheHit::class, CacheMissed::class]);

    $html = '<pre><code class="language-php">$a;</code></pre>';
    $plugin = CodeBlockPlugin::make()->cache(store: 'array');

    $first = renderCode($html, $plugin);

    Event::assertDispatchedTimes(CacheMissed::class, 1);
    Event::assertNotDispatched(CacheHit::class);

    expect(renderCode($html, $plugin))->toBe($first);

    Event::assertDispatchedTimes(CacheHit::class, 1);
    Event::assertDispatchedTimes(CacheMissed::class, 1);
});

it('highlights without the cache when it is off', function (): void {
    Event::fake([CacheHit::class, CacheMissed::class]);

    renderCode('<pre><code class="language-php">$a;</code></pre>', CodeBlockPlugin::make()->cache(false));

    Event::assertNotDispatched(CacheMissed::class);
});

it('uses the themes it was given', function (): void {
    expect(renderCode('<pre><code class="language-php">$a;</code></pre>', CodeBlockPlugin::make()->themes('one-light', 'one-dark-pro')->cache(false)))
        ->toContain('phiki language-php one-light phiki-themes one-dark-pro');
});
