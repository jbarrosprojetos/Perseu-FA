<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projeto_situacao', function (Blueprint $table) {
            $table->foreignId('projeto_id')->constrained('projetos')->cascadeOnDelete();
            $table->foreignId('situacao_projeto_id')->constrained('situacoes_projeto')->cascadeOnDelete();

            $table->primary(['projeto_id', 'situacao_projeto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projeto_situacao');
    }
};
