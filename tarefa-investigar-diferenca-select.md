Depois da correção anterior (extraSlackFor para Select), os campos
tipo_projeto_id e contato_pessoa_fisica_id em ProjetoResource ainda
mostram sobreposição visual entre o texto do valor selecionado e a
seta/ícone do dropdown, mesmo com a largura teoricamente corrigida
(32ch). Testado com reload completo da aba (não é cache de navegador).

O campo pessoa_juridica_id ("Cliente"), que também é um Select, NÃO
tem esse problema.

## Suspeita concreta a verificar PRIMEIRO: vendor/qalainau/bonsai-theme

Já tivemos um caso confirmado anteriormente onde o CSS do Bonsai
sobrescrevia gap com !important, afetando estilos inline nossos (ver
seção correspondente no CLAUDE.md). Investigue especificamente se
bonsai.css tem alguma regra !important relacionada a select nativo
(.fi-fo-select-native, padding, width, posição de ícone/seta) que
possa estar entrando em conflito com nosso max-width inline nos
Selects nativos especificamente (mas não nos que usam ->searchable(),
se essa for a diferença).

## Se não for o Bonsai, investigue a diferença estrutural

1. tipo_projeto_id e contato_pessoa_fisica_id têm ->searchable()
   ativado, ou são Select nativos do HTML (sem JS de busca)?
2. pessoa_juridica_id/pessoa_fisica_id têm ->searchable() ativado?
3. Selects nativos (fi-fo-select-native) e Selects com searchable
   (JS, tipo Choices.js) podem ter estruturas HTML diferentes, com o
   ícone de seta ocupando espaço de formas diferentes em cada caso -
   confirme via HTML/CSS renderizado real.
4. Confirme se a largura calculada (32ch) está caindo no elemento
   certo (.fi-input-wrp) para esses dois campos especificamente.

Corrija a causa raiz encontrada (seja Bonsai com !important exigindo
nosso próprio !important, seja diferença de slack necessário entre
select nativo vs. searchable, seja outro motivo).

Ao final, rode ddev artisan optimize:clear e ddev artisan filament:assets.
Relate exatamente qual foi a causa raiz confirmada.
