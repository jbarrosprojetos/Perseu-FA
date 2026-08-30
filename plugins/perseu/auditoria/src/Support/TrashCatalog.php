<?php

namespace Perseu\Auditoria\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Perseu\Comercial\Models\Obra;
use Perseu\Comercial\Models\ReferenciaPreco;
use Perseu\Pessoas\Models\PessoaFisica;
use Perseu\Pessoas\Models\PessoaJuridica;

/**
 * Lista os Models que hoje têm Lixeira DE VERDADE (SoftDeletes +
 * `TrashedFilter`/`RestoreAction`/`ForceDeleteAction` no próprio
 * Resource — ver "Auditoria (log de atividade) + Lixeira completa" no
 * CLAUDE.md). Confirmado por grep (`grep -rl "use SoftDeletes"
 * plugins/perseu`) antes de escrever esta lista, não assumido: estes
 * 4 (`ReferenciaPreco` adicionado na tarefa do Cluster Referências,
 * 2026-08-30, já nascendo com `SoftDeletes` desde a criação, seguindo
 * a convenção) — Categoria de Pessoa, Setor, Tipo/Situação de Obra
 * NÃO usam `SoftDeletes` hoje (limitação já documentada, ver
 * "Limitação conhecida" no CLAUDE.md).
 *
 * Deliberadamente um subconjunto (não uma cópia) de
 * `SubjectTypeCatalog` — aquela classe cobre os 9 Models AUDITADOS
 * (a maioria sem Lixeira de UI); esta cobre só quem tem pra onde
 * restaurar/excluir definitivamente. `Perseu\Auditoria\Filament\Pages\Lixeira`
 * usa `SubjectTypeCatalog::label()`/`referenceFor()` (reaproveitados,
 * não duplicados) pra exibir cada linha, e esta classe só pra saber
 * QUAIS Models entram na lista e QUAL query trazer.
 *
 * Ao dar `SoftDeletes` + Lixeira de UI a um Model novo no futuro
 * (ver "Convenção para todo Model de cadastro de negócio criado a
 * partir de agora" no CLAUDE.md), adicionar aqui também.
 */
class TrashCatalog
{
    /**
     * @return list<class-string<Model>>
     */
    public static function models(): array
    {
        return [
            Obra::class,
            ReferenciaPreco::class,
            PessoaJuridica::class,
            PessoaFisica::class,
        ];
    }

    /**
     * @param  class-string<Model>  $model
     */
    public static function onlyTrashedQuery(string $model): Builder
    {
        return $model::onlyTrashed();
    }
}
