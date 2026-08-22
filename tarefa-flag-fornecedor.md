Releia CLAUDE.md antes de começar. Mesmo padrão da tarefa anterior
(e_cliente), adicionar agora o campo e_fornecedor à tabela
categorias_pessoa (nome correto confirmado na tarefa anterior) e ao
Model CategoriaPessoa.

1. Nova migration (não alterar as existentes) adicionando a coluna
   e_fornecedor (boolean, default false), registrada em
   PessoasServiceProvider::hasMigrations() (mesmo processo já
   confirmado necessário na tarefa anterior).
2. Adicionar e_fornecedor ao $fillable e $casts do Model
   CategoriaPessoa.
3. No CategoriaPessoaResource, adicionar Toggle "e_fornecedor", label
   "É categoria de Fornecedor?", ao lado dos toggles já existentes
   (aplica_pf, aplica_pj, e_cliente).
4. Adicionar coluna IconColumn (boolean) na listagem, mesma posição
   relativa.
5. Traduções via __() nos dois idiomas.

Ao final, rode ddev artisan migrate, ddev artisan optimize:clear e
ddev artisan filament:assets.

Depois, atualize CLAUDE.md acrescentando uma nota de decisão de
arquitetura (nova seção "## Flags de sistema em Categoria de Pessoa —
decisão consciente de escopo limitado"), registrando: apenas "Cliente"
(e_cliente) e "Fornecedor" (e_fornecedor) recebem flags booleanas
fixas e dedicadas na tabela de Categoria, porque são os dois papéis
que módulos do sistema (ex: Comercial, futuramente Compras) precisam
filtrar de forma confiável e estável. Qualquer outro papel/classificação
adicional deve ser resolvido através de Categorias comuns (tags livres,
sem flag de sistema dedicada) — não adicionar novos campos booleanos
e_algumaCoisa no futuro sem antes reavaliar essa decisão com o cliente.
Anote isso como um ponto de atenção para consultas futuras/manual do
sistema.
