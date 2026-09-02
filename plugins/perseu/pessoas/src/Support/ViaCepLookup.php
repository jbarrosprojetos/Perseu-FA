<?php

namespace Perseu\Pessoas\Support;

use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Consulta a API pública ViaCEP (https://viacep.com.br, sem autenticação) e
 * preenche logradouro/bairro/municipio/uf via Set. Extraído de
 * Perseu\Pessoas\Traits\HasEnderecoRelationManagerSchema para um local com
 * nome neutro (não amarrado a "Relation Manager") — qualquer formulário do
 * projeto com esses mesmos campos pode reaproveitar (ex: o
 * createOptionForm de Endereço em
 * Perseu\Comercial\...\ProjetoResource::form(), sem precisar herdar tipo/
 * principal ou form()/table() de Relation Manager, que não fazem sentido
 * fora desse contexto).
 */
class ViaCepLookup
{
    public static function fill(Set $set, ?string $cep): void
    {
        $digits = preg_replace('/\D/', '', (string) $cep);

        if (strlen($digits) !== 8) {
            return;
        }

        try {
            $response = Http::timeout(5)->get("https://viacep.com.br/ws/{$digits}/json/");
        } catch (Throwable) {
            return;
        }

        if (! $response->successful()) {
            return;
        }

        $data = $response->json();

        if (! is_array($data) || ($data['erro'] ?? false)) {
            return;
        }

        $set('logradouro', $data['logradouro'] ?? null);
        $set('bairro', $data['bairro'] ?? null);
        $set('municipio', $data['localidade'] ?? null);
        $set('uf', $data['uf'] ?? null);
    }
}
