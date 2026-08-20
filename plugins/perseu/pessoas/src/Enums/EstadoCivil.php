<?php

namespace Perseu\Pessoas\Enums;

use Filament\Support\Contracts\HasLabel;

enum EstadoCivil: int implements HasLabel
{
    case Solteiro = 1;

    case Casado = 2;

    case Divorciado = 3;

    case Viuvo = 4;

    case UniaoEstavel = 5;

    public function getLabel(): string
    {
        return match ($this) {
            self::Solteiro     => __('pessoas::enums.estado-civil.solteiro'),
            self::Casado       => __('pessoas::enums.estado-civil.casado'),
            self::Divorciado   => __('pessoas::enums.estado-civil.divorciado'),
            self::Viuvo        => __('pessoas::enums.estado-civil.viuvo'),
            self::UniaoEstavel => __('pessoas::enums.estado-civil.uniao-estavel'),
        };
    }
}
