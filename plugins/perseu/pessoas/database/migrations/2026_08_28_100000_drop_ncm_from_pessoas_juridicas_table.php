<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reverte 2026_08_27_100000_add_ncm_to_pessoas_juridicas_table — NCM é
 * classificação de produto/mercadoria, não de empresa; foi um equívoco de
 * escopo (ver CLAUDE.md). Não editamos a migration antiga porque ela já
 * foi commitada/rodada; esta migration nova é a forma correta de reverter.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pessoas_juridicas', function (Blueprint $table) {
            $table->dropColumn('ncm');
        });
    }

    public function down(): void
    {
        Schema::table('pessoas_juridicas', function (Blueprint $table) {
            $table->string('ncm')->nullable()->after('cnae');
        });
    }
};
