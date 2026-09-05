<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Itens que compõem um Projeto (Section "Itens" do ProjetoResource) —
 * hoje só "Item Avulso" grava de fato (ver CLAUDE.md), mas a tabela já
 * nasce pensada pras outras 6 origens do Select
 * (`origem_item_selecionada`/`Perseu\Comercial\Enums\OrigemItemProjeto`):
 * item de linha vinculado a um Produto de cadastro, Promob, Sketchup,
 * CortCloud — ver CONCEITO-OBRA-PROPOSTA-PROJETO.md, "Itens do Projeto:
 * dois tipos, não um cadastro único".
 *
 * `produto_id`/`situacao_item_id` são colunas RESERVADAS, sem FK de
 * verdade — os cadastros de Produto e Situação de Item ainda não
 * existem no sistema (confirmado por grep, nenhum Model/tabela com
 * esse nome em nenhum plugin). Adicionar o `->constrained()` numa
 * migration nova quando esses cadastros forem criados.
 *
 * SEM `softDeletes()` (de propósito, diferente da convenção padrão de
 * Model de negócio) — excluir um item RENUMERA os itens seguintes
 * daquele Projeto pra fechar o buraco na sequência (`numero_item`), e
 * isso exige que o número excluído fique DE VERDADE livre; uma linha
 * soft-deleted continuaria ocupando o slot no índice único
 * `(projeto_id, numero_item)` abaixo, bloqueando a renumeração do
 * item seguinte pra esse mesmo número. Ver `ProjetoResource
 * ::excluirItemAvulso()`/CLAUDE.md pra a decisão completa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itens_projeto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projeto_id')->constrained('projetos')->cascadeOnDelete();
            $table->string('numero_item', 3);
            $table->string('origem');
            $table->unsignedBigInteger('produto_id')->nullable();
            $table->text('descricao')->nullable();
            $table->integer('quantidade')->nullable();
            $table->decimal('valor_unitario', 10, 2)->nullable();
            $table->decimal('valor_total', 10, 2)->nullable();
            $table->decimal('porcentagem', 5, 2)->nullable();
            $table->decimal('custo_unitario', 10, 2)->nullable();
            // Cópia (snapshot) do `imposto` da Referência de Preços
            // efetivamente usado no cálculo de valor_unitario/valor_total
            // no momento da criação/edição — NÃO um FK vivo pra
            // referencias_precos.imposto, de propósito: preserva o
            // histórico do cálculo mesmo que a Referência de Preços mude
            // depois (achado real de concorrência, ver
            // INVESTIGACAO-TRANSACOES-CONCORRENCIA.md, risco "Imposto
            // obsoleto" — sem esta coluna não havia como auditar depois
            // "por que este item foi calculado com este valor").
            $table->decimal('imposto_aplicado', 5, 2)->nullable();
            $table->unsignedBigInteger('situacao_item_id')->nullable();
            $table->timestamps();

            $table->unique(['projeto_id', 'numero_item']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('itens_projeto');
    }
};
