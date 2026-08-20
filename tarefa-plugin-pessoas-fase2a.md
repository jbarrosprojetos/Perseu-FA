Releia CLAUDE.md e GUIA-CRIACAO-PLUGIN.md antes de começar. O plugin
Pessoas (plugins/perseu/pessoas) já tem a Fase 1 completa (migrations,
models, enums). Use como modelo de referência a estrutura de Cluster já
existente no sistema (Settings, em plugins/webkul/security ou
plugins/webkul/support) para o padrão de Filament\Clusters\Cluster.

## Objetivo desta etapa: Cluster "Pessoas" + Resource de Categoria

## 1. Criar o Cluster

Em plugins/perseu/pessoas/src/Filament/Clusters/, criar a classe
PessoasCluster estendendo Filament\Clusters\Cluster, com
getNavigationLabel() retornando __('pessoas::clusters/pessoas.navigation.label')
= "Pessoas" (criar a chave em lang/en e lang/pt_BR). Escolha um ícone
heroicon apropriado (ex: heroicon-o-users), confirmando que existe no
pacote instalado.

Registrar o Cluster no PessoasServiceProvider (packageRegistered(),
junto com o restante do que já foi registrado na Fase 1) para que ele
apareça no menu principal do painel admin.

## 2. Criar o Resource de Categoria

Em plugins/perseu/pessoas/src/Filament/Clusters/Pessoas/Resources/,
criar CategoriaPessoaResource, associado ao model
Perseu\Pessoas\Models\CategoriaPessoa, dentro do Cluster criado acima
($cluster = PessoasCluster::class).

Formulário (Schema, seguindo o padrão Filament 5/Schemas já usado no
projeto, não Forms\Components\Form antigo):
- descricao: TextInput, obrigatório, maxLength 255, columnSpanFull()
- aplica_pf: Toggle, label "Aplica-se a Pessoa Física"
- aplica_pj: Toggle, label "Aplica-se a Pessoa Jurídica"

Tabela (listagem):
- descricao (searchable, sortable)
- aplica_pf (IconColumn boolean)
- aplica_pj (IconColumn boolean)
- created_at (sortable, toggleable oculto por padrão)

getModelLabel() / getPluralModelLabel(): "Categoria" / "Categorias"
getNavigationLabel(): "Categorias"

Todos os labels via __() com chaves em lang/en e lang/pt_BR do plugin
pessoas, seguindo a convenção de namespace já usada (ex:
pessoas::filament/resources/categoria-pessoa.*).

Título da coluna do menu, breadcrumb, tudo em português no pt_BR,
seguindo os mesmos cuidados já aplicados no resto do sistema (lembre-se
do aprendizado registrado no CLAUDE.md sobre Resources duplicados —
confirme que não há conflito com nenhum Resource existente).

Não esqueça o $fillable já existe no Model da Fase 1, então não deve
gerar erro de MassAssignmentException.

## 3. Validação

1. Rode ddev artisan optimize:clear e ddev artisan filament:assets
2. Confirme que "Pessoas" aparece como item no menu principal
3. Confirme que "Categorias" aparece dentro do Cluster
4. Teste criar um registro (via tinker ou relatando que testou pela
   tela) para confirmar que não há erro de fillable/validação

Me relate os arquivos criados e o resultado dos testes.
