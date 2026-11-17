<?php

namespace Webwizardsusa\RichEditorPlusCode;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Webwizardsusa\RichEditorPlus\Support\RegistersPluginAssets;

/**
 * Registers the package's translations, its `<x-rich-editor-plus-code::assets />` component and its built JS module
 * and stylesheet. `RegistersPluginAssets` finds `dist/rich-editor-plus-code.{js,css}`, or the Vite dev server while
 * `public/rich-editor-plus-code.hot` exists.
 */
class RichEditorPlusCodeServiceProvider extends PackageServiceProvider
{
    use RegistersPluginAssets {
        registerPluginAssets as registerModuleAssets;
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('rich-editor-plus-code')
            ->hasTranslations()
            ->hasViews();
    }

    public function packageBooted(): void
    {
        $this->registerPluginAssets();
    }

    /**
     * Registers the module and stylesheet, and each of Shiki's grammar and theme chunks (`dist/chunks-*.js`) as an
     * Alpine component of its own. `filament:assets` then publishes them into the module's directory, where the module
     * imports them by relative URL. Alpine components are only fetched when asked for, so no page loads them otherwise.
     */
    protected function registerPluginAssets(): void
    {
        $this->registerModuleAssets();

        $chunks = array_map(
            static fn (string $path): AlpineComponent => AlpineComponent::make(basename($path, '.js'), $path),
            glob(static::assetsDistPath().'/chunks-*.js') ?: [],
        );

        if ($chunks !== []) {
            FilamentAsset::register($chunks, static::assetPackage());
        }
    }

    public static function assetPackage(): string
    {
        return 'webwizardsusa/rich-editor-plus-code';
    }

    public static function assetId(): string
    {
        return 'rich-editor-plus-code';
    }
}
