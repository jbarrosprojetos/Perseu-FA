<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vínculo opcional (FK nullable) de Projeto com ReferenciaPreco — usado
 * futuramente para calcular o valor de Venda do Projeto (ver CLAUDE.md,
 * "Trava de exclusão/edição com Projeto vinculado"). `nullOnDelete()`
 * em vez de `restrict`/`cascade`: o vínculo pode ficar sem Referência
 * (campo opcional por design), então se uma ReferenciaPreco algum dia
 * for excluída definitivamente (Lixeira → Excluir Permanentemente) sem
 * a trava de aplicação ter impedido antes por algum motivo, o Projeto
 * não deve travar/quebrar — só perde a referência. A trava de negócio
 * de verdade (não pode excluir/editar Referência com Projeto vinculado)
 * é feita na camada de aplicação (Policy), não depende desta FK.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projetos', function (Blueprint $table) {
            $table->foreignId('referencia_preco_id')
                ->nullable()
                ->after('endereco_id')
                ->constrained('referencias_precos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projetos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referencia_preco_id');
        });
    }
};
