<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Coluna `referencia` — reservada desde a criação de `itens_projeto`
 * pro futuro "Item de Linha" (rastreabilidade de onde o item veio),
 * mas nunca chegou a ser adicionada. A tarefa "Criar Itens do Promob"
 * (2026-09-06, ver CLAUDE.md, "Fluxo Promob") passou a criar Itens
 * automaticamente, SEM modal de revisão por item — decisão do usuário
 * pra simplificar o fluxo (menos código, sem o mecanismo frágil de
 * "auto-abrir o próximo modal", ver histórico de bugs no CLAUDE.md).
 *
 * Conteúdo (revisado no mesmo dia): NÃO guarda nome de arquivo + data/
 * hora — só um LABEL curto de origem ("Promob"/"Item Avulso"), pra
 * identificar de relance a origem do item na listagem sem duplicar
 * informação que já existe em outro lugar (nome/data do arquivo Promob
 * ficam na `NotaProjeto` de sistema vinculada, ícone "Cálculos"; a
 * "origem" de um Item Avulso é a própria Descrição digitada pelo
 * usuário). `null` é o valor de repouso normal, sem custo relevante de
 * armazenamento (uma coluna `VARCHAR` nullable com valor `NULL` não
 * ocupa espaço proporcional a um texto qualquer, só a marcação de nulo
 * do próprio motor). Nullable também porque o futuro "Item de Linha"
 * (e depois SketchUp) deve usar esta MESMA coluna de um jeito
 * diferente: não um label estático, mas o código de referência real do
 * Produto vinculado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itens_projeto', function (Blueprint $table) {
            $table->string('referencia')->nullable()->after('origem');
        });
    }

    public function down(): void
    {
        Schema::table('itens_projeto', function (Blueprint $table) {
            $table->dropColumn('referencia');
        });
    }
};
