<?php

use Webwizardsusa\RichEditorPlus\Plugins\Contracts\EditorPlugin;
use Webwizardsusa\RichEditorPlus\Plugins\Markdown\MarkdownFormat;
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

/**
 * @return array<EditorPlugin>
 */
function markdownCodePlugins(): array
{
    return PluginRegistry::mergePlugins(PluginRegistry::defaultPlugins(), [CodeBlockPlugin::make()->cache(false)]);
}

/**
 * @param  array<string, mixed>  $attrs
 */
function codeBlockMarkdown(array $attrs, string $code = 'echo 1;'): string
{
    return RichContentPlusRenderer::make(['type' => 'doc', 'content' => [['type' => 'codeBlock', 'attrs' => $attrs, 'content' => [['type' => 'text', 'text' => $code]]]]])
        ->plugins(markdownCodePlugins())
        ->toFormat(MarkdownFormat::make());
}

/**
 * @return array<string, mixed>
 */
function codeBlockFromMarkdown(string $markdown): array
{
    return RichContentPlusRenderer::make($markdown)->plugins(markdownCodePlugins())->format(MarkdownFormat::make())->toArray()['content'][0];
}

it('writes the options after the language in the fence', function (array $attrs, string $fence): void {
    expect(codeBlockMarkdown($attrs))->toBe("{$fence}\necho 1;\n```\n");
})->with([
    'language only' => [['language' => 'php'], '```php'],
    'line numbers' => [['language' => 'php', 'lineNumbers' => true], '```php showLineNumbers'],
    'highlighted lines' => [['language' => 'php', 'highlightLines' => '3,1'], '```php {1,3}'],
    'both' => [['language' => 'php', 'lineNumbers' => true, 'highlightLines' => '2'], '```php showLineNumbers {2}'],
    'options without a language' => [['lineNumbers' => true], '```text showLineNumbers'],
    'nothing' => [[], '```'],
]);

it('reads the options back from the fence', function (): void {
    $block = codeBlockFromMarkdown("```php showLineNumbers {2,4-6}\necho 1;\n```");

    expect($block['attrs'])->toBe(['language' => 'php', 'lineNumbers' => true, 'highlightLines' => '2,4-6'])
        ->and($block['content'][0]['text'])->toBe('echo 1;');
});

it('round trips a block with options and no language as plain text', function (): void {
    $markdown = codeBlockMarkdown(['lineNumbers' => true, 'highlightLines' => '1']);

    expect(codeBlockFromMarkdown($markdown)['attrs'])->toBe(['language' => null, 'lineNumbers' => true, 'highlightLines' => '1']);
});

it('fences code that holds backticks with a longer fence', function (): void {
    expect(codeBlockMarkdown(['language' => 'md'], "```\nx\n```"))->toStartWith("````md\n");
});
