<?php

namespace Perseu\Auditoria\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Perseu\Comercial\Models\Obra;
use Perseu\Comercial\Models\ReferenciaPreco;
use Perseu\Comercial\Models\SituacaoObra;
use Perseu\Comercial\Models\TipoObra;
use Perseu\Pessoas\Models\CategoriaPessoa;
use Perseu\Pessoas\Models\Contato;
use Perseu\Pessoas\Models\Endereco;
use Perseu\Pessoas\Models\PessoaFisica;
use Perseu\Pessoas\Models\PessoaJuridica;
use Perseu\Pessoas\Models\Setor;

/**
 * Ponto único de "tradução" de um `subject_type` (FQCN cru gravado pelo
 * Spatie Activitylog em `activity_log.subject_type` — não há
 * `Relation::morphMap()` configurado neste projeto, confirmado por grep,
 * então o valor salvo é sempre o nome completo da classe) para o que a
 * central de Auditoria (Configurações > Auditoria) exibe/filtra: rótulo
 * amigável, módulo/plugin de origem, e uma referência textual ao registro
 * específico (nome, razão social, número de Obra etc.).
 *
 * A lista de classes aqui precisa cobrir todo Model que usa
 * `Perseu\Auditoria\Traits\LogsBusinessActivity` (checar com
 * `grep -rl LogsBusinessActivity plugins/` ao adicionar um Model novo) —
 * um Model auditado que não apareça aqui ainda funciona (cai no fallback
 * de `label()`), só não ganha filtro dedicado nem referência amigável.
 */
class SubjectTypeCatalog
{
    public const MODULO_COMERCIAL = 'comercial';

    public const MODULO_PESSOAS = 'pessoas';

    /**
     * @return array<class-string<Model>, string>
     */
    protected static function labelSlugs(): array
    {
        return [
            Obra::class           => 'obra',
            TipoObra::class       => 'tipo-obra',
            SituacaoObra::class   => 'situacao-obra',
            ReferenciaPreco::class => 'referencia-preco',
            PessoaFisica::class   => 'pessoa-fisica',
            PessoaJuridica::class => 'pessoa-juridica',
            CategoriaPessoa::class => 'categoria-pessoa',
            Setor::class          => 'setor',
            Endereco::class       => 'endereco',
            Contato::class        => 'contato',
        ];
    }

    /**
     * @return array<class-string<Model>, self::MODULO_*>
     */
    protected static function modulos(): array
    {
        return [
            Obra::class            => self::MODULO_COMERCIAL,
            TipoObra::class        => self::MODULO_COMERCIAL,
            SituacaoObra::class    => self::MODULO_COMERCIAL,
            ReferenciaPreco::class => self::MODULO_COMERCIAL,
            PessoaFisica::class    => self::MODULO_PESSOAS,
            PessoaJuridica::class  => self::MODULO_PESSOAS,
            CategoriaPessoa::class => self::MODULO_PESSOAS,
            Setor::class           => self::MODULO_PESSOAS,
            Endereco::class        => self::MODULO_PESSOAS,
            Contato::class         => self::MODULO_PESSOAS,
        ];
    }

    public static function label(?string $subjectType): string
    {
        if (blank($subjectType)) {
            return '—';
        }

        $slug = static::labelSlugs()[$subjectType] ?? null;

        if ($slug) {
            return __("auditoria::filament/resources/auditoria.subject_types.{$slug}");
        }

        // Fallback pra um Model auditado no futuro que ainda não foi
        // mapeado aqui em cima — melhor que expor o FQCN cru.
        return Str::of($subjectType)->classBasename()->headline();
    }

    /**
     * @return array<class-string<Model>, string> FQCN => rótulo, para o SelectFilter de cadastro.
     */
    public static function subjectTypeOptions(): array
    {
        $options = [];

        foreach (array_keys(static::labelSlugs()) as $class) {
            $options[$class] = static::label($class);
        }

        asort($options);

        return $options;
    }

    /**
     * @return array<self::MODULO_*, string> chave do módulo => rótulo, para o SelectFilter de módulo.
     */
    public static function moduloOptions(): array
    {
        return [
            self::MODULO_COMERCIAL => __('auditoria::filament/resources/auditoria.modulos.comercial'),
            self::MODULO_PESSOAS   => __('auditoria::filament/resources/auditoria.modulos.pessoas'),
        ];
    }

