Nos campos compactos (dentro de flexRow), o LABEL de cada campo (ex:
"Data de Nascimento", "É WhatsApp?") é mais largo em texto do que a
largura calculada (max-width em ch) do campo/input abaixo dele. Como o
label não quebra linha, ele visualmente "vaza" para o espaço do campo
vizinho na mesma linha, mesmo com o gap de 2ch aplicado entre as caixas
dos inputs.

Investigue a estrutura do wrapper (.fi-input-wrp ou o container mais
externo do componente de campo, incluindo o label) e resolva o
vazamento com uma das abordagens abaixo (escolha a que ficar mais
limpa visualmente, teste e explique a escolha):

Opção A: permitir que o texto do label quebre em 2 linhas quando
necessário (word-wrap/white-space normal, em vez de nowrap), mantendo
a largura compacta do campo abaixo.

Opção B: quando o label for mais longo que o max-width calculado do
campo, considerar o comprimento do LABEL (não só o do valor esperado)
no cálculo de largura do HasCompactFieldWidth — ou seja, a largura
final seria o maior entre (chars do valor esperado) e (chars do texto
do label traduzido).

Prefira a Opção B se for razoável de implementar de forma limpa
(calculando dinamicamente o tamanho do label a partir da tradução
->label() já definida em cada campo), já que resolve a causa raiz sem
comprometer a leitura do label. Se for muito complexo, use a Opção A.

Ao final, rode ddev artisan optimize:clear e ddev artisan filament:assets,
e confirme via HTML renderizado a largura final de cada campo afetado
(especialmente Data de Nascimento e É WhatsApp?, que têm os labels mais
longos).
