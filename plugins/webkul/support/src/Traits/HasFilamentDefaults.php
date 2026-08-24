<?php

namespace Webkul\Support\Traits;

use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Webkul\Support\Settings\BrandSettings;
use Webkul\Support\Support\BrandAssetResolver;

trait HasFilamentDefaults
{
    protected function registerFilamentDefaults(): void
    {
        Fieldset::configureUsing(fn (Fieldset $fieldset) => $fieldset->columnSpanFull());

        Grid::configureUsing(fn (Grid $grid) => $grid->columnSpanFull());

        Section::configureUsing(fn (Section $section) => $section->columnSpanFull());
    }

    protected function registerHooks(): void
    {
        // Versão fixa em código (não vem de composer.json/config — é só
        // uma string de exibição no menu do usuário, sem relação com a
        // versão do pacote aureuserp/aureuserp ou de nenhum plugin).
        $version = '1.0.0';

        FilamentView::registerRenderHook(
            PanelsRenderHook::USER_MENU_PROFILE_BEFORE,
            function () use ($version): string {
                // Mesmo padrão de resolução de favicon usado em
                // Webkul\Support\Filament\Pages\Help::getHeading() —
                // reaproveita BrandAssetResolver em vez de duplicar a
                // lógica; calculado dentro do closure (não fora, como
                // $version) pra refletir o BrandSettings atual a cada
                // render, não o valor de quando o hook foi registrado.
                $brand = settings(BrandSettings::class);

                $faviconUrl = BrandAssetResolver::resolve($brand->favicon);

                return Blade::render(<<<'BLADE'
                    <x-filament::dropdown.list>
                        <x-filament::dropdown.list.item>
                            <div class="flex items-center gap-2">
                                @if ($faviconUrl)
                                    <img
                                        src="{{ $faviconUrl }}"
                                        width="24"
                                        height="24"
                                    />
                                @endif

                                {{ __('support::support.version', ['version' => $version]) }}
                            </div>
                        </x-filament::dropdown.list.item>
                    </x-filament::dropdown.list>
                BLADE, [
                    'version'    => $version,
                    'faviconUrl' => $faviconUrl,
                ]);
            },
        );
    }
}
