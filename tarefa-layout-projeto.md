Releia CLAUDE.md (seções sobre HasCompactFieldWidth, Flex vs Grid, e
o alerta do Bonsai sobrescrevendo gap) antes de começar. Reorganizar
completamente o formulário de ProjetoResource (plugin Comercial),
seguindo os mesmos padrões já estabelecidos em PessoaFisicaResource/
PessoaJuridicaResource.

## Nova ordem e disposição

**Linha 1 (flexRow, campos somente leitura/gerados):**
- numero_projeto (compacto)
- revisao: manter formatado com zero à esquerda (ex: "00", "01"), mas
  REMOVER o prefixo "R" que aparece hoje — mostrar só o número
- data_cadastro (compacto)

**Linha 2 (flexRow ou Grid conforme fizer mais sentido):**
- descricao ("Nome da Obra") — pode continuar ocupando mais espaço,
  já que é texto livre
- tipo_projeto_id (compacto)
- situacoes (multi-select, precisa de mais espaço - avalie se cabe
  bem nesta linha ou se necessita ficar mais larga)

**Linha 3 — Contratante:**
- O Radio de "tipo_contratante" (Pessoa Física / Pessoa Jurídica)
  atualmente é exibido verticalmente (empilhado). Altere para exibição
  HORIZONTAL (as duas opções lado a lado, não uma embaixo da outra) —
  investigue a opção correta do componente Radio do Filament para
  isso (ex: ->inline() ou equivalente).
- O Select de Cliente (pessoa_fisica_id OU pessoa_juridica_id,
  conforme a escolha) deve ficar NA MESMA LINHA do Radio, ao lado
  dele (não embaixo).

## Contato + Email/Telefone (novo comportamento)

Quando o contratante for Pessoa Jurídica E um Contato for selecionado,
exibir automaticamente (na mesma linha do campo Contato, logo à
direita) o E-mail e Telefone dessa Pessoa Física escolhida como
contato — como campos somente leitura (Placeholder ou TextInput
disabled), atualizados reativamente (->live()) sempre que o Contato
mudar. Esses dados vêm do próprio registro de PessoaFisica (campos
email e telefone já existentes), não são novos campos no banco.

## Endereço

- Renomear o label do campo de "Endereço" para "Endereço da Obra" (em
  pt_BR e en, ajustando a tradução em inglês para "Project Address"
  ou equivalente)
- Mantenha na sua própria linha, como já está

## Validação

1. Rode ddev artisan optimize:clear e ddev artisan filament:assets
2. Confirme via HTML renderizado (Livewire::test) que:
   - revisao aparece sem o prefixo "R"
   - O Radio de tipo_contratante está inline/horizontal
   - Ao selecionar um Contato de uma PJ, email e telefone aparecem
     preenchidos e corretos
   - O label "Endereço da Obra" aparece em vez de "Endereço"
3. Teste a submissão completa do formulário depois das mudanças, para
   garantir que nada quebrou funcionalmente

Me relate o resultado, incluindo qualquer decisão de layout que
precisou de critério próprio.
