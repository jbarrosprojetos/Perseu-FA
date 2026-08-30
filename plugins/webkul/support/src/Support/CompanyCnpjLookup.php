<?php

namespace Webkul\Support\Support;

use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Perseu\Pessoas\Rules\CnpjValido;
use Perseu\Pessoas\Support\BrasilApiCnpjLookup;
use Webkul\Support\Models\State;

/**
 * Complemento a `Perseu\Pessoas\Support\BrasilApiCnpjLookup` (busca de
 * CNPJ via BrasilAPI — reaproveitada aqui, NÃO recriada, ver CLAUDE.md)
 * só pra preencher o endereço EMBUTIDO de `Webkul\Support\Models\Company`
 * (`street1`/`street2`/`city`/`bairro`/`numero`/`zip`/`state_id`).
 *
 * Diferente de Pessoa Jurídica (endereço é uma relação `enderecos`,
 * sem campos no formulário principal — ver CLAUDE.md), Empresa tem
 * endereço único INLINE no próprio formulário, então precisa de um
 * `Set` direto nos campos de endereço, algo que
 * `BrasilApiCnpjLookup::fill()` não faz (não existia essa necessidade
 * antes desta tarefa). Fica em `webkul/support` (não em
 * `perseu/pessoas`) pra não misturar conhecimento específico do
 * schema legado do AureusERP (nomes de coluna `street1`/`city`/etc.,
 * `state_id` como FK em vez de UF texto livre) dentro da classe de
 * Pessoas — só reaproveita `BrasilApiCnpjLookup::buscar()`/
 * `enderecoFrom()` (ambos públicos e sem estado) como fonte de dados.
 */
class CompanyCnpjLookup
{
    /**
     * Só preenche campos vazios (mesma regra de
     * `BrasilApiCnpjLookup::fill()` — nunca sobrescreve o que o usuário
     * já digitou/editou manualmente).
     */
    public static function fillEndereco(Set $set, Get $get, ?string $cnpj): void
    {
        $digits = preg_replace('/\D/', '', (string) $cnpj);

        if (strlen($digits) !== 14 || ! CnpjValido::isValid($digits)) {
            return;
        }

        $data = BrasilApiCnpjLookup::buscar($digits);

        if ($data === null) {
            return;
        }

        $endereco = BrasilApiCnpjLookup::enderecoFrom($data);

        $mapa = [
            'logradouro'  => 'street1',
            'complemento' => 'street2',
            'municipio'   => 'city',
            'bairro'      => 'bairro',
            'numero'      => 'numero',
            'cep'         => 'zip',
        ];

        foreach ($mapa as $campoApi => $campoDestino) {
            if (blank($get($campoDestino)) && filled($endereco[$campoApi] ?? null)) {
                $set($campoDestino, $endereco[$campoApi]);
            }
        }

        // `uf` (string, ex. "SP") precisa virar `state_id` (FK) — os 27
        // estados brasileiros já estão seedados com `code` de 2 letras
        // batendo exatamente com o formato da BrasilAPI (confirmado via
        // tinker antes de implementar, não assumido).
        if (blank($get('state_id')) && filled($endereco['uf'] ?? null)) {
            $estado = State::whereHas('country', fn ($query) => $query->where('code', 'BR'))
                ->where('code', $endereco['uf'])
                ->first();

            if ($estado) {
                $set('state_id', $estado->id);
                $set('country_id', $estado->country_id);
            }
        }
    }
}
