<?php

use Illuminate\Support\Facades\Blade;
use Webwizardsusa\RichEditorPlusCode\Tests\TestCase;

uses(TestCase::class);

it('prints the styles and the copy button script', function (): void {
    $html = Blade::render('<x-rich-editor-plus-code::assets />');

    expect($html)->toContain('<style>')
        ->toContain('.dark .rep-code .phiki')
        ->toContain('rep-code-copy')
        ->toContain('<script>');
});

it('leaves the copy button out when asked', function (): void {
    expect(Blade::render('<x-rich-editor-plus-code::assets :copy="false" />'))->not->toContain('<script>');
});

it('follows the system dark mode, or none', function (): void {
    expect(Blade::render('<x-rich-editor-plus-code::assets dark="media" />'))->toContain('@media (prefers-color-scheme:dark)')
        ->and(Blade::render('<x-rich-editor-plus-code::assets dark="none" />'))->not->toContain('--phiki-dark-color');
});
