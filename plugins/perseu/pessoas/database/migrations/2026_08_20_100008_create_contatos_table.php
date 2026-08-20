<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contatos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pessoa_fisica_id')
                ->constrained('pessoas_fisicas')
                ->cascadeOnDelete();

            $table->foreignId('pessoa_juridica_id')
                ->constrained('pessoas_juridicas')
                ->cascadeOnDelete();

            $table->string('cargo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contatos');
    }
};
