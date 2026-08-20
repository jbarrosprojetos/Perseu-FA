<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pessoa_fisica_endereco', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pessoa_fisica_id')
                ->constrained('pessoas_fisicas')
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
        Schema::dropIfExists('pessoa_fisica_endereco');
    }
};
