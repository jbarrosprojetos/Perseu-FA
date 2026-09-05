<?php

namespace Perseu\Comercial\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * As 7 origens possíveis de um Item de Projeto — mesmos valores já
 * usados como chave do `Select::make('origem_item_selecionada')` em
 * `ProjetoResource::origensItemOptions()` (não duplicar/renomear essas
 * strings sem atualizar os dois lugares). Hoje só `ItemAvulso` tem
 * persistência de verdade — as outras 6 continuam com a notificação
 * placeholder no formulário, mas já entram aqui pra `itens_projeto
 * .origem` aceitar qualquer uma delas quando cada origem ganhar sua
 * própria lógica.
 */
enum OrigemItemProjeto: string implements HasLabel
{
    case ItemAvulso = 'item_avulso';

    case ItemLinha = 'item_linha';

    case PromobPlus = 'promob_plus';

    case PromobStart = 'promob_start';

    case SketchupHellomob = 'sketchup_hellomob';

    case SketchupCutlist = 'sketchup_cutlist';

    case Cortcloud = 'cortcloud';

    public function getLabel(): string
    {
        return match ($this) {
            self::ItemAvulso       => __('comercial::filament/resources/projeto.form.itens.origens.item-avulso'),
            self::ItemLinha        => __('comercial::filament/resources/projeto.form.itens.origens.item-linha'),
            self::PromobPlus       => __('comercial::filament/resources/projeto.form.itens.origens.promob-plus'),
            self::PromobStart      => __('comercial::filament/resources/projeto.form.itens.origens.promob-start'),
            self::SketchupHellomob => __('comercial::filament/resources/projeto.form.itens.origens.sketchup-hellomob'),
            self::SketchupCutlist  => __('comercial::filament/resources/projeto.form.itens.origens.sketchup-cutlist'),
            self::Cortcloud        => __('comercial::filament/resources/projeto.form.itens.origens.cortcloud'),
        };
    }
}
