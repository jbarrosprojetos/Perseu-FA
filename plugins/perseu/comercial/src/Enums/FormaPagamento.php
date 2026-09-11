<?php

namespace Perseu\Comercial\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * As 3 formas de pagamento de uma Condição Financeira (escolha ÚNICA
 * por registro, mesmo padrão de `OrigemItemProjeto` — enum PHP nativo
 * `string`, `implements HasLabel`, usado tanto no Select do form
 * quanto no cast `forma_pagamento` de `CondicaoFinanceira`).
 */
enum FormaPagamento: string implements HasLabel
{
    case PixTransferencia = 'pix_transferencia';

    case Boleto = 'boleto';

    case Cartao = 'cartao';

    public function getLabel(): string
    {
        return match ($this) {
            self::PixTransferencia => __('comercial::filament/resources/condicao-financeira.formas-pagamento.pix-transferencia'),
            self::Boleto           => __('comercial::filament/resources/condicao-financeira.formas-pagamento.boleto'),
            self::Cartao           => __('comercial::filament/resources/condicao-financeira.formas-pagamento.cartao'),
        };
    }
}
