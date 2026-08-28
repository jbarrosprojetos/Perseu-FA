<?php

namespace Perseu\Pessoas\Traits;

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
    protected static function bootCascadesRelatedDataOnForceDelete(): void
    {
        static::forceDeleting(function (self $model): void {
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
    }
}
