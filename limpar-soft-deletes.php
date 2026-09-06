<?php

/**
 * Script para forçar a exclusão definitiva (forceDelete) de todos os
 * registros soft-deleted (excluídos, mas ainda presentes no banco)
 * do plugin perseu/pessoas.
 *
 * Uso:
 *   ddev artisan tinker < limpar-soft-deletes.php
 *
 * Não altera nenhum model nem migration — só limpa o que já está
 * marcado como excluído, liberando CNPJ/CPF/etc. para recadastro.
 */

$modelClasses = [
    \Perseu\Pessoas\Models\PessoaJuridica::class,
    \Perseu\Pessoas\Models\PessoaFisica::class,
    \Perseu\Pessoas\Models\Categoria::class,
    \Perseu\Pessoas\Models\Setor::class,
    \Perseu\Pessoas\Models\Endereco::class,
    \Perseu\Pessoas\Models\Contato::class,
];

foreach ($modelClasses as $class) {
    if (! class_exists($class)) {
        echo "(pulando, classe não existe: {$class})\n";
        continue;
    }

    if (! in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($class))) {
        echo "(pulando, {$class} não usa SoftDeletes)\n";
        continue;
    }

    $trashed = $class::onlyTrashed()->get();
    $count = $trashed->count();

    if ($count === 0) {
        echo "{$class}: nenhum registro excluído pendente.\n";
        continue;
    }

    foreach ($trashed as $registro) {
        $registro->forceDelete();
    }

    echo "{$class}: {$count} registro(s) excluído(s) definitivamente.\n";
}

echo "\nConcluído.\n";
