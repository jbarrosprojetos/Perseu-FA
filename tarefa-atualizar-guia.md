Atualize o arquivo GUIA-CRIACAO-PLUGIN.md, adicionando uma nova seção
no início (ou onde fizer mais sentido estruturalmente) chamada
"## Convenção de namespace para plugins próprios (Perseu)":

Conteúdo desta seção:
- Plugins originais do AureusERP ficam em plugins/webkul/
- Plugins customizados desenvolvidos para este projeto devem ficar em
  plugins/perseu/ (namespace próprio), mantendo clara a separação
  entre código de terceiros e código próprio — isso protege módulos
  customizados de serem afetados por um futuro `composer update` do
  AureusERP.
- Confirmado por investigação técnica: o mecanismo de descoberta de
  plugins (tela "Módulos") NÃO depende do caminho da pasta ser
  "plugins/webkul" — funciona genericamente para qualquer plugin em
  plugins/*/*/ que cumpra os requisitos abaixo.

Requisitos confirmados para um plugin novo aparecer corretamente na
tela de Módulos (instalar/desinstalar):
1. plugins/<vendor>/<pacote>/composer.json com autoload PSR-4 correto
   (coberto automaticamente pelo glob plugins/*/*/composer.json já
   configurado no composer.json raiz)
2. ServiceProvider que estende Webkul\PluginManager\PackageServiceProvider,
   com configureCustomPackage() e SEM isCore()
3. ServiceProvider registrada em bootstrap/providers.php
4. No método packageRegistered() da ServiceProvider, registrar o
   plugin no painel admin: $panel->plugin(XxxPlugin::make())
5. Convenção de nomenclatura obrigatória: plugin Filament deve se
   chamar "XxxPlugin" e a ServiceProvider "XxxServiceProvider", no
   MESMO namespace (o mecanismo de descoberta depende dessa convenção
   via str_replace)
6. A classe ServiceProvider deve estar em <plugin>/src/ (o cálculo do
   caminho base usa dirname() assumindo essa estrutura)

Após criar um plugin novo, é necessário rodar `composer dump-autoload`
e usar a ação "Sincronizar" na tela de Módulos (ou rodar o seeder
correspondente) para popular a tabela "plugins" no banco.

Não altere mais nada no arquivo além de adicionar esta seção.
