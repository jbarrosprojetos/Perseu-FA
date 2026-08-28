<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename completo de "Projeto" para "Obra" (decisão de nomenclatura
 * final registrada em CLAUDE.md) — via Schema::rename()/renameColumn(),
 * preservando dados existentes, nunca dropar/recriar. A numeração
 * automática (prefixo AAT####, ver GeradorNumeroObra) não muda; só os
 * nomes de tabela/coluna que diziam "projeto" passam a dizer "obra".
 *
 * Ordem: tabelas primeiro, depois colunas dentro das tabelas já
 * renomeadas (uma FK aponta para o NOME ATUAL da tabela — MySQL/MariaDB
 * atualiza a constraint automaticamente quando a tabela referenciada é
 * renomeada, sem precisar dropar/recriar a FK).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('situacoes_projeto', 'situacoes_obra');
        Schema::rename('tipos_projeto', 'tipos_obra');
        Schema::rename('projetos', 'obras');
        Schema::rename('projeto_numero_sequencias', 'obra_numero_sequencias');
        Schema::rename('projeto_situacao', 'obra_situacao');

        Schema::table('obra_numero_sequencias', function (Blueprint $table) {
            $table->renameColumn('tipo_projeto_id', 'tipo_obra_id');
        });

        Schema::table('obras', function (Blueprint $table) {
            $table->renameColumn('tipo_projeto_id', 'tipo_obra_id');
            $table->renameColumn('numero_projeto', 'numero_obra');
        });

        Schema::table('obra_situacao', function (Blueprint $table) {
            $table->renameColumn('projeto_id', 'obra_id');
            $table->renameColumn('situacao_projeto_id', 'situacao_obra_id');
        });
    }

    public function down(): void
    {
        Schema::table('obra_situacao', function (Blueprint $table) {
            $table->renameColumn('obra_id', 'projeto_id');
            $table->renameColumn('situacao_obra_id', 'situacao_projeto_id');
        });

        Schema::table('obras', function (Blueprint $table) {
            $table->renameColumn('tipo_obra_id', 'tipo_projeto_id');
            $table->renameColumn('numero_obra', 'numero_projeto');
        });

        Schema::table('obra_numero_sequencias', function (Blueprint $table) {
            $table->renameColumn('tipo_obra_id', 'tipo_projeto_id');
        });

        Schema::rename('obra_situacao', 'projeto_situacao');
        Schema::rename('obra_numero_sequencias', 'projeto_numero_sequencias');
        Schema::rename('obras', 'projetos');
        Schema::rename('tipos_obra', 'tipos_projeto');
        Schema::rename('situacoes_obra', 'situacoes_projeto');
    }
};
