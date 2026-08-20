Releia CLAUDE.md antes de começar (especialmente as seções sobre
HasCompactFieldWidth, Grid vs Flex, e a regra de largura de campos).
Use PessoaFisicaResource.php como modelo de referência direto — a
estrutura deve ser bem parecida, incluindo o uso do trait
HasCompactFieldWidth e do padrão de 3 páginas (List/Create/Edit).

## Objetivo: Resource de Pessoa Jurídica

Em plugins/perseu/pessoas/src/Filament/Clusters/Pessoas/Resources/,
criar PessoaJuridicaResource, associado ao model
Perseu\Pessoas\Models\PessoaJuridica, dentro do Cluster
($cluster = PessoasCluster::class). Slug explícito: pessoas-juridicas.

## Formulário (Schema), seguindo o mesmo padrão de layout de
PessoaFisicaResource (usar HasCompactFieldWidth):

- razao_social: TextInput, maxLength 255, columnSpanFull()
- nome_fantasia: TextInput, obrigatório (required), maxLength 255,
  columnSpanFull()
- Linha (flexRow): cnpj (compacto, mask '99.999.999/9999-99',
  ->rule(new CnpjValido()), unique(ignoreRecord: true)) + telefone
  (compacto, obrigatório, mask '(99) 99999-9999') + email (grow,
  tipo email())
- Linha (flexRow): inscricao_estadual (compacto) + cnae (compacto,
  mask '9999-9/99') + regime_tributario (Select compacto, options a
  partir do enum RegimeTributario)
- Linha (flexRow): data_abertura (DatePicker compacto)
  — sozinho na linha, ou se preferir, avalie se cabe mais algum campo
  nela; não force um agrupamento sem sentido só para preencher
- observacoes: Textarea, columnSpanFull()
- Section vazia "Endereços" e "Contatos" (placeholders, mesma lógica
  do "Disponível após salvar o cadastro" usada em Pessoa Física —
  serão implementados na Fase 3)

## Tabela (listagem)

- nome_fantasia (searchable, sortable)
- razao_social (searchable, toggleable oculto por padrão)
- cnpj (searchable)
- telefone
- regime_tributario (badge, a partir do enum)
- created_at (sortable, toggleable oculto por padrão)

## Labels e traduções

getModelLabel() / getPluralModelLabel(): "Pessoa Jurídica" / "Pessoas
Jurídicas"
getNavigationLabel(): "Pessoas Jurídicas"

Tudo via __() com chaves em lang/en e lang/pt_BR do plugin pessoas,
mesma convenção de namespace já usada.

## Validação

1. Rode ddev artisan optimize:clear e ddev artisan filament:assets
2. Confirme via route:list que admin/pessoas/pessoas-juridicas existe
   (index/create/edit)
3. Confirme que "Pessoas Jurídicas" aparece no Cluster, junto com
   Categorias e Pessoas Físicas
4. Teste via tinker: criar um registro com CNPJ válido (ex:
   11.222.333/0001-81) — deve funcionar. Testar com CNPJ inválido
   (dígito alterado) — deve ser rejeitado.
5. Teste via Livewire::test(...) o mount das 3 páginas
6. Renderize o HTML e confirme que os campos compactos (cnpj, cnae,
   inscricao_estadual, regime_tributario, data_abertura) têm as
   larguras calculadas corretamente, sem vazamento de label (aplicando
   a mesma fórmula max(chars, label) já usada em Pessoa Física)

Me relate os arquivos criados e o resultado dos testes.
