<?php

namespace Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaJuridicaResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Perseu\Pessoas\Enums\TipoEndereco;
use Perseu\Pessoas\Traits\HasEnderecoRelationManagerSchema;

class EnderecosRelationManager extends RelationManager
{
    use HasEnderecoRelationManagerSchema;

    protected static string $relationship = 'enderecos';

    protected static ?string $recordTitleAttribute = 'logradouro';

    protected static function translationPrefix(): string
    {
        return 'pessoas::filament/resources/pessoa-juridica/relation-managers/enderecos';
    }

    /**
     * Pessoa Jurídica (ver CLAUDE.md, "Filtro de Tipo de Endereço por
     * contexto"): tags Comercial, Cobrança, Entrega, Obra, Outro — sem
     * Residencial.
     *
     * @return array<TipoEndereco>
     */
    protected static function tipoEnderecoOptions(): array
    {
        return [
            TipoEndereco::Comercial,
            TipoEndereco::Cobranca,
            TipoEndereco::Entrega,
            TipoEndereco::Obra,
            TipoEndereco::Outro,
        ];
    }
}
