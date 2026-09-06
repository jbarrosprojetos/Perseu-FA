<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de "Mobilização e Frete" vinculado 1-pra-1 a um
 * `ItemProjeto` (origem `OrigemItemProjeto::MobilizacaoFrete`) — só os
 * campos IMPUTADOS pelo usuário no modal (ver
 * `ProjetoResource::camposFormularioMobilizacaoFrete()`); os totais
 * (mobilização vistoria/obra, frete, geral) são CALCULADOS sob demanda
 * (`ProjetoResource::calcularTotaisMobilizacaoFrete()`), de propósito
 * NÃO persistidos aqui — decisão do usuário, ver CLAUDE.md,
 * "Mobilização e Frete: tabela `fretes_mobilizacao`".
 *
 * `item_projeto_id` é `unique()` (não só indexado) — reforça no banco a
 * relação 1-pra-1 com `ItemProjeto` (cada Item de origem Mobilização e
 * Frete tem NO MÁXIMO um registro de Frete vinculado), mesmo padrão de
 * "chave estrangeira única" já usado por outras relações 1-pra-1 no
 * sistema.
 *
 * SEM `SoftDeletes` (mesmo critério do `ItemProjeto`, ver a migration
 * dele) — excluir o Item (`cascadeOnDelete()` abaixo) deve excluir o
 * registro de Frete junto, sem deixar rastro órfão nem bloquear reuso
 * do vínculo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fretes_mobilizacao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projeto_id')->constrained('projetos')->cascadeOnDelete();
            $table->foreignId('item_projeto_id')->unique()->constrained('itens_projeto')->cascadeOnDelete();

            // Mobilização (vistoria e obra) — linhas 3/4/5/6 da planilha
            // de referência da F.A. Marcenaria.
            $table->unsignedInteger('prazo_obra_dias')->nullable();
            $table->unsignedInteger('qtde_vistoria')->nullable();
            $table->unsignedInteger('funcionarios_vistoria')->nullable();
            $table->unsignedInteger('funcionarios_obra')->nullable();

            // Alimentação e hospedagem (por refeição/diária, linhas 7-10).
            $table->decimal('valor_cafe_manha', 10, 2)->nullable();
            $table->decimal('valor_almoco', 10, 2)->nullable();
            $table->decimal('valor_jantar', 10, 2)->nullable();
            $table->decimal('valor_hotel', 10, 2)->nullable();

            // Viagem aérea/rodoviária (linhas 11-13).
            $table->unsignedInteger('dias_viagem')->nullable();
            $table->decimal('valor_aviao', 10, 2)->nullable();
            $table->decimal('valor_onibus', 10, 2)->nullable();

            // Frete e translado local — modelo simplificado (2026-09-06):
            // um único valor por viagem, sem tentar identificar região;
            // `km` fica só como referência informativa (sem campo de
            // valor por km neste modelo, ver CLAUDE.md).
            $table->decimal('km', 10, 2)->nullable();
            $table->unsignedInteger('qtde_frete')->nullable();
            $table->decimal('valor_frete_viagem', 10, 2)->nullable();
            $table->decimal('valor_translado', 10, 2)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fretes_mobilizacao');
    }
};
