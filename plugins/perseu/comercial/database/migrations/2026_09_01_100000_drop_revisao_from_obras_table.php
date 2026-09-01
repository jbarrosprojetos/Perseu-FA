<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove `obras.revisao` — campo que veio junto com a numeração
 * AAT#### desde a criação do cadastro (migration original
 * `..._create_projetos_table`), mas conceitualmente pertence à
 * Proposta (e ao Projeto), não à Obra — ver
 * CONCEITO-OBRA-PROPOSTA-PROJETO.md. Confirmado por grep antes de
 * remover: sem uso em `GeradorNumeroObra` (a numeração AAT#### não
 * depende dele), sem `$casts`, sem referência de nenhum outro Model —
 * só existia como Placeholder somente-leitura no formulário
 * (`revisao_display`) e como entrada de `$fillable` nunca
 * efetivamente preenchida pelo usuário.
 *
 * A migration original que criou a coluna (`..._create_projetos_table`)
 * não foi editada — já tinha sido aplicada; remover uma coluna já
 * existente é sempre uma migration nova (mesma convenção de sempre).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obras', function (Blueprint $table) {
            $table->dropColumn('revisao');
        });
    }

    public function down(): void
    {
        Schema::table('obras', function (Blueprint $table) {
            $table->unsignedInteger('revisao')->default(0);
        });
    }
};
