<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pessoa_juridica_setor', function (Blueprint $table) {
            $table->foreignId('pessoa_juridica_id')
                ->constrained('pessoas_juridicas')
                ->cascadeOnDelete();

            $table->foreignId('setor_id')
                ->constrained('setores')
                ->cascadeOnDelete();

            $table->primary(['pessoa_juridica_id', 'setor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pessoa_juridica_setor');
    }
};
