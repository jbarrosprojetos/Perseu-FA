<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Tipo de Endereço" deixa de ser um valor único por relação
 * Pessoa-Endereço (coluna `tipo` nos pivots `pessoa_fisica_endereco`/
 * `pessoa_juridica_endereco`) e passa a ser uma TAG — um mesmo
 * `Endereco` pode ter várias finalidades ao mesmo tempo (ex:
 * Comercial + Obra), ver CLAUDE.md de `perseu/pessoas`, "Tipo de
 * Endereço como tag".
 *
 * Modelado como N:N entre `enderecos` e o enum `TipoEndereco`
 * (`endereco_tipo`, não entre a relação Pessoa-Endereço e o tipo) —
 * confirmado por query antes de decidir: nenhum `Endereco` hoje é
 * compartilhado entre duas Pessoas (cada linha de `enderecos`
 * pertence a exatamente um pivot PF ou PJ), então a tag pertence
 * naturalmente ao Endereço em si, não à relação.
 *
 * Migration de dados: cada linha existente em
 * `pessoa_fisica_endereco`/`pessoa_juridica_endereco` vira UMA linha
 * em `endereco_tipo` com o mesmo valor de `tipo` que já tinha — sem
 * marcar tags extras (isso só vale para endereços NOVOS, criados a
 * partir de agora, no formulário). `principal` continua como estava,
 * na própria tabela pivot Pessoa-Endereço (não é uma tag, é uma
 * característica da relação).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('endereco_tipo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('endereco_id')->constrained('enderecos')->cascadeOnDelete();
            $table->unsignedTinyInteger('tipo');
            $table->timestamps();
            $table->unique(['endereco_id', 'tipo']);
        });

        $agora = now();

        foreach (['pessoa_fisica_endereco', 'pessoa_juridica_endereco'] as $pivotTable) {
            $linhas = DB::table($pivotTable)->select('endereco_id', 'tipo')->get();

            foreach ($linhas as $linha) {
                DB::table('endereco_tipo')->insertOrIgnore([
                    'endereco_id' => $linha->endereco_id,
                    'tipo'        => $linha->tipo,
                    'created_at'  => $agora,
                    'updated_at'  => $agora,
                ]);
            }
        }

        Schema::table('pessoa_fisica_endereco', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });

        Schema::table('pessoa_juridica_endereco', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('pessoa_fisica_endereco', function (Blueprint $table) {
            $table->unsignedTinyInteger('tipo')->nullable()->after('endereco_id');
        });

        Schema::table('pessoa_juridica_endereco', function (Blueprint $table) {
            $table->unsignedTinyInteger('tipo')->nullable()->after('endereco_id');
        });

        // Reversão best-effort: se um endereço ganhou mais de uma tag
        // depois da migração, só a de menor valor volta pro pivot (a
        // estrutura antiga só suporta um valor). Cenário esperado só em
        // rollback logo após o `up()`, sem alterações no meio.
        foreach (['pessoa_fisica_endereco', 'pessoa_juridica_endereco'] as $pivotTable) {
            $linhas = DB::table($pivotTable)->select('id', 'endereco_id')->get();

            foreach ($linhas as $linha) {
                $tipo = DB::table('endereco_tipo')
                    ->where('endereco_id', $linha->endereco_id)
                    ->orderBy('tipo')
                    ->value('tipo');

                DB::table($pivotTable)
                    ->where('id', $linha->id)
                    ->update(['tipo' => $tipo ?? 6]); // 6 = TipoEndereco::Outro, fallback
            }
        }

        Schema::table('pessoa_fisica_endereco', function (Blueprint $table) {
            $table->unsignedTinyInteger('tipo')->nullable(false)->change();
        });

        Schema::table('pessoa_juridica_endereco', function (Blueprint $table) {
            $table->unsignedTinyInteger('tipo')->nullable(false)->change();
        });

        Schema::dropIfExists('endereco_tipo');
    }
};
