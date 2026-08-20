## Objetivo: criar uma REGRA REUTILIZÁVEL de largura de campo, aplicá-la
ao PessoaFisicaResource, e documentar para uso em formulários futuros
do projeto (Pessoa Jurídica e outros plugins).

## 1. Investigar a causa do layout quebrado atual

No PessoaFisicaResource.php, os Grids aninhados (Grid dentro de Grid)
fizeram Email e CPF caírem para linhas próprias, em vez de ficarem ao
lado de Telefone/RG como pretendido. Investigue por que isso aconteceu
(provavelmente o Grid aninhado ocupa a largura total da célula pai,
"empurrando" os componentes seguintes da grid externa). Reconstrua o
formulário SEM aninhar Grid dentro de Grid — use uma estrutura mais
simples (ex: um único Grid::make(N) por linha, com columnSpan()
explícito em cada campo, sem aninhamento).

## 2. Criar uma regra reutilizável de largura visual

Campos de conteúdo curto/formato conhecido (telefone, CPF, CNPJ, RG,
CEP, DatePicker, Select/dropdown, Toggle) devem ter largura visual
LIMITADA (não esticar para preencher a coluna inteira), mesmo em telas
grandes. Investigue o mecanismo correto do Filament 5/Schemas para
isso (ex: extraInputAttributes(['style' => 'max-width: 220px']),
ou ->maxWidth() se existir no componente, ou outra abordagem
idiomática) e crie uma forma reutilizável de aplicar isso — pode ser
um Trait, uma classe helper estática, ou uma macro em
Filament\Forms\Components\TextInput, que outros Resources do projeto
(incluindo Pessoa Jurídica futuramente) possam reaproveitar sem
repetir o mesmo código toda vez.

Sugestão de nome: algo como HasCompactFieldWidth (trait) ou um helper
CompactField::make() que envolve a definição, à sua escolha de
implementação mais idiomática ao Filament.

## 3. Reconstruir o formulário de PessoaFisicaResource com o layout:

- Nome: columnSpanFull()
- Telefone (compacto) + É WhatsApp? (toggle) + E-mail (largura normal),
  os 3 na mesma linha
- RG (compacto) + Data de Nascimento (compacta) + CPF (compacto),
  os 3 na mesma linha
- Estado Civil (compacto) + Sexo (compacto) + Profissão (largura
  normal), os 3 na mesma linha
- Observações: columnSpanFull()

## 4. Documentar a regra

Adicione ao CLAUDE.md uma seção "## Regra de largura de campos em
formulários" explicando a convenção (campos curtos = compactos,
campos livres = full width) e como usar o helper/trait criado, para
que seja seguida consistentemente nos próximos Resources (Pessoa
Jurídica, Endereços, e plugins futuros).

Ao final, rode ddev artisan optimize:clear e ddev artisan filament:assets.
