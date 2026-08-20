Preciso entender com precisão como a tela de "Módulos" (plugin-manager,
antes chamada "Plugins") descobre quais plugins existem e exibe na
lista de instalar/desinstalar.

Investigue o código do plugin plugins/webkul/plugin-manager:
1. Como ele detecta quais plugins existem no sistema — é uma varredura
   de diretório hardcoded para "plugins/webkul" especificamente, ou é
   mais genérico (ex: lê todos os pacotes registrados via composer.json/
   bootstrap/providers.php, independente do caminho da pasta)?
2. O que exatamente precisa existir/estar registrado para um plugin
   NOVO aparecer nessa lista (ex: apenas estar em bootstrap/providers.php
   é suficiente, ou precisa de algum outro registro específico, como um
   arquivo de manifesto, uma anotação, ou estar listado em algum
   composer.json raiz)?

Depois de confirmar isso, me responda diretamente: se eu criar um
plugin em plugins/perseu/pessoas (namespace próprio, fora de
plugins/webkul), ele apareceria normalmente na tela de Módulos para
instalar/desinstalar, ou exigiria algum ajuste adicional no
plugin-manager para reconhecer esse novo caminho/namespace?

NÃO altere nada ainda — apenas investigue e relate a resposta.
