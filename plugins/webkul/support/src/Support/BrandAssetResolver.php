<?php

namespace Webkul\Support\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Resolve a BrandSettings asset path (ex: favicon, light_logo, dark_logo)
 * to a usable URL: absolute URLs passam direto, caminhos existentes no
 * disco "public" (upload feito pela tela de Configurações > Branding)
 * resolvem via Storage::url(), e qualquer outro caminho cai em asset()
 * como fallback. Extraído de
 * Webkul\Support\Filament\Pages\Help::resolveBrandAsset() (a mesma
 * lógica, sem duplicação) para ser reaproveitado por qualquer lugar do
 * painel que precise do mesmo comportamento — ex:
 * Webkul\Support\Traits\HasFilamentDefaults::registerHooks() (indicador
 * de versão no menu do usuário).
 */
class BrandAssetResolver
{
    public static function resolve(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//')) {
            return $path;
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        return asset($path);
    }
}
