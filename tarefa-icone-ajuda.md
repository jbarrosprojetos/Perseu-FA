Na página de Ajuda (plugins/webkul/support/src/Filament/Pages/Help.php
e a view help.blade.php), preciso adicionar o ícone/favicon configurado
em Settings > Branding, exibido ANTES do título "Perseu MRP" (heading),
como um pequeno logo/ícone ao lado ou acima do texto.

Investigue como o BrandSettings (Webkul\Support\Settings\BrandSettings)
expõe o campo "favicon" (ou, se preferir usar o logo em vez do favicon
para melhor qualidade visual, o campo "light_logo"/"dark_logo" — use
seu critério sobre qual fica melhor visualmente num contexto de
cabeçalho de página, não de aba do navegador).

Renderize essa imagem de forma DINÂMICA (lendo do BrandSettings em
tempo de execução), não com um caminho fixo, para que uma futura troca
de logo pela tela de Configurações reflita automaticamente aqui também.

Posicione o ícone/logo alinhado com o título "Perseu MRP" (ex: lado a
lado, ícone à esquerda do texto, ambos centralizados verticalmente),
com um tamanho razoável (nem muito pequeno a ponto de sumir, nem
gigante a ponto de dominar a página).

Ao final, rode ddev artisan optimize:clear e ddev artisan view:clear.
