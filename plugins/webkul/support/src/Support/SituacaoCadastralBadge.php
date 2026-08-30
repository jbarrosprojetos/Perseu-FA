<?php

namespace Webkul\Support\Support;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

/**
 * Mesmo badge de Situação Cadastral já usado em
 * `Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaJuridicaResource`
 * (cores conforme os códigos oficiais: 02 Ativa, 03 Suspensa, 04/05
 * Inapta/Nula, 01/08 Baixada — ver CLAUDE.md), extraído aqui como uma
 * função pura (sem `Get`/`Set` do Filament) porque é reaproveitado em
 * dois lugares dentro de `webkul/support`
 * (`CompanyResource`/`BranchesRelationManager`, que não compartilham
 * uma classe base) — mais simples que uma dependência cruzada de
 * `perseu/pessoas` (plugin de negócio) pra dentro de `webkul/support`
 * (core) só por causa de um helper de 15 linhas.
 */
class SituacaoCadastralBadge
{
    public static function render(?string $codigo, ?string $descricao): ?HtmlString
    {
        if (blank($descricao)) {
            return null;
        }

        return new HtmlString(Blade::render(
            '<x-filament::badge :color="$color">{{ $label }}</x-filament::badge>',
            ['color' => static::color($codigo), 'label' => $descricao],
        ));
    }

    protected static function color(?string $codigo): string
    {
        return match ($codigo) {
            '2'      => 'success',
            '3'      => 'warning',
            '4', '5' => 'danger',
            '1', '8' => 'gray',
            default  => 'gray',
        };
    }
}
