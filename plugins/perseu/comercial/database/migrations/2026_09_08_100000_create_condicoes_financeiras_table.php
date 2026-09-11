<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de Condições Financeiras (2026-09-08) — mesmo padrão de
 * `ReferenciaPreco` (ver CLAUDE.md, "Cluster 'Referências' e Referência
 * de Preços"): várias condições coexistindo ao mesmo tempo (NÃO é
 * histórico/versionamento), cada registro reutilizável por vários
 * Projetos, escolhida via `projetos.condicao_financeira_id` (migration
 * irmã `2026_09_08_100001_add_condicao_financeira_id_to_projetos_table`).
 *
 * `taxa_mensal` é a taxa de juros ao mês (ex.: 5,00 = "5% a.m."),
 * usada para calcular o FATOR de amortização pelo Sistema Price
 * (fator = i / (1 - (1+i)^-n)) em tempo real, de acordo com a
 * `qtde_parcelas` escolhida no Projeto — decisão explícita do usuário:
 * NÃO armazenar uma tabela fixa de fatores por quantidade de parcelas
 * (a tabela de exemplo com 1x–10x foi só ilustração da fórmula), o
 * fator é sempre recalculado a partir da taxa mensal cadastrada aqui.
 * O cálculo do Total do Projeto usando este fator fica para uma tarefa
 * futura (ver CLAUDE.md) — esta migration só registra o modelo de
 * dados.
 *
 * `porcentagem_entrada`/`intervalo_dias`: por ora apenas informativos
 * (a regra de como a entrada desconta do valor financiado e se o
 * intervalo entra em algum cálculo futuro de vencimento/boleto ainda
 * será definida na tarefa do cálculo do Total).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('condicoes_financeiras', function (Blueprint $table) {
            $table->id();
            $table->string('descricao');
            $table->decimal('porcentagem_entrada', 5, 2)->nullable()->default(0);
            $table->unsignedInteger('qtde_parcelas')->nullable()->default(1);
            $table->unsignedInteger('intervalo_dias')->nullable()->default(30);
            $table->string('forma_pagamento')->nullable();
            $table->decimal('taxa_mensal', 5, 2)->nullable()->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('condicoes_financeiras');
    }
};
