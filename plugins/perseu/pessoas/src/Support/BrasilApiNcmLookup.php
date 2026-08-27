<?php

namespace Perseu\Pessoas\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * RESERVADO PARA USO FUTURO — não está em uso em nenhum formulário no
 * momento (ver CLAUDE.md, "NCM removido do cadastro de Pessoa Jurídica").
 *
 * Foi implementada originalmente para um campo `ncm` em Pessoa Jurídica,
 * removido depois por equívoco de escopo: NCM é classificação de
 * PRODUTO/MERCADORIA, não de empresa — não pertence ao cadastro de
 * Pessoa Jurídica. Mantida no código (não deletada) porque a lógica em
 * si (busca assíncrona na BrasilAPI, `GET /api/ncm/v1`) é reaproveitável
 * quando o futuro cadastro de Produto/Material for criado, que é onde
 * NCM realmente pertence — só o ponto de uso (`getSearchResultsUsing`/
 * `getOptionLabelUsing` num Select) precisa ser reconectado lá.
 */
class BrasilApiNcmLookup
{
    /**
     * @return array<string, string> código NCM => "código - descrição"
     */
    public static function buscar(string $termo): array
    {
        if (mb_strlen(trim($termo)) < 2) {
            return [];
        }

        try {
            $response = Http::timeout(8)->get('https://brasilapi.com.br/api/ncm/v1', ['search' => $termo]);
        } catch (Throwable) {
            return [];
        }

        if (! $response->successful()) {
            return [];
        }

        $data = $response->json();

        if (! is_array($data)) {
            return [];
        }

        return collect($data)
            ->take(50)
            ->filter(fn (array $item) => filled($item['codigo'] ?? null))
            ->mapWithKeys(fn (array $item) => [$item['codigo'] => "{$item['codigo']} - {$item['descricao']}"])
            ->all();
    }

    /**
     * Rótulo do código já salvo, para o Select mostrar o valor selecionado
     * (getOptionLabelUsing) sem precisar rebuscar por termo. Cacheado por
     * mais tempo que a busca de CNPJ — a tabela de NCM muda raramente
     * (é atualizada por Resolução Camex, não em tempo real).
     */
    public static function label(?string $codigo): ?string
    {
        if (blank($codigo)) {
            return null;
        }

        return Cache::remember(
            'brasilapi.ncm.'.$codigo,
            now()->addDay(),
            function () use ($codigo) {
                try {
                    $response = Http::timeout(8)->get('https://brasilapi.com.br/api/ncm/v1/'.$codigo);
                } catch (Throwable) {
                    return $codigo;
                }

                if (! $response->successful()) {
                    return $codigo;
                }

                $data = $response->json();

                return is_array($data) && filled($data['descricao'] ?? null)
                    ? "{$codigo} - {$data['descricao']}"
                    : $codigo;
            },
        );
    }
}
