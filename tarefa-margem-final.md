Nas linhas com flexRow (campos compactos), apesar do gap de 2ch já
aplicado entre os itens do flex, os LABELS de campos vizinhos ainda
aparecem visualmente colados (ex: "É WhatsApp?" colado em "E-mail").

Investigue: o gap: 2ch atual está sendo aplicado ao container flex
pai (.fi-sc-flex), mas os elementos internos (.fi-fo-field, que
contém o label + input/toggle) podem estar com largura calculada
exata, sem margem própria, então o gap do flex container pode não
estar "chegando" visualmente entre os textos dos labels da forma
esperada (ex: se o Toggle usa .fi-fo-field com um wrapper diferente
do .fi-input-wrp, o gap pode não se aplicar entre eles do mesmo jeito).

Solução: adicione um "padding-right" (ou margin-right) fixo de 2ch em
CADA campo dentro do flexRow (aplicado ao wrapper mais externo do
campo, .fi-fo-field ou equivalente), como respiro adicional e
consistente, além do gap já existente do container — não é para
substituir o gap, é um reforço aplicado item por item.

Aplique isso de forma centralizada dentro de flexRow() ou compact()/
compactByLabel() no trait HasCompactFieldWidth, para valer
automaticamente em qualquer campo compacto futuro, sem precisar
repetir isso manualmente Resource por Resource.

Ao final, rode ddev artisan optimize:clear e ddev artisan filament:assets,
e confirme via HTML renderizado que cada campo do flexRow tem o
padding/margin de 2ch aplicado, além do gap do container.
