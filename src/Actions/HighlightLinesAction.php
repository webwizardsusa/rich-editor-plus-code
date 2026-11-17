<?php

namespace Webwizardsusa\RichEditorPlusCode\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\Width;
use Webwizardsusa\RichEditorPlus\RichEditorPlus;
use Webwizardsusa\RichEditorPlus\Support\EditorCommand;
use Webwizardsusa\RichEditorPlusCode\Support\LineRanges;

/**
 * Asks which lines of the code block at the cursor to highlight, such as `2,4-6`. The editor mounts it with the
 * block's current lines (see `registerActionArguments('highlightCodeLines')` in the JS module).
 */
class HighlightLinesAction
{
    public const string NAME = 'highlightCodeLines';

    public static function make(): Action
    {
        return Action::make(self::NAME)
            ->label(__('rich-editor-plus-code::plugin.actions.highlight_lines.label'))
            ->modalHeading(__('rich-editor-plus-code::plugin.actions.highlight_lines.heading'))
            ->modalWidth(Width::Small)
            ->fillForm(fn (array $arguments): array => ['lines' => LineRanges::normalize($arguments['highlightLines'] ?? null)])
            ->schema([
                TextInput::make('lines')
                    ->label(__('rich-editor-plus-code::plugin.actions.highlight_lines.lines'))
                    ->helperText(__('rich-editor-plus-code::plugin.actions.highlight_lines.help'))
                    ->placeholder('2, 4-6')
                    ->maxLength(500)
                    ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                        if (! is_string($value) || ! LineRanges::isValid($value) || (filled($value) && LineRanges::parse($value) === [])) {
                            $fail(__('rich-editor-plus-code::plugin.actions.highlight_lines.invalid'));
                        }
                    }),
            ])
            ->action(function (array $arguments, array $data, RichEditorPlus $component): void {
                $component->runCommands(
                    [EditorCommand::make('updateAttributes', ['codeBlock', ['highlightLines' => LineRanges::normalize($data['lines'] ?? null)]])],
                    $arguments['editorSelection'] ?? null,
                );
            });
    }
}
