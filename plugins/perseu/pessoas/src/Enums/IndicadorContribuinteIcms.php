<?php

namespace Perseu\Pessoas\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Corresponde ao `indIEDest` da NF-e (futuro módulo de emissão) — por
 * enquanto é só um campo de cadastro, sem lógica de emissão associada
 * (ver CLAUDE.md). Sem case "Não Informado": diferente de
 * RegimeTributario, este campo não tem preenchimento automático via
 * BrasilAPI e fica sempre nulo até o usuário escolher manualmente.
 */
enum IndicadorContribuinteIcms: int implements HasLabel
{
    case Contribuinte = 1;

    case Isento = 2;

    case NaoContribuinte = 3;

    public function getLabel(): string
    {
        return match ($this) {
            self::Contribuinte   => __('pessoas::enums.indicador-contribuinte-icms.contribuinte'),
            self::Isento         => __('pessoas::enums.indicador-contribuinte-icms.isento'),
            self::NaoContribuinte => __('pessoas::enums.indicador-contribuinte-icms.nao-contribuinte'),
        };
    }
}
