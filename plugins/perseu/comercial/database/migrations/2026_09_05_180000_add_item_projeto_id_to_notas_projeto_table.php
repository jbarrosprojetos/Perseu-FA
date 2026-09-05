<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `item_projeto_id` — vínculo opcional de uma nota com um Item
 * específico do Projeto (ver `NotaProjeto::item()`/CLAUDE.md, "Notas do
 * Projeto"). Base do futuro ícone "Cálculos" no menu de cada item (uma
 * tarefa futura, junto da criação de Itens via Promob) — esta migration
 * só prepara a COLUNA e a possibilidade de vincular, sem nenhuma lógica
 * de geração automática ainda.
 *
 * `NULL` cobre TRÊS casos possíveis (nota de usuário; nota de sistema
 * sobre o Projeto como um todo; nota de sistema sobre um item
 * específico é o ÚNICO caso com valor) — a combinação de
 * `tipo_sistema` + `item_projeto_id` já diferencia os 3, sem precisar
 * de nenhum valor sentinela tipo "item 0".
 *
 * `nullOnDelete()` (não `cascadeOnDelete()`) — mesma decisão já tomada
 * pra `usuario_id`: excluir o Item não deveria apagar o HISTÓRICO da
 * nota, só o vínculo específico com aquele item.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notas_projeto', function (Blueprint $table) {
            $table->foreignId('item_projeto_id')
                ->nullable()
                ->after('usuario_id')
                ->constrained('itens_projeto')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('notas_projeto', function (Blueprint $table) {
            $table->dropConstrainedForeignId('item_projeto_id');
        });
    }
};
