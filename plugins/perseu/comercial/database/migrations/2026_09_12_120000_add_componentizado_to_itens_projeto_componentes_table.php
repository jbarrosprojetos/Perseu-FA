<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-09-12 — achado real ao ampliar `PromobXmlParser::componentesParaMateriais()`
 * pra também capturar ferragens/acessórios (`COMPONENT="N"` sem
 * filhos — antes ficavam de fora, só entravam agregados em "Misc";
 * ver docblock de `componentesParaMateriais()`): a fórmula original de
 * Custo Unitário (`ProjetoResource::calcularDetalhamentoCustoPromob()`)
 * usa "Misc" como "tudo que NÃO é peça de madeira/painel
 * (`COMPONENT="Y"`)" — ao passar a extrair ferragens como componentes
 * próprios, "Misc" (calculado por subtração, ver `custo_total_xml`)
 * iria a praticamente zero, quebrando a linha "Ferragens/Miscelânea"
 * do cálculo AO VIVO (`ItemProjeto::metricasPromob()`).
 *
 * `componentizado` guarda o `COMPONENT` ORIGINAL do XML (`true` = "Y",
 * peça de madeira/painel; `false` = "N", ferragem/acessório) — permite
 * reconstruir ao vivo a MESMA divisão Custo(madeira)/Misc(ferragens)
 * que `PromobXmlParser::metricas()` sempre fez, sem depender só de
 * `custo_total_xml` (que segue existindo, mas agora só como valor de
 * conferência/auditoria, não mais fonte principal de "Misc").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itens_projeto_componentes', function (Blueprint $table) {
            $table->boolean('componentizado')->default(true)->after('origem');
        });
    }

    public function down(): void
    {
        Schema::table('itens_projeto_componentes', function (Blueprint $table) {
            $table->dropColumn('componentizado');
        });
    }
};
