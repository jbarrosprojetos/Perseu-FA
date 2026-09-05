<?php

namespace Perseu\Comercial\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * As 4 origens possíveis de um Item de Projeto — mesmos valores já
 * usados como chave do `Select::make('origem_item_selecionada')` em
 * `ProjetoResource::origensItemOptions()` (não duplicar/renomear essas
 * strings sem atualizar os dois lugares). Hoje só `ItemAvulso` tem
 * persistência de verdade e `Promob` tem o modal de upload/checagem
 * (ver CLAUDE.md) — `ItemLinha`/`Sketchup` continuam com a
 * notificação placeholder no formulário, mas já entram aqui pra
 * `itens_projeto.origem` aceitar qualquer uma delas quando cada
 * origem ganhar sua própria lógica.
 */
enum OrigemItemProjeto: string implements HasLabel
{
    case ItemAvulso = 'item_avulso';

    case ItemLinha = 'item_linha';

    case Promob = 'promob';

    case Sketchup = 'sketchup';

    public function getLabel(): string
    {
        return match ($this) {
            self::ItemAvulso => __('comercial::filament/resources/projeto.form.itens.origens.item-avulso'),
            self::ItemLinha  => __('comercial::filament/resources/projeto.form.itens.origens.item-linha'),
            self::Promob     => __('comercial::filament/resources/projeto.form.itens.origens.promob'),
            self::Sketchup   => __('comercial::filament/resources/projeto.form.itens.origens.sketchup'),
        };
    }
}
