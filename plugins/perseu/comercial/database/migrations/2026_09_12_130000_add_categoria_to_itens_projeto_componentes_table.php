<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-09-12 — a pedido do usuário, reforça o aproveitamento da
 * estrutura do XML do Promob: guarda o `CATEGORY/@DESCRIPTION` de onde
 * cada componente foi extraído (ver docblock de
 * `PromobXmlParser::componentesParaMateriais()`). Não é usado pra
 * decidir `componentizado` (isso continua vindo do `COMPONENT` do
 * Promob — nomes de categoria variam demais: numerado `"001 "`, nome
 * de ambiente `"Cozinha"`/`"Dormitório"`/`"Construtor de Armários"`
 * pras peças de madeira/painel, ou nome de fabricante/finalidade
 * `"Acessórios"`/`"Hettich"`/`"Processo de Fabricação"` pras
 * categorias-irmãs de ferragem — nenhuma lista fixa é confiável). Serve
 * só como metadado pra agrupar a lista de Materiais por ambiente/
 * fabricante na tela e, no futuro, na Aba P/Plano de Corte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itens_projeto_componentes', function (Blueprint $table) {
            $table->string('categoria')->nullable()->after('componentizado');
        });
    }

    public function down(): void
    {
        Schema::table('itens_projeto_componentes', function (Blueprint $table) {
            $table->dropColumn('categoria');
        });
    }
};
