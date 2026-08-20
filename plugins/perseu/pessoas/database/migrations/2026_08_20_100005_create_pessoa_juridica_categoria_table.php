<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pessoa_juridica_categoria', function (Blueprint $table) {
            $table->foreignId('pessoa_juridica_id')
                ->constrained('pessoas_juridicas')
                ->cascadeOnDelete();

            $table->foreignId('categoria_pessoa_id')
                ->constrained('categorias_pessoa')
                ->cascadeOnDelete();

            $table->primary(['pessoa_juridica_id', 'categoria_pessoa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pessoa_juridica_categoria');
    }
};
