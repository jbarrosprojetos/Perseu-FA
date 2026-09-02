<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rename completo de "Obra" para "Projeto" (decisão de nomenclatura final
 * registrada em CONCEITO-OBRA-PROPOSTA-PROJETO.md e CLAUDE.md — a palavra
 * "Projeto" foi liberada pelo rename Project→Processo em
 * `webkul/projects`, feito antes deste) — via Schema::rename()/
 * renameColumn(), preservando dados existentes, nunca dropar/recriar.
 * Reverte, na prática, a mesma técnica do rename anterior
 * `2026_08_28_120000_rename_projeto_to_obra.php` (aquela migration não foi
 * editada — já tinha sido aplicada; reverter/renomear uma migration já
 * aplicada é sempre uma migration nova).
 *
 * Ordem: tabelas primeiro, depois colunas dentro das tabelas já
 * renomeadas (uma FK aponta para o NOME ATUAL da tabela — MySQL/MariaDB
 * atualiza a constraint automaticamente quando a tabela referenciada é
 * renomeada, sem precisar dropar/recriar a FK).
 *
 * Inclui também a atualização de `activity_log.subject_type` para as 3
 * classes renomeadas — sem isso, os logs de auditoria já existentes
 * (criados enquanto o cadastro se chamava "Obra") ficariam órfãos: o
 * `subject_type` gravado pelo Spatie Activitylog é o FQCN cru (sem
 * `Relation::morphMap()` neste projeto — ver SubjectTypeCatalog), então o
 * histórico de auditoria só continua reconhecendo esses registros se a
 * string salva acompanhar o rename da classe.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('situacoes_obra', 'situacoes_projeto');
        Schema::rename('tipos_obra', 'tipos_projeto');
        Schema::rename('obras', 'projetos');
        Schema::rename('obra_numero_sequencias', 'projeto_numero_sequencias');
        Schema::rename('obra_situacao', 'projeto_situacao');

        Schema::table('projeto_numero_sequencias', function (Blueprint $table) {
            $table->renameColumn('tipo_obra_id', 'tipo_projeto_id');
        });

        Schema::table('projetos', function (Blueprint $table) {
            $table->renameColumn('tipo_obra_id', 'tipo_projeto_id');
            $table->renameColumn('numero_obra', 'numero_projeto');
        });

        Schema::table('projeto_situacao', function (Blueprint $table) {
            $table->renameColumn('obra_id', 'projeto_id');
            $table->renameColumn('situacao_obra_id', 'situacao_projeto_id');
        });

        $this->renameSubjectTypes([
            'Perseu\\Comercial\\Models\\Obra' => 'Perseu\\Comercial\\Models\\Projeto',
            'Perseu\\Comercial\\Models\\SituacaoObra' => 'Perseu\\Comercial\\Models\\SituacaoProjeto',
            'Perseu\\Comercial\\Models\\TipoObra' => 'Perseu\\Comercial\\Models\\TipoProjeto',
        ]);
    }

    public function down(): void
    {
        $this->renameSubjectTypes([
            'Perseu\\Comercial\\Models\\Projeto' => 'Perseu\\Comercial\\Models\\Obra',
            'Perseu\\Comercial\\Models\\SituacaoProjeto' => 'Perseu\\Comercial\\Models\\SituacaoObra',
            'Perseu\\Comercial\\Models\\TipoProjeto' => 'Perseu\\Comercial\\Models\\TipoObra',
        ]);

        Schema::table('projeto_situacao', function (Blueprint $table) {
            $table->renameColumn('projeto_id', 'obra_id');
            $table->renameColumn('situacao_projeto_id', 'situacao_obra_id');
        });

        Schema::table('projetos', function (Blueprint $table) {
            $table->renameColumn('tipo_projeto_id', 'tipo_obra_id');
            $table->renameColumn('numero_projeto', 'numero_obra');
        });

        Schema::table('projeto_numero_sequencias', function (Blueprint $table) {
            $table->renameColumn('tipo_projeto_id', 'tipo_obra_id');
        });

        Schema::rename('projeto_situacao', 'obra_situacao');
        Schema::rename('projeto_numero_sequencias', 'obra_numero_sequencias');
        Schema::rename('projetos', 'obras');
        Schema::rename('tipos_projeto', 'tipos_obra');
        Schema::rename('situacoes_projeto', 'situacoes_obra');
    }

    /**
     * @param  array<string, string>  $map  FQCN antigo => FQCN novo
     */
    private function renameSubjectTypes(array $map): void
    {
        if (! Schema::hasTable('activity_log')) {
            return;
        }

        foreach ($map as $de => $para) {
            DB::table('activity_log')->where('subject_type', $de)->update(['subject_type' => $para]);
        }
    }
};
