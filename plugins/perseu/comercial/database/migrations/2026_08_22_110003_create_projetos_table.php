<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projetos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pessoa_fisica_id')->nullable()->constrained('pessoas_fisicas');
            $table->foreignId('pessoa_juridica_id')->nullable()->constrained('pessoas_juridicas');
            $table->foreignId('contato_pessoa_fisica_id')->nullable()->constrained('pessoas_fisicas');
            $table->foreignId('tipo_projeto_id')->constrained('tipos_projeto');
            $table->foreignId('endereco_id')->nullable()->constrained('enderecos');
            $table->string('descricao');
            $table->unsignedInteger('revisao')->default(0);
            $table->string('numero_projeto')->unique();
            $table->dateTime('data_cadastro');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projetos');
    }
};
