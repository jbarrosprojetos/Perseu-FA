Refatorar o trait HasCompactFieldWidth
(plugins/perseu/pessoas/src/Traits/HasCompactFieldWidth.php) para
calcular a largura com base na quantidade de caracteres esperada do
campo, em vez de um valor fixo em pixels para todos.

## Nova assinatura

O método compact() deve aceitar um parâmetro de quantidade de
caracteres esperada, ex:
HasCompactFieldWidth::compact($component, chars: 15)

Internamente, calcule a largura usando a unidade CSS "ch" (que
representa a largura do caractere "0" na fonte atual): largura =
(chars + margem de folga) . "ch". Use uma margem de folga pequena (ex:
+2ch) para acomodar padding interno do input e, quando aplicável,
ícones (calendário do DatePicker, seta do Select).

## Aplicar a cada campo do PessoaFisicaResource, calculando os
caracteres reais esperados:

- Telefone: máscara "(99) 99999-9999" = 15 caracteres
- CPF: máscara "999.999.999-99" = 14 caracteres
- RG: sem máscara fixa, mas defina um valor razoável baseado no
  padrão brasileiro mais comum (ex: "99.999.999-9" = 12 caracteres)
- Data de Nascimento: "10/11/1971" = 10 caracteres, mais folga extra
  para o ícone de calendário do input type=date (adicione +2ch a mais
  que o padrão, pela natureza desse componente)
- Estado Civil: calcule dinamicamente o comprimento do MAIOR label
  entre os casos do enum EstadoCivil (ex: "União estável" tem 13
  caracteres) — se possível, implemente isso via código (percorrendo
  EstadoCivil::cases() e medindo strlen do getLabel() de cada um) em
  vez de contar manualmente, para que a largura se ajuste sozinha caso
  o enum ganhe um novo caso maior no futuro
- Sexo: mesmo princípio, calculado dinamicamente a partir do enum Sexo

Para os dois Selects (Estado Civil e Sexo), se o cálculo dinâmico via
enum for muito trabalhoso de implementar de forma limpa, pode usar um
valor fixo calculado manualmente a partir do maior label atual, desde
que documente no código que esse valor deve ser revisado se o enum
mudar — mas prefira a solução dinâmica se for razoável de implementar.

Atualize CLAUDE.md com a nova assinatura do método e o princípio de
cálculo (baseado em caracteres esperados, não em valor de pixel fixo),
para uso consistente em Pessoa Jurídica e outros formulários futuros.

Ao final, rode ddev artisan optimize:clear e ddev artisan filament:assets.
