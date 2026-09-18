<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-09-17 — corrige a interpretação de `PLATECUTTINGROTATE` usada
 * pelo Plano de Corte. A migration `2026_09_13_100000_...` guardou
 * `veio_travado` (bool) como `PLATECUTTINGROTATE === "Y"`, tratando
 * `"N"`/`"NONE"` como igualmente "livre pra rotacionar". Usuário
 * reportou uma peça real (Carvalho Mel Sonoma, `Tamponamento Inferior
 * 18`, 800×220, `PLATECUTTINGROTATE="N"`) com o veio errado no nosso
 * Plano de Corte mas correto no Promob Cut Pro real — investigação
 * cruzando o XML do projeto com o PDF real do Promob Cut Pro do MESMO
 * projeto (Lista de Cortes, coluna "Dimensão", casado por `UNIQUEID`)
 * confirmou em 11 de 11 peças conferidas: "Y" e "N" são duas travas
 * DIFERENTES (qual dimensão crua — `largura` ou `profundidade` — fica
 * no eixo do veio da chapa), nunca "livre". Só ausente/"NONE" é livre
 * de verdade. Ver `PromobXmlParser`/`PlanoCorteNestingService`,
 * "Revisão 2026-09-17", pra evidência completa.
 *
 * Campo NOVO em vez de reaproveitar `veio_travado` (que fica mantido,
 * só por compatibilidade — nenhum código novo deve lê-lo): registros
 * já persistidos foram calculados com a interpretação antiga e
 * incorreta, então uma migração de dados in-place aqui não teria como
 * recuperar sozinha qual era o `PLATECUTTINGROTATE` original de cada
 * linha. O caminho já em uso neste plugin pra refresh de componentes
 * é excluir + reimportar o XML do Promob (ver `OrigemComponenteItem`)
 * — isso repopula `veio_eixo_fixo` corretamente a partir do XML.
 * Nullable: `null` = sem veio (`"NONE"`/ausente), livre de verdade.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itens_projeto_componentes', function (Blueprint $table) {
            $table->string('veio_eixo_fixo')->nullable()->after('veio_travado');
        });
    }

    public function down(): void
    {
        Schema::table('itens_projeto_componentes', function (Blueprint $table) {
            $table->dropColumn('veio_eixo_fixo');
        });
    }
};
