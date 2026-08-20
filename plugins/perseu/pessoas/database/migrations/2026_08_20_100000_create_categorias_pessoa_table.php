<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_pessoa', function (Blueprint $table) {
            $table->id();
            $table->string('descricao');
            $table->boolean('aplica_pf')->default(false);
            $table->boolean('aplica_pj')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias_pessoa');
    }
};
