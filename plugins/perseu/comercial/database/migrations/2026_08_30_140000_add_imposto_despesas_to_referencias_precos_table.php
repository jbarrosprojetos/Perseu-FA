<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Completa a cadeia de cálculo de preço da Referência de Preços com os
 * campos de Imposto, Despesas Variáveis e Despesas Fixas (levantados a
 * partir da planilha real de Proposta da F.A. Marcenaria — ver
 * CLAUDE.md). Margem de Lucro NÃO entra aqui: é resultado calculado,
 * não parâmetro de entrada.
 *
 * A tabela `referencias_precos` já existia (migration
 * `2026_08_30_130000_create_referencias_precos_table`) — como já tinha
 * sido aplicada, esta é uma migration nova de ALTER, não uma edição
 * retroativa daquela (mesma convenção usada em todo rename/ajuste de
 * schema já aplicado neste projeto).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referencias_precos', function (Blueprint $table) {
            $table->decimal('imposto', 5, 2)->nullable()->after('retencao_tecnica');
            $table->decimal('despesas_variaveis', 5, 2)->nullable()->after('imposto');
            $table->decimal('despesas_fixas', 5, 2)->nullable()->after('despesas_variaveis');
        });
    }

    public function down(): void
    {
        Schema::table('referencias_precos', function (Blueprint $table) {
            $table->dropColumn(['imposto', 'despesas_variaveis', 'despesas_fixas']);
        });
    }
};
