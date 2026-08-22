<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projeto_numero_sequencias', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('ano');
            $table->foreignId('tipo_projeto_id')->constrained('tipos_projeto');
            $table->unsignedInteger('ultimo_sequencial')->default(0);
            $table->timestamps();

            $table->unique(['ano', 'tipo_projeto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projeto_numero_sequencias');
    }
};
