<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pessoas_juridicas', function (Blueprint $table) {
            $table->id();
            $table->string('razao_social')->nullable();
            $table->string('nome_fantasia');
            $table->string('cnpj')->nullable()->unique();
            $table->string('inscricao_estadual')->nullable();
            $table->string('cnae')->nullable();
            $table->unsignedTinyInteger('regime_tributario')->nullable();
            $table->date('data_abertura')->nullable();
            $table->string('email')->nullable();
            $table->string('telefone');
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pessoas_juridicas');
    }
};
