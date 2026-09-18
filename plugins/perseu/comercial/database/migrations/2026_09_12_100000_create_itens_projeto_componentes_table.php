<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Componentes (matéria-prima) extraídos do XML do Promob e persistidos
 * no momento de "Criar Itens" (junto com o `ItemProjeto`, mesma
 * transação) — base da Aba P (Lista de Compras/Necessidade de
 * Materiais) e do Plano de Corte (2026-09-12, ver CLAUDE.md "Fluxo
 * Promob" e handoff `handoff_aba_p.md`).
 *
 * Cada linha é um componente `ITEM[@COMPONENT="Y"]` do XML (chapa,
 * fita, ferragem, puxador etc. — ver `PromobXmlParser`), lido tal como
 * o Promob já calculou (regra de ouro do parser: nunca recalcula
 * nada). Vinculado a `item_projeto_id` (não direto ao Projeto) —
 * decisão do usuário (2026-09-12): excluir um `ItemProjeto` já exclui
 * seus componentes junto (`cascadeOnDelete`), mantendo os totais da
 * Lista de Compras sempre consistentes com os Itens realmente
 * existentes no Projeto, sem precisar de rotina de "recalcular" à
 * parte.
 *
 * SEM `softDeletes()` — mesma decisão/motivo de `itens_projeto`
 * (registro operacional, não um cadastro central auditado); e porque o
 * usuário confirmou que quer CRUD completo (editar/adicionar/excluir
 * linha) na Aba P antes de gerar o documento — exclusão aqui precisa
 * ser definitiva, sem linha "fantasma" atrapalhando uma nova extração
 * caso o Item seja recriado a partir de um XML.
 *
 * `origem`: distingue linha extraída automaticamente do XML
 * (`xml_promob`) de linha adicionada/ajustada manualmente pelo usuário
 * depois (`manual`) — necessário porque a extração roda de novo se o
 * usuário excluir o Item e recriá-lo via Promob; sem essa marcação não
 * haveria como saber quais linhas eram ajustes manuais a preservar ou
 * descartar nesse recálculo (decisão de UX de re-extração fica para a
 * tarefa de implementação da Section, não desta migration).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itens_projeto_componentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_projeto_id')->constrained('itens_projeto')->cascadeOnDelete();
            $table->string('origem')->default('xml_promob');
            $table->string('referencia')->nullable();
            $table->string('descricao');

            // Dimensões em mm, cruas do XML (WIDTH/HEIGHT/DEPTH) — sem
            // conversão pra cm/m aqui, mesma convenção do
            // PromobXmlParser (quem exibe formata na hora de mostrar).
            $table->decimal('largura', 10, 2)->nullable();
            $table->decimal('altura', 10, 2)->nullable();
            $table->decimal('profundidade', 10, 2)->nullable();

            // REPETITION do Promob — quantas peças deste componente
            // existem no item (ex.: 4 pés iguais de uma mesa).
            $table->integer('repeticao')->default(1);

            // QUANTITY do Promob — métrica própria do componente usada
            // pelo Promob no cálculo de m² (REPETITION × QUANTITY, ver
            // PromobXmlParser::metricas()); mantido cru, sem reinventar
            // o que ele significa por tipo de material.
            $table->decimal('quantidade', 12, 4)->nullable();

            $table->decimal('custo', 10, 2)->default(0);
            $table->decimal('preco', 10, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('itens_projeto_componentes');
    }
};
