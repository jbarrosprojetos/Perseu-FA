Releia CLAUDE.md antes de começar (especialmente a nota sobre filtro
de Tipo de Endereço por contexto PF/PJ, e a seção sobre
HasRelationManagerDividers). Use ContatosRelationManager.php como
referência de estrutura.

## Objetivo: Relation Manager de Endereços, para Pessoa Física E
Pessoa Jurídica

Como a tabela enderecos é compartilhada (mesma tabela física, ligada
via pivots pessoa_fisica_endereco e pessoa_juridica_endereco), crie
DOIS Relation Managers (um para cada contexto, já que os campos de
formulário e o filtro de tipo diferem):

## 1. EnderecosRelationManager para PessoaFisicaResource

Em .../PessoaFisicaResource/RelationManagers/EnderecosRelationManager.php,
usando o relacionamento enderecos() já existente no model PessoaFisica.

Formulário:
- cep: TextInput, mask '99999-999', ->live(onBlur: true) com
  ->afterStateUpdated() consultando a API ViaCEP (https://viacep.com.br/ws/{cep}/json/)
  e preenchendo automaticamente logradouro, bairro, municipio, uf (mesmo
  princípio já usado no Perseu-FA original, mas adaptado a este plugin)
- logradouro, numero, complemento, bairro, municipio, uf: TextInput
  (uf com maxLength 2)
- tipo: Select, options FILTRADAS do enum TipoEndereco, mostrando
  apenas: Residencial, Cobranca, Entrega, Outro (conforme documentado
  no CLAUDE.md para contexto de Pessoa Física)
- principal: Toggle, label "Endereço principal?"

Tabela: logradouro, numero, bairro, municipio, uf, tipo (badge),
principal (ícone boolean)

## 2. EnderecosRelationManager para PessoaJuridicaResource

Mesma estrutura, mas o Select de tipo mostra: Comercial, Cobranca,
Entrega, Obra, Outro (conforme documentado para contexto de Pessoa
Jurídica).

## 3. Registrar e remover os placeholders

- Adicionar EnderecosRelationManager::class ao getRelations() de
  ambos os Resources (PessoaFisicaResource já não tinha
  getRelations() ainda; PessoaJuridicaResource já tem, adicionar mais
  um item ao array)
- Remover a Section placeholder "Endereços" do form() de ambos os
  Resources (e as traduções órfãs correspondentes)

## 4. Aplicar HasRelationManagerDividers em EditPessoaFisica também

Já que Pessoa Física vai ganhar seu primeiro Relation Manager agora,
aplique o trait HasRelationManagerDividers (já usado em
EditPessoaJuridica) também em EditPessoaFisica, para manter a mesma
separação visual (espaçamento antes do footer, divisor duplo + card
cinza antes da área de Relation Managers).

## 5. Considere a ORDEM dos Relation Managers em Pessoa Jurídica

Como agora existem dois (Endereços e Contatos), decida uma ordem que
faça sentido (sugestão: Endereços primeiro, Contatos depois — mas use
seu critério) no array de getRelations().

## Validação

1. ddev artisan optimize:clear e ddev artisan filament:assets
2. Teste criar um Endereço vinculado a uma Pessoa Física via
   Livewire::test, confirmando que só as 4 opções de tipo aparecem
   (não Comercial nem Obra)
3. Teste o mesmo para Pessoa Jurídica, confirmando as 5 opções
   corretas (incluindo Comercial e Obra, sem Residencial)
4. Confirme a persistência na tabela pivot correta
   (pessoa_fisica_endereco ou pessoa_juridica_endereco) com o campo
   tipo e principal corretos
5. Confirme visualmente (via HTML) que ambas as páginas de Edit têm a
   separação visual (gap + divisor + card) aplicada corretamente

Me relate o resultado.
