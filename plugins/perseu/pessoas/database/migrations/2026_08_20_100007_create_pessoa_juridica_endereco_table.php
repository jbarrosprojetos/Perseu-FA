<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pessoa_juridica_endereco', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pessoa_juridica_id')
                ->constrained('pessoas_juridicas')
                ->cascadeOnDelete();

            $table->foreignId('endereco_id')
                ->constrained('enderecos')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('tipo');
            $table->boolean('principal')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pessoa_juridica_endereco');
    }
};
