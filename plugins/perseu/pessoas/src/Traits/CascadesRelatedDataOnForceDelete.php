<?php

namespace Perseu\Pessoas\Traits;

use Illuminate\Support\Facades\DB;
use Perseu\Pessoas\Models\Endereco;

/**
 * Endereço/Contato não usam SoftDeletes (ver CLAUDE.md) — sem isso,
 * apagar definitivamente (forceDelete) uma Pessoa Física/Jurídica deixava
 * os dois "vivos" no banco, órfãos, apontando pra um registro que não
 * existe mais nem na lixeira.
 *
 * Usa `forceDeleting` (NÃO `deleting`) de propósito — essa é a correção
 * em relação à primeira versão deste fix (só em PessoaJuridica, ver
 * histórico em CLAUDE.md): rodar isso em `deleting` também dispararia
 * num soft-delete comum, apagando Endereço/Contato de forma definitiva
 * mesmo quando o registro pai só foi pra lixeira — o que quebraria a
 * Lixeira (`Restaurar` traria a Pessoa de volta, mas sem Endereços/
 * Contatos, que já teriam sido apagados de vez). `forceDeleting` só
 * dispara em exclusão definitiva de fato, preservando Endereço/Contato
 * enquanto o registro pai só estiver soft-deleted (podendo ser
 * restaurado normalmente, com tudo intacto).
 *
 * Laravel chama automaticamente `boot{NomeDoTrait}()` de cada trait
 * usado por um Model (`Model::bootTraits()`) — não precisa (nem deve)
 * ser chamado manualmente de dentro de um `boot()` próprio do Model.
 *
 * Requer que a classe que usa este trait tenha os métodos
 * `enderecos()` (BelongsToMany de Endereco) e `contatos()` (HasMany de
 * Contato) — mesmo formato em PessoaFisica e PessoaJuridica.
 */
trait CascadesRelatedDataOnForceDelete
{
    /**
     * `DB::transaction()` — achado real de concorrência (ver
     * `INVESTIGACAO-TRANSACOES-CONCORRENCIA.md`, risco "Cascata de
     * exclusão de Pessoa sem transação"): sem isso, uma interrupção
     * (timeout, worker reciclado, exception) entre `contatos()->delete()`
     * e `enderecos()->detach()` deixava os Contatos já apagados mas o
     * vínculo Pessoa↔Endereço intacto; uma interrupção dentro do `foreach`
     * deixava parte dos Endereços excluídos e parte não — nos dois casos,
     * dado órfão numa operação que já é definitiva (`forceDelete`, sem
     * lixeira pra recuperar). Envolvendo tudo numa transação, qualquer
     * falha no meio desfaz a cascata inteira (tudo ou nada).
     *
     * **Nota de escopo**: esta transação cobre só a CASCATA (Contatos +
     * Endereços), não a exclusão da própria Pessoa em si — essa roda
     * DEPOIS, fora daqui, como parte do fluxo nativo do Eloquent
     * (`Model::forceDelete()` só chama `$this->delete()` depois que este
     * listener de `forceDeleting` retorna). Na prática isso já cobre o
     * cenário relatado (interrupção NO MEIO da cascata): se a cascata
     * falhar, a exception propaga e `forceDelete()` nunca chega a excluir
     * a Pessoa. O único caso residual não coberto por esta transação — a
     * cascata terminar com sucesso e a exclusão da PRÓPRIA Pessoa falhar
     * logo em seguida — exigiria embrulhar a chamada de `forceDelete()`
     * inteira no CHAMADOR (ex.: `Lixeira::forceDeleteRecord()`, já feito
     * nesta mesma tarefa — nesse caminho específico as duas transações
     * aninhadas, via savepoint do Laravel, já cobrem os dois casos
     * juntos); outros chamadores (`ForceDeleteAction` direto num
     * Resource) continuam com esse risco residual, bem mais estreito que
     * o problema original.
     */
    protected static function bootCascadesRelatedDataOnForceDelete(): void
    {
        static::forceDeleting(function (self $model): void {
            DB::transaction(function () use ($model): void {
                $model->contatos()->delete();

                $enderecoIds = $model->enderecos()->pluck('enderecos.id');

                $model->enderecos()->detach();

                foreach ($enderecoIds as $enderecoId) {
                    $endereco = Endereco::find($enderecoId);

                    if ($endereco && ! $endereco->pessoasFisicas()->exists() && ! $endereco->pessoasJuridicas()->exists()) {
                        $endereco->delete();
                    }
                }
            });
        });
    }
}
