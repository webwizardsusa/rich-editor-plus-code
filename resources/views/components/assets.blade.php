{{--
    The styles and copy button for highlighted code blocks in rendered content. Put it once on each page that shows
    the content, for example in the layout's <head>.

    dark: "class" uses the dark theme under a `.dark` ancestor (as Tailwind and Filament do), "media" follows the
    visitor's system setting, and "none" keeps the light theme.
--}}
@props(['copy' => true, 'dark' => 'class'])

@php
    $darkSelector = match ($dark) {
        'class' => '.dark .rep-code',
        'media' => '.rep-code',
        default => null,
    };

    $darkRules = $darkSelector === null ? '' : "{$darkSelector} .phiki,{$darkSelector} .phiki span{color:var(--phiki-dark-color)!important}{$darkSelector} .phiki{background-color:var(--phiki-dark-background-color)!important}{$darkSelector} .phiki .line.highlighted{background:rgb(255 255 255/.08)}";
@endphp

<style>
.rep-code{position:relative;margin:1rem 0}
.rep-code .phiki{margin:0;padding:1rem 0;overflow-x:auto;border-radius:.5rem;font-size:.875em;line-height:1.6}
.rep-code .phiki code{display:block;min-width:fit-content}
.rep-code .phiki .line{display:block;padding:0 1rem}
.rep-code .phiki .line.highlighted{background:rgb(0 0 0/.06);box-shadow:inset 3px 0 0 currentColor}
.rep-code .phiki .line-number{display:inline-block;min-width:2.5ch;margin-right:1rem;text-align:right}
.rep-code-copy{position:absolute;top:.5rem;right:.5rem;padding:.125rem .5rem;border:1px solid rgb(127 127 127/.4);border-radius:.25rem;background:rgb(127 127 127/.15);color:inherit;font-size:.75rem;cursor:pointer;opacity:0;transition:opacity .15s}
.rep-code:hover .rep-code-copy,.rep-code-copy:focus-visible{opacity:1}
@if ($dark === 'media')
@media (prefers-color-scheme:dark){ {!! $darkRules !!} }
@else
{!! $darkRules !!}
@endif
</style>

@if ($copy)
    <script>
        (() => {
            const labels = @js(['copy' => __('rich-editor-plus-code::plugin.copy.label'), 'copied' => __('rich-editor-plus-code::plugin.copy.copied')])

            const addButtons = () => document.querySelectorAll('.rep-code:not([data-rep-copy])').forEach((block) => {
                block.dataset.repCopy = ''

                const button = document.createElement('button')
                button.type = 'button'
                button.className = 'rep-code-copy'
                button.textContent = labels.copy

                button.addEventListener('click', () => {
                    const code = block.querySelector('code')?.cloneNode(true)

                    if (!code || !navigator.clipboard) {
                        return
                    }

                    code.querySelectorAll('.line-number').forEach((number) => number.remove())

                    navigator.clipboard.writeText(code.textContent.replace(/\n$/, '')).then(() => {
                        button.textContent = labels.copied
                        setTimeout(() => (button.textContent = labels.copy), 1500)
                    })
                })

                block.append(button)
            })

            document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', addButtons) : addButtons()
            document.addEventListener('livewire:navigated', addButtons)
        })()
    </script>
@endif
