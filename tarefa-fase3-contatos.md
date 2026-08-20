Releia CLAUDE.md antes de começar. Objetivo: criar o Relation Manager
de Contatos dentro de PessoaJuridicaResource, substituindo a Section
placeholder "Contatos" que hoje mostra "Disponível após salvar o
cadastro".

## Estrutura

Em plugins/perseu/pessoas/src/Filament/Clusters/Pessoas/Resources/
PessoaJuridicaResource/RelationManagers/, criar ContatosRelationManager,
associado ao relacionamento contatos() já existente no model
PessoaJuridica (Fase 1).

## Formulário de criação (dropdown + tag)

- pessoa_fisica_id: Select, searchable(), preload(), buscando entre
  os registros existentes de PessoaFisica (relationship ou options
  manual via query), mostrando o campo "nome" como label de cada
  opção. NÃO permitir criar uma Pessoa Física nova a partir daqui
  (createOptionForm não deve existir) — a pessoa física precisa já
  estar cadastrada previamente em Pessoas Físicas.
- cargo: TextInput, label "Cargo/Função" (ex: "Sócio", "Comprador",
  "Financeiro"), opcional

## Tabela (lista abaixo do formulário)

Colunas, puxando da Pessoa Física relacionada:
- Nome (via pessoaFisica.nome, relationship column)
- E-mail (via pessoaFisica.email)
- Telefone (via pessoaFisica.telefone)
- Cargo (campo próprio do Contato)

Ações: Editar (só o campo cargo, já que nome/email/telefone vêm de
outro cadastro) e Excluir (remove só o vínculo de Contato, não afeta
o cadastro da Pessoa Física em si).

## Registrar no Resource

Adicionar o método getRelations() em PessoaJuridicaResource
retornando [ContatosRelationManager::class], e trocar a Section
placeholder "Contatos" — como Relation Managers do Filament aparecem
como abas/seções próprias na página de Edit (não dentro do form()
principal), remova a Section placeholder do form() e confirme que o
Relation Manager aparece automaticamente na página de Edit.

## Traduções

Labels via __() seguindo a convenção já usada, criando as chaves
necessárias em lang/en e lang/pt_BR.

## Validação

1. Rode ddev artisan optimize:clear e ddev artisan filament:assets
2. Via tinker, crie uma Pessoa Física de teste, depois crie uma Pessoa
   Jurídica, e teste vincular a Pessoa Física como Contato dela
   (criando um registro na tabela contatos) via Livewire::test do
   RelationManager
3. Confirme que a tabela do Relation Manager mostra nome/email/
   telefone corretos da Pessoa Física vinculada
4. Confirme que excluir o Contato não apaga o registro de Pessoa
   Física (só a linha da tabela contatos)

Me relate o resultado.
