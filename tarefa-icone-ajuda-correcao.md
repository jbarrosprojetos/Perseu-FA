Correção na página de Ajuda (plugins/webkul/support/src/Filament/Pages/Help.php):
o cabeçalho está usando "light_logo"/"dark_logo" do BrandSettings, mas
deveria usar o campo "favicon".

Contexto importante para não repetir esse erro no futuro: neste
sistema, "favicon" representa a identidade do PRODUTO Perseu (o
software em si), enquanto "light_logo"/"dark_logo" representam a
identidade da EMPRESA CLIENTE que está usando o sistema (usado, por
exemplo, na topbar). São propositalmente campos distintos, para marcas
diferentes.

Troque o getHeading() do Help.php para usar o campo "favicon" do
BrandSettings em vez de light_logo/dark_logo, mantendo o mesmo
tratamento dinâmico (lendo em tempo de execução) e a mesma lógica de
resolução de URL já implementada. Como o favicon normalmente é um
ícone (não uma logo horizontal), talvez seja necessário ajustar o
tamanho/proporção para ficar visualmente adequado ao lado do texto
"Perseu MRP" (não precisa necessariamente usar logo_height, que foi
pensado para logo horizontal — use um tamanho fixo razoável para
ícone, como 32-40px de altura, a menos que ache melhor manter
proporcional).

Ao final, rode ddev artisan optimize:clear e ddev artisan view:clear.
