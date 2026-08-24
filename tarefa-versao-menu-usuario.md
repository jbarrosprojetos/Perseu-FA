No menu suspenso do avatar do usuário (canto superior direito do
painel admin), aparece um indicador com um ícone + "Versão 1.5.0",
antes do nome do usuário e do botão "Sair".

Investigue:
1. Onde exatamente esse indicador de versão é definido (procure por
   "1.5.0" ou "Versão" no código - pode ser um userMenuItems() no
   AdminPanelProvider, um Widget, ou um Blade view customizado de
   algum plugin, provavelmente webkul/support ou webkul/security)
2. De onde vem o número "1.5.0" - é uma string fixa em algum lugar,
   ou é lido de um arquivo (composer.json, um arquivo VERSION, uma
   config)?
3. De onde vem o ícone usado ali - é uma imagem fixa (provavelmente
   o logo original da Aureus), ou já é dinâmico via BrandSettings
   (favicon/logo)?

Depois de confirmar:
1. Se o número da versão for uma string fixa em código, altere para
   "1.0.0". Se for lido de um arquivo/config, me informe qual arquivo
   é antes de decidir se altero ali ou sobrescrevo em algum lugar
   mais apropriado (ex: se for a versão do pacote raiz do AureusERP,
   pode não fazer sentido "forçar" 1.0.0 ali - explique a situação e
   sugira a melhor abordagem antes de aplicar).
2. Se o ícone for fixo (não vindo de BrandSettings), altere para usar
   o campo "favicon" do BrandSettings dinamicamente, seguindo o MESMO
   padrão já implementado na página de Ajuda (Perseu\Support\...\Help.php,
   que já resolve URL de favicon dinamicamente) - reaproveite a lógica
   se possível, sem duplicar.

Ao final, rode ddev artisan optimize:clear e ddev artisan filament:assets.
Relate onde encontrou e o que foi alterado.
