Releia CLAUDE.md, GUIA-CRIACAO-PLUGIN.md e use os Resources do plugin
Pessoas (PessoaFisicaResource, PessoaJuridicaResource,
CategoriaPessoaResource) como referência de padrão e de
HasCompactFieldWidth.

## Objetivo: Cluster "Comercial" + 3 Resources

## 1. Cluster

Criar ComercialCluster (Filament\Clusters\Cluster), label "Comercial",
ícone heroicon-o-briefcase (confirmar existência), registrado via
discoverClusters no ComercialPlugin (mesmo mecanismo já confirmado
funcionando em PessoasPlugin — reveja como foi feito lá).

## 2. SituacaoProjetoResource (simples)

Mesmo padrão de CategoriaPessoaResource: form só com descricao
(TextInput, obrigatório, columnSpanFull), tabela com descricao +
created_at. Página única (ManageRecords), sem SoftDeletes.

## 3. TipoProjetoResource (simples)

Form: codigo (TextInput, obrigatório, maxLength 1, unique), descricao
(TextInput, obrigatório, columnSpanFull). Tabela: codigo, descricao.
Página única (ManageRecords).

## 4. ProjetoResource (o mais complexo)

Padrão de 3 páginas (List/Create/Edit), usando HasCompactFieldWidth.

### Formulário

**Bloco 1 — Identificação:**
- descricao: TextInput, obrigatório, label "Nome da Obra",
  columnSpanFull()
- tipo_projeto_id: Select, relationship, obrigatório, compacto
- situacoes: Select multiple, relationship, columnSpanFull (mesmo
  padrão de "categorias" em PessoaFisicaResource)

**Bloco 2 — Contratante (lógica condicional):**
- Um campo de escolha (Radio ou Select, NÃO persistido no banco —
  use ->dehydrated(false)) chamado algo como "tipo_contratante", com
  opções "Pessoa Física" / "Pessoa Jurídica", ->live(). No modo Edit,
  esse campo precisa refletir corretamente qual dos dois (
  pessoa_fisica_id ou pessoa_juridica_id) já está preenchido no
  registro (usar ->afterStateHydrated() para deduzir e setar o valor
  inicial correto).
- Se "Pessoa Física" selecionado: exibir Select pessoa_fisica_id,
  ->relationship('pessoaFisica', 'nome'), FILTRADO para mostrar
  apenas Pessoas Físicas vinculadas a uma CategoriaPessoa com
  e_cliente=true (investigue a forma correta de fazer esse filtro via
  relationship() com modifyQueryUsing, atravessando o relacionamento
  categorias() já existente no model PessoaFisica).
- Se "Pessoa Jurídica" selecionado: exibir Select pessoa_juridica_id,
  mesmo princípio de filtro por e_cliente=true via categorias() de
  PessoaJuridica. ALÉM disso, exibir um segundo Select
  "contato_pessoa_fisica_id", reativo (->live() no
  pessoa_juridica_id), mostrando apenas os Contatos (via model
  Perseu\Pessoas\Models\Contato) já vinculados àquela Pessoa Jurídica
  específica escolhida — atualizando as opções dinamicamente se o
  usuário trocar de PJ.
- Os dois selects (PF e PJ) devem ter ->visible() condicionado ao
  valor do campo "tipo_contratante", e quando um é escondido, seu
  valor correspondente deve ser limpo (evitar salvar os dois
  preenchidos ao mesmo tempo).

**Bloco 3 — Endereço:**
- endereco_id: Select, reativo às escolhas do Bloco 2 — as opções
  devem vir dos endereços já vinculados à Pessoa Física OU Jurídica
  selecionada como contratante (via relacionamento enderecos() já
  existente nos models do plugin Pessoas). Investigue se dá para
  também oferecer "criar novo endereço" inline a partir daqui
  (createOptionForm) — se for razoável de implementar sem
  complicar demais, inclua; senão, apenas o Select de endereços
  existentes já resolve por agora.

**Bloco 4 — Somente leitura (gerados automaticamente):**
- numero_projeto: TextInput ou Placeholder, disabled/dehydrated
  conforme apropriado, mostrando o valor já gerado (só aparece
  preenchido depois de salvar pela primeira vez — na tela de criação,
  mostrar uma mensagem tipo "Gerado automaticamente ao salvar")
- revisao: exibir formatado como "R01", "R04" etc. (o valor
  armazenado é inteiro) — investigue a forma idiomática do Filament
  de formatar exibição sem perder o valor real ao salvar
  (formatStateUsing/dehydrateStateUsing, ou componente Placeholder
  read-only se revisão não for editável nesta fase — use seu
  critério, mas documente a escolha)
- data_cadastro: Placeholder, mostrando a data/hora, não editável

### Tabela (listagem)

- numero_projeto (searchable, sortable)
- descricao (searchable)
- tipoProjeto.descricao
- Uma coluna computada "Contratante" mostrando o nome de
  pessoaFisica OU pessoaJuridica (o que estiver preenchido)
- situacoes (badges, múltiplos)
- data_cadastro (sortable, toggleable oculto por padrão)

## Traduções e Policies

Seguir a mesma convenção já estabelecida (namespace, __()) e CRIAR AS
POLICIES para os 3 Resources deste plugin desde já (não esquecer,
como aconteceu no plugin Pessoas) — SituacaoProjetoPolicy,
TipoProjetoPolicy, ProjetoPolicy, registradas no
ComercialServiceProvider, e o config/filament-shield.php do plugin
(incluindo exclusão do ComercialCluster das páginas, mesmo ajuste já
aplicado em PessoasCluster).

## Validação

1. ddev artisan optimize:clear e ddev artisan filament:assets
2. Confirme via route:list as rotas do cluster e dos 3 Resources
3. Teste via tinker/Livewire::test: criar um Cliente PF e um Cliente
   PJ (categoria com e_cliente=true), um Contato para a PJ, e um
   Endereço para cada; depois criar um Projeto escolhendo cada tipo
   de contratante, confirmando que os campos condicionais aparecem/
   somem corretamente e que o número do projeto é gerado
4. Confirme que Policies bloqueiam corretamente um usuário sem
   permissão (mesmo teste que já fizemos no plugin Pessoas)

Me relate o resultado, incluindo qualquer decisão de implementação
que precisou de critério próprio (ex: como formatou a revisão, como
tratou o createOptionForm de endereço).
