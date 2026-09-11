<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vínculo opcional (FK nullable) de Projeto com CondicaoFinanceira —
 * mesmo padrão de `referencia_preco_id` (ver
 * `2026_09_02_130000_add_referencia_preco_id_to_projetos_table`):
 * `nullOnDelete()` porque o vínculo é opcional por design, e uma
 * eventual exclusão definitiva de Condição Financeira não deve travar/
 * quebrar o Projeto, só perder a referência. Posicionada logo após
 * `referencia_preco_id` no layout do form (ver CLAUDE.md, "Condições
 * Financeiras" — Endereço 50% / Referência de Preços 25% / Condições
 * Financeiras 25%).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projetos', function (Blueprint $table) {
            $table->foreignId('condicao_financeira_id')
                ->nullable()
                ->after('referencia_preco_id')
                ->constrained('condicoes_financeiras')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projetos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('condicao_financeira_id');
        });
    }
};
