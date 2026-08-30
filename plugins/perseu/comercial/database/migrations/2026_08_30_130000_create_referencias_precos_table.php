<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cadastro de apoio do Cluster "Referências" (ver CLAUDE.md) — várias
 * tabelas de preços podem coexistir simultaneamente (ex.: "Tabela
 * Padrão", "Tabela Cliente Corporativo"), escolhidas na hora de montar
 * uma Proposta (funcionalidade futura, fora de escopo aqui). NÃO é
 * histórico/versionamento no tempo — todos os registros ficam "vivos"
 * ao mesmo tempo, como Categoria ou Setor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referencias_precos', function (Blueprint $table) {
            $table->id();
            $table->string('descricao');
            $table->decimal('laminacao', 10, 2)->nullable();
            $table->decimal('corte', 10, 2)->nullable();
            $table->decimal('hora_producao', 10, 2)->nullable();
            $table->decimal('hora_execucao', 10, 2)->nullable();
            $table->decimal('retencao_tecnica', 5, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referencias_precos');
    }
};
