<?php

namespace Perseu\Pessoas\Enums;

use Filament\Support\Contracts\HasLabel;

enum RegimeTributario: int implements HasLabel
{
    case SimplesNacional = 1;

    case LucroPresumido = 2;

    case LucroReal = 3;

    case Mei = 4;

    public function getLabel(): string
    {
        return match ($this) {
            self::SimplesNacional => __('pessoas::enums.regime-tributario.simples-nacional'),
            self::LucroPresumido  => __('pessoas::enums.regime-tributario.lucro-presumido'),
            self::LucroReal       => __('pessoas::enums.regime-tributario.lucro-real'),
            self::Mei             => __('pessoas::enums.regime-tributario.mei'),
        };
    }
}
