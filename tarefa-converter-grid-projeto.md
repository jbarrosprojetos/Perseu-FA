Releia CLAUDE.md (seções de HasCompactFieldWidth, Flex vs Grid, e o
alerta do Bonsai) antes de começar. Vou converter o layout do
formulário de ProjetoResource, saindo do padrão Flex (flexRow) para
Grid::make(12), com posicionamento explícito por número de colunas.

## Nova estrutura completa do form()

### Linha 1 — Grid::make(12), ->columnSpanFull() no Grid inteiro
- numero_projeto: columnSpan(1)
- revisao: columnSpan(1)
- data_cadastro: columnSpan(1)
- descricao ("Nome da Obra"): columnSpan(4)
- tipo_projeto_id: columnSpan(2)
- situacoes: columnSpan(3)

### Linha 2 — Grid::make(12), ->columnSpanFull() no Grid inteiro
- tipo_contratante (Radio Física/Jurídica): columnSpan(3)
- Select de Cliente (pessoa_fisica_id OU pessoa_juridica_id, o que
  estiver visível): columnSpan(4)
- contato_pessoa_fisica_id: columnSpan(2)
- contato_email: columnSpan(2)
- contato_telefone: columnSpan(1)

### Linha 3
- endereco_id ("Endereço da Obra"), sozinho, columnSpanFull()
  (não precisa de Grid, só o campo direto)

### Linha 4 — Linha vazia/espaçadora
Adicione um componente de espaçamento visual (linha em branco) entre
o Endereço e os botões "Salvar alterações"/"Cancelar", com altura
adequada para dar respiro (não precisa ser um campo de verdade -
investigue a forma mais idiomática do Filament Schemas para isso, ex:
um componente Text/Html vazio com altura fixa via extraAttributes, ou
outro mecanismo limpo). LEMBRE-SE do alerta do Bonsai sobrescrever gap
com !important - se usar gap para isso, use !important também.

## Remover código antigo

Remova todo uso de HasCompactFieldWidth (compact/compactByLabel/
flexRow/grow) neste Resource - não é mais necessário, já que a largura
agora vem do columnSpan do Grid, não do cálculo por caractere. Mantenha
a lógica de negócio intacta (Radio condicional, filtro de Cliente por
e_cliente, Contato dependente de PJ, Email/Telefone do Contato,
createOptionForm de Endereço) - só a estrutura de layout muda.

## Validação

1. ddev artisan optimize:clear e ddev artisan filament:assets
2. Confirme via HTML renderizado o columnSpan exato de cada campo nas
   2 linhas Grid
3. Teste a submissão completa do formulário (PF e PJ com Contato) para
   garantir que nada quebrou funcionalmente
4. Descreva como implementou a Linha 4 (espaçadora)

Me relate o resultado e qualquer decisão de implementação que
precisou de critério próprio.
