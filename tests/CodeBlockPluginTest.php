<?php

use Webwizardsusa\RichEditorPlus\Plugins\Contracts\EditorPlugin;
use Webwizardsusa\RichEditorPlus\Plugins\Core\CodeBlockPlugin as CoreCodeBlockPlugin;
use Webwizardsusa\RichEditorPlus\Plugins\Plugin;
use Webwizardsusa\RichEditorPlus\Rendering\RichContentPlusRenderer;
use Webwizardsusa\RichEditorPlus\Support\PluginRegistry;
use Webwizardsusa\RichEditorPlus\Support\Tool;
use Webwizardsusa\RichEditorPlusCode\CodeBlockPlugin;
use Webwizardsusa\RichEditorPlusCode\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    PluginRegistry::flush();
    Plugin::flushConfigurations();
});

/**
 * @return array<EditorPlugin>
 */
function codeBlockPlugins(?CodeBlockPlugin $plugin = null): array
{
    return PluginRegistry::mergePlugins(PluginRegistry::defaultPlugins(), [$plugin ?? CodeBlockPlugin::make()->cache(false)]);
}

/**
 * @return array<string, mixed>
 */
function codeBlockFrom(string $html): array
{
    return RichContentPlusRenderer::make($html)->plugins(codeBlockPlugins())->parseHtml()->toArray()['content'][0];
}

it('replaces the core code block', function (): void {
    $plugins = PluginRegistry::make(codeBlockPlugins())->getPlugins();

    expect($plugins[CoreCodeBlockPlugin::make()->getId()])->toBeInstanceOf(CodeBlockPlugin::class)
        ->and(collect($plugins)->filter(fn ($plugin): bool => $plugin instanceof CoreCodeBlockPlugin))->toHaveCount(1);
});

it('parses and stores the language, line numbers and highlighted lines', function (): void {
    $html = '<pre data-line-numbers="true" data-highlight-lines="4-6,2"><code class="language-php">echo 1;</code></pre>';

    expect(codeBlockFrom($html)['attrs'])->toBe(['language' => 'php', 'lineNumbers' => true, 'highlightLines' => '2,4-6'])
        ->and(RichContentPlusRenderer::make($html)->plugins(codeBlockPlugins())->parseHtml()->toStorableHtml())
        ->toBe('<pre data-line-numbers="true" data-highlight-lines="2,4-6"><code class="language-php">echo 1;</code></pre>');
});

it('stores a block without options as the core plugin did', function (): void {
    $html = '<pre><code class="language-js">x</code></pre>';

    expect(RichContentPlusRenderer::make($html)->plugins(codeBlockPlugins())->parseHtml()->toStorableHtml())->toBe($html)
        ->and(codeBlockFrom($html)['attrs'])->toBe(['language' => 'js', 'lineNumbers' => false, 'highlightLines' => null]);
});

it('drops highlighted lines it cannot read', function (): void {
    expect(codeBlockFrom('<pre data-highlight-lines="two"><code>x</code></pre>')['attrs']['highlightLines'])->toBeNull();
});

it('reads plain text language names as no language', function (string $name): void {
    expect(codeBlockFrom("<pre><code class=\"language-{$name}\">x</code></pre>")['attrs']['language'])->toBeNull();
})->with(['text', 'txt', 'plaintext', 'Plain']);

it('offers the configured languages in the language menu, after plain text', function (): void {
    $tool = collect(CodeBlockPlugin::make()->languages(['php' => 'PHP', 'sql'])->getTools())
        ->first(fn (Tool $tool): bool => $tool->getName() === 'codeBlockLanguage');

    $items = collect($tool->getMenu());

    expect($items->map->getName()->all())->toBe(['text', 'php', 'sql'])
        ->and($items->map->getLabel()->all())->toBe(['Plain text', 'PHP', 'sql'])
        ->and($items->map->getArguments()->all())->toBe([[null], ['php'], ['sql']])
        ->and($items->first()->isFallback())->toBeTrue();
});

it('refuses a language id that could not be stored', function (): void {
    CodeBlockPlugin::make()->languages(['php"><script>' => 'PHP']);
})->throws(InvalidArgumentException::class);

it('refuses a default language it does not offer', function (): void {
    CodeBlockPlugin::make()->languages(['php' => 'PHP'])->defaultLanguage('sql');
})->throws(InvalidArgumentException::class);

it('sends the languages, default language and themes to the editor', function (): void {
    $options = CodeBlockPlugin::make()
        ->languages(['php' => 'PHP', 'sql' => 'SQL'])
        ->defaultLanguage('php')
        ->themes('one-light', 'one-dark-pro')
        ->getJsExtension()
        ->toArray();

    expect($options['id'])->toBe(CodeBlockPlugin::JS_EXTENSION)
        ->and($options['options'])->toBe([
            'languages' => ['php', 'sql'],
            'defaultLanguage' => 'php',
            'themes' => ['light' => 'one-light', 'dark' => 'one-dark-pro'],
        ]);
});

it('defines in the editor every command its tools run', function (): void {
    $script = file_get_contents(dirname(__DIR__).'/resources/js/code-block.js');
    $tools = CodeBlockPlugin::make()->getTools();

    $commands = collect($tools)
        ->flatMap(fn (Tool $tool): array => [$tool->getCommand(), ...array_map(fn ($item): ?string => $item->getCommand(), $tool->getMenu() ?? [])])
        ->filter()
        ->reject(fn (string $command): bool => $command === 'updateAttributes')
        ->unique();

    expect($commands->all())->not->toBeEmpty();

    foreach ($commands as $command) {
        expect($script)->toMatch("/\\b{$command}:\\s/");
    }
});
