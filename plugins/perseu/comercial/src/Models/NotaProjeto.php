<?php

namespace Perseu\Comercial\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Perseu\Auditoria\Traits\LogsBusinessActivity;
use Webkul\Security\Models\User;

/**
 * Histórico de notas/anotações de um Projeto (ver CLAUDE.md, "Notas do
 * Projeto"). SEM `SoftDeletes`, mesma divergência já usada por
 * `ItemProjeto` na convenção padrão de Model de negócio — aqui não é
 * por causa de renumeração (que não existe, ver `numero_nota` abaixo),
 * é porque uma nota de USUÁRIO excluída dentro do prazo de 24h não
 * precisa de Lixeira própria, e uma nota de SISTEMA nunca é excluída
 * pela UI (`tipo_sistema` já cobre essa garantia sozinho, exceto pro
 * super usuário — ver `podeSerEditadaPor()`/`podeSerExcluidaPor()`).
 */
class NotaProjeto extends Model
{
    use LogsBusinessActivity;

    protected $table = 'notas_projeto';

    // numero_nota nunca é preenchido pelo usuário — gerado
    // automaticamente no evento "creating" abaixo (mesmo critério de
    // ItemProjeto::numero_item), por isso fora do $fillable.
    protected $fillable = [
        'projeto_id',
        'usuario_id',
        'texto',
        'tipo_sistema',
        'item_projeto_id',
    ];

    protected $casts = [
        'tipo_sistema' => 'boolean',
    ];

    public function projeto(): BelongsTo
    {
        return $this->belongsTo(Projeto::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * `item_projeto_id` — vínculo opcional com um Item específico do
     * Projeto (base do futuro ícone "Cálculos" no menu de cada item,
     * ver CLAUDE.md). `null` pra nota de usuário e pra nota de sistema
     * sobre o Projeto como um todo; preenchido só numa nota de sistema
     * sobre um item específico (geração automática ainda não
     * implementada — tarefa futura, junto da criação de Itens via
     * Promob).
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(ItemProjeto::class, 'item_projeto_id');
    }

    /**
     * `numero_nota` = maior número já usado NAQUELE Projeto + 1,
     * começando em 1. Diferente de `ItemProjeto::numero_item`, este
     * número NUNCA é renumerado ao excluir uma nota (ver
     * `ProjetoResource::excluirNotaProjeto()`) — é só um identificador
     * sequencial de cada nota ao longo do tempo (não uma lista visível
     * ao cliente), então pode ficar com buraco na sequência depois de
     * uma exclusão, de propósito, pra preservar a identidade de cada
     * nota já existente.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (NotaProjeto $nota): void {
            if (blank($nota->numero_nota)) {
                $ultimoNumero = (int) static::where('projeto_id', $nota->projeto_id)->max('numero_nota');

                $nota->numero_nota = $ultimoNumero + 1;
            }
        });
    }

    /**
     * "Super usuário" = usuário com a Role `Admin` (guard `web`) — a
     * role de privilégio total já usada de fato neste sistema (556
     * permissões atribuídas hoje, sincronizadas manualmente conforme
     * "Convenção para Model novo de cadastro de negócio" no CLAUDE.md
     * da raiz). **Achado da investigação desta tarefa, registrado aqui
     * porque o enunciado pediu explicitamente pra confirmar o nome**:
     * NÃO é a Role `Sistema` (guard `sanctum`) — essa é usada só pra
     * liberar a Debugbar (ver CLAUDE.md da raiz, "Debugbar via Role com
     * Guard Sanctum"), um propósito completamente diferente, sem
     * relação com privilégio administrativo geral. Também não é
     * `config('filament-shield.super_admin.name')` — esse mecanismo do
     * Shield está `'enabled' => false` em `config/filament-shield.php`
     * e a role que ele nomeia (`'super_admin'`) nem existe na tabela
     * `roles` deste banco (confirmado por query direta); os dois
     * `Gate::before()` do projeto que checam esse nome
     * (`bypass_company_scope`/`bypass_ownership_scope`, em
     * `webkul/support`/`webkul/security`) estão efetivamente dormentes
     * hoje por causa disso. `Admin` (guard `web`) é a role que de fato
     * concentra o privilégio total na prática atual do sistema.
     */
    public function ehSuperUsuario(User $usuario): bool
    {
        return $usuario->hasRole('Admin', 'web');
    }

    /**
     * Regra de permissão (ver CLAUDE.md, "Notas do Projeto — regra de
     * 24h e super usuário"):
     *
     * - Super usuário: pode editar QUALQUER nota, de qualquer usuário,
     *   mesmo fora do prazo e mesmo de sistema — usado pra testes e
     *   manutenção.
     * - Nota de SISTEMA: usuário comum NUNCA pode editar, independente
     *   do prazo.
     * - Nota de USUÁRIO: só o próprio autor (`usuario_id === $usuario->id`)
     *   pode editar, e só dentro de 24h a partir de `created_at`.
     *
     * Usado tanto pra exibição condicional dos ícones de editar/excluir
     * (`ProjetoResource::linhaExibicaoNota()`) quanto como validação de
     * segurança no backend (`ProjetoResource::salvarEdicaoNota()`/
     * `excluirNotaProjeto()`) — nunca confiar só em esconder o botão na
     * tela.
     */
    public function podeSerEditadaPor(User $usuario): bool
    {
        if ($this->ehSuperUsuario($usuario)) {
            return true;
        }

        if ($this->tipo_sistema) {
            return false;
        }

        return $this->usuario_id === $usuario->id && $this->dentroDoPrazoDeEdicao();
    }

    /**
     * Hoje idêntica a `podeSerEditadaPor()` — mantida como método
     * separado porque editar e excluir podem divergir no futuro (ex.:
     * um super usuário com permissão de editar mas não de excluir),
     * mesmo sem essa necessidade concreta ainda.
     */
    public function podeSerExcluidaPor(User $usuario): bool
    {
        return $this->podeSerEditadaPor($usuario);
    }

    /**
     * Janela de 24h a partir de `created_at` — usada por
     * `podeSerEditadaPor()`/`podeSerExcluidaPor()` (só pra nota de
     * USUÁRIO, o super usuário ignora completamente este prazo) e
     * também por `ProjetoResource::salvarEdicaoNota()` pra decidir se
     * uma edição do próprio autor deve "reiniciar" a janela (atualizar
     * `created_at` pro momento da edição — ver CLAUDE.md).
     */
    public function dentroDoPrazoDeEdicao(): bool
    {
        if ($this->tipo_sistema) {
            return false;
        }

        return $this->created_at !== null && $this->created_at->addHours(24)->isFuture();
    }
}
