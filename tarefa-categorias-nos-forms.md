Adicionar seleção de Categorias (relação muitos-para-muitos já
existente desde a Fase 1, via pivots pessoa_fisica_categoria e
pessoa_juridica_categoria) aos formulários de PessoaFisicaResource e
PessoaJuridicaResource.

## Em ambos os Resources

Adicione um campo de seleção múltipla (estilo "tags"), logo após o
campo Nome/Nome Fantasia (antes das linhas flexRow), usando
Select::make('categorias')->relationship() com ->multiple() e
->preload() e ->searchable(), renderizando como chips/tags (o Select
multiple do Filament já faz isso nativamente).

IMPORTANTE — filtrar as opções por contexto:
- Em PessoaFisicaResource: mostrar apenas CategoriaPessoa onde
  aplica_pf = true (usar modifyQueryUsing no relationship() para
  filtrar)
- Em PessoaJuridicaResource: mostrar apenas CategoriaPessoa onde
  aplica_pj = true

O campo deve ocupar a largura total da linha (columnSpanFull) — não é
um campo compacto, é uma lista que pode ter vários itens.

Label: "Categorias" (via __(), mesma convenção de tradução já usada).

## Verificação de relacionamento

Confirme que os relacionamentos categorias() já definidos nos models
PessoaFisica e PessoaJuridica (criados na Fase 1) apontam
corretamente para os pivots certos (pessoa_fisica_categoria e
pessoa_juridica_categoria respectivamente) antes de usar
->relationship('categorias', 'descricao') no formulário — se algo
estiver incorreto ou incompleto nos models, corrija.

## Também adicionar a coluna na tabela (listagem)

Em ambos os Resources, adicione uma coluna na tabela mostrando as
categorias vinculadas (ex: TextColumn com badge, listando os nomes
separados por vírgula ou como badges individuais).

## Validação

1. Rode ddev artisan optimize:clear e ddev artisan filament:assets
2. Via tinker, crie uma CategoriaPessoa com aplica_pf=true,
   aplica_pj=false, e confirme que ela aparece como opção disponível
   no formulário de Pessoa Física mas NÃO no de Pessoa Jurídica
   (inspecionando o HTML renderizado das opções do Select)
3. Teste vincular uma categoria a um registro de Pessoa Física via
   Livewire::test (set + call create) e confirme que a relação foi
   persistida no banco (via ddev mysql, consultando a tabela pivot)

Me relate o resultado.
