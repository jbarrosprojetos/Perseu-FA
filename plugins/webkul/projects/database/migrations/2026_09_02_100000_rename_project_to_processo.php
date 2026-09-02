<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Renomeia a entidade "Project" para "Processo" dentro do plugin
 * `webkul/projects` — "Projeto" fica reservado pra entidade de negócio
 * em `perseu/comercial` (ver CLAUDE.md e CONCEITO-OBRA-PROPOSTA-PROJETO.md).
 *
 * Tabelas primeiro (`Schema::rename()`), colunas depois (`renameColumn()`,
 * já com o nome novo da tabela) — mesma ordem/convenção usada no rename
 * Projeto→Obra em `perseu/comercial`. FKs entre as tabelas renomeadas
 * continuam funcionando sem precisar dropar/recriar (MySQL/MariaDB
 * atualiza a constraint automaticamente para apontar pro novo nome de
 * tabela/coluna).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('projects_projects', 'projects_processos');
        Schema::rename('projects_project_stages', 'projects_processo_stages');
        Schema::rename('projects_project_tag', 'projects_processo_tag');
        Schema::rename('projects_user_project_favorites', 'projects_user_processo_favorites');

        Schema::table('projects_tasks', function (Blueprint $table) {
            $table->renameColumn('project_id', 'processo_id');
        });

        Schema::table('projects_milestones', function (Blueprint $table) {
            $table->renameColumn('project_id', 'processo_id');
        });

        Schema::table('projects_task_stages', function (Blueprint $table) {
            $table->renameColumn('project_id', 'processo_id');
        });

        Schema::table('projects_processo_tag', function (Blueprint $table) {
            $table->renameColumn('project_id', 'processo_id');
        });

        Schema::table('projects_user_processo_favorites', function (Blueprint $table) {
            $table->renameColumn('project_id', 'processo_id');
        });

        Schema::table('analytic_records', function (Blueprint $table) {
            $table->renameColumn('project_id', 'processo_id');
        });
    }

    public function down(): void
    {
        Schema::table('analytic_records', function (Blueprint $table) {
            $table->renameColumn('processo_id', 'project_id');
        });

        Schema::table('projects_user_processo_favorites', function (Blueprint $table) {
            $table->renameColumn('processo_id', 'project_id');
        });

        Schema::table('projects_processo_tag', function (Blueprint $table) {
            $table->renameColumn('processo_id', 'project_id');
        });

        Schema::table('projects_task_stages', function (Blueprint $table) {
            $table->renameColumn('processo_id', 'project_id');
        });

        Schema::table('projects_milestones', function (Blueprint $table) {
            $table->renameColumn('processo_id', 'project_id');
        });

        Schema::table('projects_tasks', function (Blueprint $table) {
            $table->renameColumn('processo_id', 'project_id');
        });

        Schema::rename('projects_user_processo_favorites', 'projects_user_project_favorites');
        Schema::rename('projects_processo_tag', 'projects_project_tag');
        Schema::rename('projects_processo_stages', 'projects_project_stages');
        Schema::rename('projects_processos', 'projects_projects');
    }
};
