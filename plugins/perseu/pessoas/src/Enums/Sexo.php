<?php

namespace Perseu\Pessoas\Enums;

use Filament\Support\Contracts\HasLabel;

enum Sexo: int implements HasLabel
{
    case Masculino = 1;

    case Feminino = 2;

    case Outro = 3;

    public function getLabel(): string
    {
        return match ($this) {
            self::Masculino => __('pessoas::enums.sexo.masculino'),
            self::Feminino  => __('pessoas::enums.sexo.feminino'),
            self::Outro     => __('pessoas::enums.sexo.outro'),
        };
    }
}
