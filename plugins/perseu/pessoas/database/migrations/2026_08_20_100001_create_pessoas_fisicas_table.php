<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pessoas_fisicas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('telefone');
            $table->boolean('telefone_whatsapp')->default(false);
            $table->string('email')->nullable();
            $table->date('data_nascimento')->nullable();
            $table->string('rg')->nullable();
            $table->string('cpf')->nullable()->unique();
            $table->unsignedTinyInteger('estado_civil')->nullable();
            $table->unsignedTinyInteger('sexo')->nullable();
            $table->string('profissao')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pessoas_fisicas');
    }
};
