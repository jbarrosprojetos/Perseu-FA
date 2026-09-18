<?php

namespace Perseu\Comercial\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Origem de uma linha de `itens_projeto_componentes` (2026-09-12, ver
 * migration `2026_09_12_100000_create_itens_projeto_componentes_table`
 * e CLAUDE.md "Fluxo Promob") — distingue componente extraído
 * automaticamente do XML do Promob (`XmlPromob`) de linha
 * adicionada/ajustada manualmente pelo usuário na Aba P (`Manual`),
 * necessário porque re-extrair um Item (excluir + recriar via Promob)
 * precisa saber quais linhas eram ajustes manuais.
 */
enum OrigemComponenteItem: string implements HasLabel
{
    case XmlPromob = 'xml_promob';

    case Manual = 'manual';

    public function getLabel(): string
    {
        return match ($this) {
            self::XmlPromob => __('comercial::filament/resources/projeto.form.itens.componentes.origens.xml-promob'),
            self::Manual    => __('comercial::filament/resources/projeto.form.itens.componentes.origens.manual'),
        };
    }
}
