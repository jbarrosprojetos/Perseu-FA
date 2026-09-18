<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-09-13 — a pedido do usuário ("agora precisamos... corrigir os
 * itens do Projeto no Perseu que importam do promob para armazenar os
 * dados com melhor estrutura para uso na lista de peças... e para o
 * plano de corte"), acrescenta os campos do `<REFERENCES>`/
 * `PLATECUTTINGROTATE` do Promob levantados no estudo de
 * 2026-09-13 (ver CLAUDE.md, "Estudo do XML do Promob — estrutura
 * completa pra Plano de Corte", seção "O que falta persistir").
 * Escopo travado pelo usuário: só enriquecer os campos (sem hierarquia/
 * árvore de montagem) — ver docblock de
 * `PromobXmlParser::coletarComponentesMateriais()`.
 *
 * Todos nullable — nem toda folha tem `<REFERENCES>` cheio (ferragem de
 * catálogo próprio, processo etc.) nem todo campo aparece em toda
 * peça (ex.: `FORNECEDOR`/`CATEGORIA`/`ATIVO`/`PEDIDOFABRICA` só em
 * ferragem de plugin parceiro como Hettich; `PERIMETRO_FITA` é raro,
 * visto só em "Prateleira Linear" nas fixtures reais).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itens_projeto_componentes', function (Blueprint $table) {
            // `REFERENCES/MATERIAL` — ex. "MDF". Distinto de `categoria`
            // (que vem de `CATEGORY/@DESCRIPTION`, metadado de
            // agrupamento, não de composição física da peça).
            $table->string('material')->nullable()->after('preco');

            // `REFERENCES/MODEL_DESCRIPTION` — cor/acabamento da peça
            // em si (ex. "Branco", "Grafite"). Distinto da cor da fita
            // (`fita_cor`/`fita_cor_frontal` abaixo) — podem divergir
            // na mesma peça (ex. peça branca com fita terracota).
            $table->string('cor')->nullable()->after('material');

            // `REFERENCES/THICKNESS`, em mm — espessura do material da
            // peça (ex. 15, 18). Já existe `HEIGHT`/`altura` bruto do
            // XML (mantido como está, ver ressalva no CLAUDE.md sobre
            // `HEIGHT` nem sempre = "Espessura" na UI do Promob por
            // tipo de peça); este campo vem direto do catálogo do
            // material, não da geometria da peça.
            $table->decimal('espessura', 8, 2)->nullable()->after('cor');

            // Fabricante do material — unifica `REFERENCES/SUPPLIER`
            // (chapa/painel, ex. "Duratex") e `REFERENCES/FORNECEDOR`
            // (ferragem de plugin parceiro, ex. "Hettich"): nas
            // fixtures reais nunca aparecem os dois preenchidos na
            // mesma peça (são categorias de item diferentes), então um
            // único campo textual é suficiente — ver
            // `PromobXmlParser::coletarComponentesMateriais()`.
            $table->string('fornecedor')->nullable()->after('espessura');

            // `REFERENCES/MAXWIDTH`/`MAXDEPTH`, em mm — tamanho da
            // chapa de origem do material (sempre 2750×1830/1850mm nas
            // fixtures reais). Nomeado pelo tamanho físico da chapa
            // (largura/comprimento), não pelos nomes ambíguos
            // WIDTH/DEPTH do XML.
            $table->decimal('chapa_largura', 8, 2)->nullable()->after('fornecedor');
            $table->decimal('chapa_comprimento', 8, 2)->nullable()->after('chapa_largura');

            // `REFERENCES/FITA_BORDA_1..4`, em mm — espessura da fita
            // de borda por lado (0 = sem fita). Par estrutural {1,2}/
            // {3,4} por dimensão da peça — ver CLAUDE.md pro estudo
            // completo (validado com `PERIMETRO_FITA` em 181 itens
            // reais).
            $table->decimal('fita_borda_1', 4, 2)->nullable()->after('chapa_comprimento');
            $table->decimal('fita_borda_2', 4, 2)->nullable()->after('fita_borda_1');
            $table->decimal('fita_borda_3', 4, 2)->nullable()->after('fita_borda_2');
            $table->decimal('fita_borda_4', 4, 2)->nullable()->after('fita_borda_3');

            // `REFERENCES/MODEL_DESCRIPTION_FITA`/`_FRO` — só existem 2
            // "specs" de cor de fita por peça (normal + frontal), não 4
            // independentes (uma por lado) — ver CLAUDE.md.
            $table->string('fita_cor')->nullable()->after('fita_borda_4');
            $table->string('fita_cor_frontal')->nullable()->after('fita_cor');

            // `REFERENCES/PERIMETRO_FITA` — string bruta do Promob
            // (ex. "0+770+0+0", posicional a FITA_BORDA_1..4). Raro nas
            // fixtures reais (só "Prateleira Linear"); guardado como
            // veio do XML, sem parsear, pra eventual conferência
            // cruzada no Plano de Corte.
            $table->string('perimetro_fita')->nullable()->after('fita_cor_frontal');

            // `ITEM/@PLATECUTTINGROTATE` (atributo do próprio ITEM, NÃO
            // de `<REFERENCES>`) — `true` quando "Y" (veio travado/
            // vertical), `false` quando "N"/"NONE" (sem trava ou
            // rotacionado). Provado por experimento controlado do
            // usuário (mesmo projeto exportado 2x, só o veio mudado) —
            // ver CLAUDE.md. Eixo do veio = Altura da peça na UI do
            // Promob (não necessariamente `HEIGHT` do XML — varia por
            // tipo de peça).
            $table->boolean('veio_travado')->default(false)->after('perimetro_fita');
        });
    }

    public function down(): void
    {
        Schema::table('itens_projeto_componentes', function (Blueprint $table) {
            $table->dropColumn([
                'material',
                'cor',
                'espessura',
                'fornecedor',
                'chapa_largura',
                'chapa_comprimento',
                'fita_borda_1',
                'fita_borda_2',
                'fita_borda_3',
                'fita_borda_4',
                'fita_cor',
                'fita_cor_frontal',
                'perimetro_fita',
                'veio_travado',
            ]);
        });
    }
};
