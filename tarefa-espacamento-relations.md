Na página de Edição de Pessoa Jurídica (EditPessoaJuridica), o espaço
vertical entre as seções está apertado, especificamente:

1. Entre a Section "Endereços" (placeholder) e os botões "Salvar
   alterações"/"Cancelar" — adicionar uma linha divisória (separator/
   hr, ou um espaçamento com borda inferior visível) logo depois da
   Section "Endereços".

2. Entre os botões "Salvar alterações"/"Cancelar" e a área do
   Relation Manager de Contatos (tabela) — adicionar duas linhas
   divisórias (ou um espaçamento maior + uma linha), reforçando que
   ali começa uma seção logicamente diferente (o Relation Manager, que
   tecnicamente é renderizado fora do form() principal).

Investigue a forma correta e idiomática de adicionar esse espaçamento/
divisor no contexto de uma página EditRecord do Filament que também
tem Relation Managers — pode ser via customização do layout da página,
CSS direcionado a classes existentes do Filament, ou um componente
Divider/Section do próprio Filament Schemas, o que for mais limpo e
sustentável.

Aplique de forma que sirva de padrão reutilizável para outros
Resources com Relation Managers no futuro (ex: quando adicionarmos
Endereços de verdade e o Relation Manager em Pessoa Física também),
documentando a abordagem no CLAUDE.md.

Ao final, rode ddev artisan optimize:clear e ddev artisan filament:assets.
