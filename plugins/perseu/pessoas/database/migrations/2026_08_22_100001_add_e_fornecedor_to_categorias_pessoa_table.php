<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categorias_pessoa', function (Blueprint $table) {
            $table->boolean('e_fornecedor')->default(false)->after('e_cliente');
        });
    }

    public function down(): void
    {
        Schema::table('categorias_pessoa', function (Blueprint $table) {
            $table->dropColumn('e_fornecedor');
        });
    }
};
