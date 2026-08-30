<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mais 4 campos da composição de custo da Referência de Preços (ver
 * CLAUDE.md): Valor por Peças (monetário) e três fatores percentuais
 * (Madeiras, Ferragens e Miscelânias, Mão de Obra).
 *
 * A tabela `referencias_precos` já existia e já tinha sido alterada
 * duas vezes (criação + Imposto/Despesas) — esta é mais uma migration
 * de ALTER, não uma edição das anteriores (mesma convenção de sempre).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referencias_precos', function (Blueprint $table) {
            $table->decimal('valor_pecas', 10, 2)->nullable()->after('despesas_fixas');
            $table->decimal('fator_madeiras', 5, 2)->nullable()->after('valor_pecas');
            $table->decimal('fator_ferragens_miscelanias', 5, 2)->nullable()->after('fator_madeiras');
            $table->decimal('fator_mao_obra', 5, 2)->nullable()->after('fator_ferragens_miscelanias');
        });
    }

    public function down(): void
    {
        Schema::table('referencias_precos', function (Blueprint $table) {
            $table->dropColumn(['valor_pecas', 'fator_madeiras', 'fator_ferragens_miscelanias', 'fator_mao_obra']);
        });
    }
};
