<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Histórico de notas/anotações de um Projeto — ícone na Section
 * "Cabeçalho" do `ProjetoResource` (ver CLAUDE.md, "Notas do Projeto").
 *
 * `usuario_id` NULLABLE + `nullOnDelete()` — decisão registrada: uma
 * nota de USUÁRIO sempre grava o autenticado no momento da criação
 * (nunca nulo na prática, ver `ProjetoResource::adicionarNotaProjeto()`);
 * fica nullable só para (a) sobreviver caso o usuário autor seja
 * excluído depois (`nullOnDelete()`, não `cascadeOnDelete()` — perder o
 * AUTOR não deveria apagar o HISTÓRICO) e (b) para uma nota de SISTEMA
 * futura que não tenha um usuário claro por trás da ação que a gerou
 * (a geração automática de notas do sistema em si é uma tarefa futura,
 * ver CLAUDE.md — esta migration só prepara a coluna).
 *
 * `numero_nota` sequencial POR Projeto (`unique(['projeto_id',
 * 'numero_nota'])`), gerado em `NotaProjeto::boot()` (mesmo critério de
 * `ItemProjeto::numero_item`) — mas SEM renumeração ao excluir (ver
 * `ProjetoResource::excluirNotaProjeto()`/CLAUDE.md): aqui o número é
 * só um identificador sequencial de cada nota ao longo do tempo, não
 * uma lista visível ao cliente, então pode ficar com buraco depois de
 * uma exclusão.
 *
 * SEM `softDeletes()` (mesma divergência deliberada de `itens_projeto`
 * da convenção padrão de Model de negócio) — mas aqui não é por causa
 * de renumeração (que não existe): é porque `tipo_sistema` já cobre a
 * única exclusão que "importa" preservar (o sistema nunca perde uma
 * nota seguindo o fluxo normal da tela, já que notas de sistema não
 * são excluíveis pela UI) — uma nota de usuário excluída dentro do
 * prazo de 24h não precisa de Lixeira própria.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notas_projeto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projeto_id')->constrained('projetos')->cascadeOnDelete();
            $table->unsignedInteger('numero_nota');
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('texto');
            $table->boolean('tipo_sistema')->default(false);
            $table->timestamps();

            $table->unique(['projeto_id', 'numero_nota']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas_projeto');
    }
};
