<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de Documentos (2026-09-11, ver CLAUDE.md "Documentos —
 * templates de Excel com marcadores de texto") — cada registro é um
 * arquivo-modelo (.xls/.xlsx/.xlsm) enviado pelo usuário operacional,
 * com marcadores de texto (%Campo%) que o Perseu vai preencher na
 * hora de gerar o documento final de um Projeto (Vistoria, Proposta,
 * O.S., Lista de Compras, Plano de Corte etc.) — mecanismo de
 * substituição de texto ainda a ser implementado numa tarefa futura,
 * esta migration só registra o cadastro do arquivo em si.
 *
 * `arquivo` guarda o CAMINHO relativo no disco (gerenciado pelo
 * `Filament\Forms\Components\FileUpload`, disco `local` — privado,
 * NUNCA público), não o conteúdo do arquivo — decisão explícita
 * confirmada com o usuário: arquivo binário nunca vira BLOB no banco
 * (infla o dump do `mysqldump`/backup, sem ganho nenhum de
 * performance ou compressão comparado a guardar em disco).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->string('descricao');
            $table->string('arquivo')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};
