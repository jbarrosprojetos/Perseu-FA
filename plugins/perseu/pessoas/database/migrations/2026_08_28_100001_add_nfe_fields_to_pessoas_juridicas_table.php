<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campos necessários para viabilizar a futura emissão de NF-e (ver
 * CLAUDE.md). `situacao_cadastral`/`descricao_situacao_cadastral` são
 * somente leitura no formulário (refletem a Receita Federal, nunca
 * digitados pelo usuário) — guardados como string porque o código vem da
 * API como inteiro pequeno (ex: 2) mas não há benefício em modelar como
 * enum aqui (não é uma escolha do usuário, só exibição).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pessoas_juridicas', function (Blueprint $table) {
            $table->string('cnae_descricao')->nullable()->after('cnae');
            $table->string('porte')->nullable()->after('data_abertura');
            $table->string('descricao_porte')->nullable()->after('porte');
            $table->string('situacao_cadastral')->nullable()->after('descricao_porte');
            $table->string('descricao_situacao_cadastral')->nullable()->after('situacao_cadastral');
            $table->unsignedTinyInteger('indicador_contribuinte_icms')->nullable()->after('inscricao_estadual');
        });
    }

    public function down(): void
    {
        Schema::table('pessoas_juridicas', function (Blueprint $table) {
            $table->dropColumn([
                'cnae_descricao',
                'porte',
                'descricao_porte',
                'situacao_cadastral',
                'descricao_situacao_cadastral',
                'indicador_contribuinte_icms',
            ]);
        });
    }
};
