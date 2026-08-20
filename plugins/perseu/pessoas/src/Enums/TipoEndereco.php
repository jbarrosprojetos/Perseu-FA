<?php

namespace Perseu\Pessoas\Enums;

use Filament\Support\Contracts\HasLabel;

enum TipoEndereco: int implements HasLabel
{
    case Residencial = 1;

    case Comercial = 2;

    case Cobranca = 3;

    case Obra = 4;

    case Entrega = 5;

    case Outro = 6;

    public function getLabel(): string
    {
        return match ($this) {
            self::Residencial => __('pessoas::enums.tipo-endereco.residencial'),
            self::Comercial   => __('pessoas::enums.tipo-endereco.comercial'),
            self::Cobranca    => __('pessoas::enums.tipo-endereco.cobranca'),
            self::Obra        => __('pessoas::enums.tipo-endereco.obra'),
            self::Entrega     => __('pessoas::enums.tipo-endereco.entrega'),
            self::Outro       => __('pessoas::enums.tipo-endereco.outro'),
        };
    }
}