    /**
     * @return list<class-string<Model>> classes do módulo informado, para o `whereIn('subject_type', ...)` do filtro.
     */
    public static function subjectTypesForModulo(string $modulo): array
    {
        return array_keys(array_filter(
            static::modulos(),
            fn (string $moduloDaClasse): bool => $moduloDaClasse === $modulo,
        ));
    }

    /**
     * Referência textual pro registro específico afetado pelo log —
     * "Obra", "Pessoa Jurídica" etc. já contam pela coluna de cadastro;
     * esta é a parte que muda de linha pra linha (nome, razão social,
     * número da Obra...). Retorna `null` quando o subject não está mais
     * disponível (excluído em definitivo) ou é de um tipo não mapeado.
     */
    public static function referenceFor(?Model $subject): ?string
    {
        if (! $subject) {
            return null;
        }

        return match ($subject::class) {
            Obra::class => trim(
                ($subject->numero_obra ? "{$subject->numero_obra} — " : '') . $subject->descricao
            ),
            TipoObra::class, SituacaoObra::class, ReferenciaPreco::class, CategoriaPessoa::class, Setor::class => $subject->descricao,
            PessoaFisica::class => $subject->nome,
            PessoaJuridica::class => $subject->razao_social,
            Endereco::class => trim(
                $subject->logradouro . ($subject->numero ? ", {$subject->numero}" : '')
            ) ?: null,
            Contato::class => $subject->pessoaFisica?->nome ?? $subject->cargo,
            default => null,
        };
    }

    /**
     * Aplica o termo digitado na caixa "Pesquisar" da central de
     * Auditoria (nome, razão social, número de Obra etc.) via
     * `whereHasMorph` — um único termo pesquisado na(s) coluna(s)
     * certa(s) de cada tipo de subject mapeado, sem precisar de uma
     * coluna própria na tabela `activity_log` (a referência é derivada
     * do model relacionado, não armazenada). Ligado à busca global do
     * Filament via `->searchable(query: ...)` na coluna
     * `subject_reference`
     * (`AuditoriaResource::getSubjectReferenceColumnComponent()`) — não
     * é mais um `Filter` separado (existiu como um até 2026-08-29,
     * removido por redundância com a caixa "Pesquisar").
     *
     * Todas as colunas comparadas aqui usam collation
     * `utf8mb4_unicode_ci` neste banco (confirmado com `SHOW FULL
     * COLUMNS`), então o `LIKE` já é case-insensitive sem precisar de
     * `LOWER()` — ver nota mais completa em
     * `getSubjectReferenceColumnComponent()`.
     */
    public static function applyBusca(Builder $query, string $termo): Builder
    {
        return $query->whereHasMorph(
            'subject',
            array_keys(static::labelSlugs()),
            function (Builder $subQuery, string $type) use ($termo) {
                match ($type) {
                    Obra::class => $subQuery->where(fn ($q) => $q
                        ->where('descricao', 'like', "%{$termo}%")
                        ->orWhere('numero_obra', 'like', "%{$termo}%")),
                    TipoObra::class, SituacaoObra::class, ReferenciaPreco::class, CategoriaPessoa::class, Setor::class => $subQuery
                        ->where('descricao', 'like', "%{$termo}%"),
                    PessoaFisica::class => $subQuery->where('nome', 'like', "%{$termo}%"),
                    PessoaJuridica::class => $subQuery->where(fn ($q) => $q
                        ->where('razao_social', 'like', "%{$termo}%")
                        ->orWhere('nome_fantasia', 'like', "%{$termo}%")
                        ->orWhere('cnpj', 'like', "%{$termo}%")),
                    Endereco::class => $subQuery->where(fn ($q) => $q
                        ->where('logradouro', 'like', "%{$termo}%")
                        ->orWhere('bairro', 'like', "%{$termo}%")
                        ->orWhere('municipio', 'like', "%{$termo}%")),
                    Contato::class => $subQuery->where('cargo', 'like', "%{$termo}%"),
                    default => $subQuery->whereRaw('1 = 0'),
                };
            },
        );
    }
}
