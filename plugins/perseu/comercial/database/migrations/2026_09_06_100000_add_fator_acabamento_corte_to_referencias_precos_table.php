<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Único campo, dos 9 confirmados na tarefa "Criar Itens do Promob", que
 * ainda não existia em `referencias_precos` (ver CLAUDE.md, "Fluxo
 * Promob" — "Parte 0"): Fator Acabamento/Corte, usado na fórmula de
 * Custo Unitário (`AcabamentoCorte = (Laminação + Corte + Peças) ×
 * FatorAcabamentoCorte`). Os outros 8 (Fator Madeira/`fator_madeiras`,
 * Fator Ferragens e Miscelânea/`fator_ferragens_miscelanias`, Fator Mão
 * de Obra/`fator_mao_obra`, Valor Laminação/`laminacao`, Valor
 * Corte/`corte`, Valor Por Peça/`valor_pecas`, Valor Hora
 * Produção/`hora_producao`, Valor Hora Execução/`hora_execucao`) já
 * existiam desde as migrations `2026_08_30_130000_.../2026_08_30_150000_...`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referencias_precos', function (Blueprint $table) {
            $table->decimal('fator_acabamento_corte', 5, 2)->nullable()->after('fator_ferragens_miscelanias');
        });
    }

    public function down(): void
    {
        Schema::table('referencias_precos', function (Blueprint $table) {
            $table->dropColumn('fator_acabamento_corte');
        });
    }
};
