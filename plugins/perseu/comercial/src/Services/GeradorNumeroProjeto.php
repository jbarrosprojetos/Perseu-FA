<?php

namespace Perseu\Comercial\Services;

use Illuminate\Support\Facades\DB;
use Perseu\Comercial\Models\TipoProjeto;

/**
 * projeto_numero_sequencias é tabela de controle interna (sem tela, sem
 * Model próprio) — só o contador usado aqui para montar numero_projeto.
 */
class GeradorNumeroProjeto
{
    public static function gerar(int $ano, TipoProjeto $tipo): string
    {
        return DB::transaction(function () use ($ano, $tipo) {
            // insertOrIgnore evita erro de chave duplicada se duas
            // transações tentarem criar a linha da sequência ao mesmo
            // tempo; o lockForUpdate() logo depois garante exclusividade
            // para o incremento em si.
            DB::table('projeto_numero_sequencias')->insertOrIgnore([
                'ano'               => $ano,
                'tipo_projeto_id'   => $tipo->id,
                'ultimo_sequencial' => 0,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            $sequencia = DB::table('projeto_numero_sequencias')
                ->where('ano', $ano)
                ->where('tipo_projeto_id', $tipo->id)
                ->lockForUpdate()
                ->first();

            $proximoSequencial = $sequencia->ultimo_sequencial + 1;

            DB::table('projeto_numero_sequencias')
                ->where('id', $sequencia->id)
                ->update([
                    'ultimo_sequencial' => $proximoSequencial,
                    'updated_at'        => now(),
                ]);

            return sprintf('%02d%s%04d', $ano % 100, $tipo->codigo, $proximoSequencial);
        });
    }
}
