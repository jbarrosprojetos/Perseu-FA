<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-09-12 — decisão do usuário de parar de gravar o detalhamento do
 * cálculo Promob como texto congelado numa `NotaProjeto` (ver
 * `ProjetoResource::renderizarResumoCalculoItemPromob()`, mecanismo
 * REMOVIDO nesta mesma tarefa) e passar a recalcular a aba "Cálculos"
 * SEMPRE AO VIVO, a partir dos componentes persistidos
 * (`itens_projeto_componentes`) e dos Fatores ATUAIS da Referência de
 * Preços do Projeto — ver handoff `handoff_aba_p.md`.
 *
 * Das 5 métricas do cálculo (`PromobXmlParser::metricas()` — Peças/m²/
 * Metro Linear/Custo/Misc), as 4 primeiras já são recalculáveis
 * somando os componentes salvos (`ItemProjeto::metricasPromob()`). A
 * exceção é "Misc" (= custo total bruto do XML daquele item MENOS a
 * soma dos componentes — tudo que não é um componente individualizado
 * no XML: ferragens não detalhadas, fita de LED etc., ver docblock de
 * `PromobXmlParser::metricas()`) — esse total bruto não é recuperável
 * só a partir dos componentes, por isso `custo_total_xml` abaixo.
 *
 * `arquivo_origem`/`arquivo_gerado_em` — nome do arquivo XML e
 * data/hora que o Promob gravou nele (`DATE`/`HOUR` de `<LISTING>`),
 * capturados no mesmo momento de "Criar Itens". Substituem a antiga
 * `NotaProjeto` de sistema como registro "de qual arquivo veio este
 * Item" — dado estruturado no próprio Item, não mais texto solto
 * dentro de uma Nota (que exigia um mecanismo de busca/parse à parte).
 * Só o CUSTO importa aqui (nunca o "Preço"/margem do Promob) — decisão
 * do usuário (2026-09-12): a margem de venda é sempre calculada pelo
 * Perseu, via Referência de Preços, nunca herdada do Promob.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itens_projeto', function (Blueprint $table) {
            $table->decimal('custo_total_xml', 10, 2)->nullable()->after('custo_unitario');
            $table->string('arquivo_origem')->nullable()->after('custo_total_xml');
            $table->string('arquivo_gerado_em')->nullable()->after('arquivo_origem');
        });
    }

    public function down(): void
    {
        Schema::table('itens_projeto', function (Blueprint $table) {
            $table->dropColumn(['custo_total_xml', 'arquivo_origem', 'arquivo_gerado_em']);
        });
    }
};
