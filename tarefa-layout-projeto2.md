Ajustes de layout em ProjetoResource (plugin Comercial), reorganizando
o começo do formulário:

## 1. Consolidar em UMA linha só (flexRow)

Juntar numero_projeto, revisao, data_cadastro, descricao ("Nome da
Obra"), tipo_projeto_id e situacoes em uma única linha (hoje estão
separados em blocos distantes). Larguras:
- numero_projeto, revisao, data_cadastro: compactos (como já estão)
- descricao: cresce (grow), mas sem exagerar - é o nome da obra, texto
  médio, não precisa ocupar metade da tela
- tipo_projeto_id: compacto
- situacoes: pode crescer também, mas avalie o espaço restante - se
  ficar muito espremido nessa configuração de 6 itens numa linha só,
  use seu critério para dar mais respiro a este campo especificamente
  (ele tem badges/chips, precisa de espaço vertical e horizontal
  razoável)

## 2. Encurtar os labels do Radio de Contratante

Trocar as opções do Radio de "Pessoa Física" / "Pessoa Jurídica" para
apenas "Física" / "Jurídica" (mais curto, ganha espaço horizontal na
linha). Ajustar também o max-width fixo que foi calculado
manualmente para esse Radio, reduzindo proporcionalmente ao novo
texto mais curto.

## 3. Reduzir a largura do campo Contato

O Select de Contato está ocupando espaço demais, fazendo com que
E-mail e Telefone (que aparecem ao lado) fiquem espremidos e quebrando
em várias linhas dentro da própria caixa. Reduza a largura do Contato
para aproximadamente metade do que está hoje, e reavalie/ajuste a
largura de E-mail e Telefone para que caibam confortavelmente na
mesma linha, sem quebrar texto.

## Validação

1. Rode ddev artisan optimize:clear e ddev artisan filament:assets
2. Confirme via HTML renderizado as novas larguras aplicadas
3. Teste a submissão completa do formulário para garantir que nada
   quebrou

Me relate o resultado e qualquer ajuste de largura que precisou de
critério próprio.
