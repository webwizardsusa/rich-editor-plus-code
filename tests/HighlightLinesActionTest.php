<?php

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Testing\TestAction;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Livewire\Component;
use Livewire\Livewire;
use Webwizardsusa\RichEditorPlus\Plugins\Plugin;
use Webwizardsusa\RichEditorPlus\RichEditorPlus;
use Webwizardsusa\RichEditorPlus\Support\PluginRegistry;
use Webwizardsusa\RichEditorPlusCode\CodeBlockPlugin;
use Webwizardsusa\RichEditorPlusCode\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    PluginRegistry::flush();
    Plugin::flushConfigurations();
});

class HighlightLinesTestForm extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([RichEditorPlus::make('content')->addPlugins([CodeBlockPlugin::make()])])
            ->statePath('data');
    }

    public function render(): string
    {
        return '<div>{{ $this->form }}</div>';
    }
}

function highlightLinesAction(): TestAction
{
    return TestAction::make('highlightCodeLines')->schemaComponent('content');
}

it('starts with the block lines the editor sends', function (): void {
    Livewire::test(HighlightLinesTestForm::class)
        ->mountAction(highlightLinesAction(), arguments: ['highlightLines' => '4-6,2', 'editorSelection' => ['type' => 'text', 'anchor' => 1, 'head' => 1]])
        ->assertActionDataSet(['lines' => '2,4-6']);
});

it('sets the normalized lines on the code block', function (?string $lines, ?string $stored): void {
    $selection = ['type' => 'text', 'anchor' => 1, 'head' => 1];

    Livewire::test(HighlightLinesTestForm::class)
        ->callAction(highlightLinesAction(), data: ['lines' => $lines], arguments: ['editorSelection' => $selection])
        ->assertHasNoActionErrors()
        ->assertDispatched('run-rich-editor-plus-commands', fn (string $event, array $params): bool => $params['key'] === 'form.content'
            && $params['editorSelection'] === $selection
            && $params['commands'] === [
                ['name' => 'updateAttributes', 'arguments' => ['codeBlock', ['highlightLines' => $stored]]],
            ]);
})->with([
    'lines' => ['3, 1-2', '1-3'],
    'none' => ['', null],
]);

it('refuses lines it cannot read', function (string $lines): void {
    Livewire::test(HighlightLinesTestForm::class)
        ->callAction(highlightLinesAction(), data: ['lines' => $lines], arguments: ['editorSelection' => ['type' => 'text', 'anchor' => 1, 'head' => 1]])
        ->assertHasActionErrors(['lines'])
        ->assertNotDispatched('run-rich-editor-plus-commands');
})->with(['two', '0', '5-3']);
