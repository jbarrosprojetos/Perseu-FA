<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Traz de volta `obras.revisao`, removido em
 * `2026_09_01_100000_drop_revisao_from_obras_table` (ver CLAUDE.md).
 *
 * Contexto do replanejamento (ver CONCEITO-OBRA-PROPOSTA-PROJETO.md e
 * CLAUDE.md — tentativa de Cluster "Propostas" separado, implementada
 * e depois revertida por completo): decidido, por ora, NÃO criar um
 * cadastro de Proposta separado — a combinação "Obra + Revisão" já
 * representa o que seria uma Proposta, mas o cadastro/menu continua
 * se chamando "Obra" (rename é decisão futura, em aberto).
 *
 * Mesmo comportamento exato de antes da remoção: `unsignedInteger`
 * `default(0)`, sem nenhuma lógica de autoincremento. Migration nova
 * (não um `down()` da migration de remoção), pra manter o histórico
 * linear — mesma convenção de sempre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obras', function (Blueprint $table) {
            $table->unsignedInteger('revisao')->default(0)->after('descricao');
        });
    }

    public function down(): void
    {
        Schema::table('obras', function (Blueprint $table) {
            $table->dropColumn('revisao');
        });
    }
};
