<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Localização do cadastro de Empresa (Company) pro padrão brasileiro de
 * Pessoa Jurídica — Empresa é a emissora principal de NF-e do sistema
 * (ver CLAUDE.md, "Localização do cadastro de Empresa"). Mesmo conjunto
 * de campos já usado em `perseu/pessoas` (`pessoas_juridicas`), SEM
 * duplicar `cnpj`/`data_abertura`: `tax_id`/`founded_date`, já
 * existentes em `companies` desde a criação original (AureusERP),
 * são REAPROVEITADOS pra esse fim — ver decisão documentada no
 * CLAUDE.md sobre por que `tax_id` vira CNPJ em vez de uma coluna nova.
 *
 * `bairro`/`numero` são os únicos dois campos de endereço que
 * `companies` realmente não tinha (o restante — `street1`/`street2`/
 * `city`/`zip`/`state_id`/`country_id` — já existe desde
 * `2025_04_04_061507_add_address_columns_in_companies_table` e é
 * reaproveitado com label brasileiro, sem alterar a coluna).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('nome_fantasia')->nullable()->after('name');
            $table->string('cnae')->nullable()->after('tax_id');
            $table->string('cnae_descricao')->nullable()->after('cnae');
            $table->unsignedTinyInteger('regime_tributario')->nullable()->after('cnae_descricao');
            $table->string('porte')->nullable()->after('regime_tributario');
            $table->string('descricao_porte')->nullable()->after('porte');
            $table->string('situacao_cadastral')->nullable()->after('descricao_porte');
            $table->string('descricao_situacao_cadastral')->nullable()->after('situacao_cadastral');
            $table->unsignedTinyInteger('indicador_contribuinte_icms')->nullable()->after('descricao_situacao_cadastral');
            $table->string('bairro')->nullable()->after('city');
            $table->string('numero')->nullable()->after('bairro');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'nome_fantasia',
                'cnae',
                'cnae_descricao',
                'regime_tributario',
                'porte',
                'descricao_porte',
                'situacao_cadastral',
                'descricao_situacao_cadastral',
                'indicador_contribuinte_icms',
                'bairro',
                'numero',
            ]);
        });
    }
};
