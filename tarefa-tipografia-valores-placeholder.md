Além do label do Placeholder (já corrigido), o VALOR exibido (o texto
com o dado em si: "2610001", "00", "22/08/2026 18:25", o e-mail, o
telefone) também está com tipografia maior que os demais campos e
desalinhado verticalmente (aparece um pouco mais abaixo do centro da
linha, comparado ao texto dentro de um TextInput normal).

Investigue a classe CSS usada para o VALOR de um TextEntry/Placeholder
(diferente da classe de label já corrigida em admin-entry-label.css -
procure algo como .fi-in-entry-content ou equivalente) e corrija,
seguindo o mesmo princípio já usado (reaproveitar/igualar aos valores
que o Bonsai já aplica a inputs normais), tanto tamanho de fonte
quanto alinhamento vertical dentro da linha.

Além disso, aplique NEGRITO (font-weight bold) especificamente aos
valores de numero_projeto, revisao e data_cadastro - MAS NÃO aos
valores de contato_email e contato_telefone (esses devem receber
apenas a correção de tamanho/alinhamento, sem negrito).

Para diferenciar quais Placeholders recebem negrito, use uma classe
CSS adicional aplicada via extraAttributes() apenas nesses 3 campos
específicos (ex: uma classe "fi-entry-bold" ou nome similar), e o CSS
correspondente só aplica font-weight bold quando essa classe estiver
presente.

Ao final, rode ddev artisan optimize:clear e ddev artisan filament:assets.
Confirme via HTML renderizado que os 3 campos (numero_projeto,
revisao, data_cadastro) têm a classe extra de negrito, e que
contato_email/contato_telefone não têm.
