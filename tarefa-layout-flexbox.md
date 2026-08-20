No formulário de PessoaFisicaResource, as linhas com campos compactos
(Telefone+WhatsApp+Email, RG+Data+CPF, Estado Civil+Sexo+Profissão)
usam Grid::make(3) com colunas de largura igual — isso deixa espaço
vazio entre os campos compactos, já que cada um ocupa só uma fração
pequena da coluna de 1/3 que recebe.

Substitua essa abordagem por um agrupamento tipo flexbox (usar
Group::make() com ->extraAttributes(['class' => 'flex ...']) ou
mecanismo equivalente e idiomático do Filament Schemas para layout
flexível), de forma que:

- Campos compactos (com largura calculada via HasCompactFieldWidth)
  fiquem lado a lado, colados (sem espaço vazio entre eles, só um
  gap pequeno e consistente de respiro visual)
- O campo de largura "normal" da linha (Email, Profissão) ocupe todo
  o espaço restante da linha (flex-grow), preenchendo o que sobrar
  depois dos campos compactos

Aplique essa mudança às 3 linhas mencionadas. Mantenha os valores de
chars/extraSlack já calculados no HasCompactFieldWidth — a mudança é
só na estrutura de agrupamento (Grid -> flex), não nos cálculos de
largura em si.

Documente essa abordagem no CLAUDE.md, atualizando a seção de regra de
largura de campos para refletir o uso de flex em vez de Grid quando
misturar campos compactos com campos de largura normal na mesma linha.

Ao final, rode ddev artisan optimize:clear e ddev artisan filament:assets,
e descreva (via HTML renderizado) a estrutura final para eu confirmar
antes de você me pedir pra testar visualmente.
