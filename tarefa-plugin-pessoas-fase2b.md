Releia CLAUDE.md antes de começar. O Cluster "Pessoas" e o Resource de
Categoria já existem (Fase 2a). Use CategoriaPessoaResource como
referência de padrão/estrutura para este novo Resource.

## Objetivo: Resource de Pessoa Física

Em plugins/perseu/pessoas/src/Filament/Clusters/Pessoas/Resources/,
criar PessoaFisicaResource, associado ao model
Perseu\Pessoas\Models\PessoaFisica, dentro do mesmo Cluster
($cluster = PessoasCluster::class).

## Formulário (Schema, padrão Filament 5)

Campos, na ordem:
- nome: TextInput, obrigatório (required), maxLength 255, columnSpanFull()
- telefone: TextInput, obrigatório, mask '(99) 99999-9999'
- telefone_whatsapp: Toggle, label "É WhatsApp?"
- email: TextInput, tipo email(), maxLength 255
- cpf: TextInput, mask '999.999.999-99', unique(ignoreRecord: true)
- rg: TextInput
- data_nascimento: DatePicker
- estado_civil: Select, options a partir do enum
  Perseu\Pessoas\Enums\EstadoCivil (usar EstadoCivil::class em
  ->options() approriadamente, já que o enum implementa HasLabel)
- sexo: Select, mesmo padrão, a partir do enum Sexo
- profissao: TextInput
- observacoes: Textarea, columnSpanFull()

## Seção de Endereço dentro do MESMO formulário (não Relation Manager
ainda — isso fica pra Fase 3). Por enquanto, no cabeçalho, adicione um
placeholder ou Section vazia com título "Endereços" e texto
"Disponível após salvar o cadastro" (já que Relation Managers só
funcionam em registros já existentes) — NÃO implemente a busca de CEP
nem os campos de endereço nesta Fase 2b, isso é exclusivamente da Fase
3 (Relation Manager de Endereços).

## Tabela (listagem)

- nome (searchable, sortable)
- telefone (searchable)
- email (searchable, toggleable oculto por padrão)
- cpf (searchable)
- estado_civil (badge, a partir do enum)
- created_at (sortable, toggleable oculto por padrão)

## Labels e traduções

getModelLabel() / getPluralModelLabel(): "Pessoa Física" / "Pessoas Físicas"
getNavigationLabel(): "Pessoas Físicas"

Tudo via __() com chaves em lang/en e lang/pt_BR do plugin pessoas,
seguindo exatamente a mesma convenção de namespace já usada e
corrigida na Fase 2a (atenção ao caminho certo das chaves, conforme o
ajuste que você já fez lá).

## Validação

1. Rode ddev artisan optimize:clear e ddev artisan filament:assets
2. Confirme via route:list que a rota admin/pessoas/pessoas-fisicas
   existe
3. Confirme que "Pessoas Físicas" aparece como item dentro do Cluster
   "Pessoas", ao lado de "Categorias"
4. Teste a criação de um registro via model diretamente
   (PessoaFisica::create(...) via tinker) para confirmar que não há
   erro de fillable/validação
5. Confirme que os Selects de estado_civil e sexo mostram os labels
   traduzidos corretamente (não o nome cru do enum)

Me relate os arquivos criados e o resultado dos testes.
