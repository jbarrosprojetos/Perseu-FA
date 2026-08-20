<?php

namespace Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaFisicaResource\RelationManagers;

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
        return 'pessoas::filament/resources/pessoa-fisica/relation-managers/enderecos';
    }

    /**
     * Pessoa Física (ver CLAUDE.md, "Filtro de Tipo de Endereço por
     * contexto"): Residencial, Cobrança, Entrega, Outro — sem Comercial
     * nem Obra.
     *
     * @return array<TipoEndereco>
     */
    protected static function tipoEnderecoOptions(): array
    {
        return [
            TipoEndereco::Residencial,
            TipoEndereco::Cobranca,
            TipoEndereco::Entrega,
            TipoEndereco::Outro,
        ];
    }
}
