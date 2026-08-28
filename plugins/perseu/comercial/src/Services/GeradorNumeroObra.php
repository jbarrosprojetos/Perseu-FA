<?php

namespace Perseu\Comercial\Services;

use Illuminate\Support\Facades\DB;
use Perseu\Comercial\Models\TipoObra;

/**
 * obra_numero_sequencias é tabela de controle interna (sem tela, sem
 * Model próprio) — só o contador usado aqui para montar numero_obra.
 */
class GeradorNumeroObra
{
    public static function gerar(int $ano, TipoObra $tipo): string
    {
        return DB::transaction(function () use ($ano, $tipo) {
            // insertOrIgnore evita erro de chave duplicada se duas
            // transações tentarem criar a linha da sequência ao mesmo
            // tempo; o lockForUpdate() logo depois garante exclusividade
            // para o incremento em si.
            DB::table('obra_numero_sequencias')->insertOrIgnore([
                'ano'               => $ano,
                'tipo_obra_id'      => $tipo->id,
                'ultimo_sequencial' => 0,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            $sequencia = DB::table('obra_numero_sequencias')
                ->where('ano', $ano)
                ->where('tipo_obra_id', $tipo->id)
                ->lockForUpdate()
                ->first();

            $proximoSequencial = $sequencia->ultimo_sequencial + 1;

            DB::table('obra_numero_sequencias')
                ->where('id', $sequencia->id)
                ->update([
                    'ultimo_sequencial' => $proximoSequencial,
                    'updated_at'        => now(),
                ]);

            return sprintf('%02d%s%04d', $ano % 100, $tipo->codigo, $proximoSequencial);
        });
    }
}
