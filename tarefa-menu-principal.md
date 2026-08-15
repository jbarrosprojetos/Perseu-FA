Três ajustes:

## 1. Dobrar o tamanho do ícone (favicon) na página de Ajuda
No Help.php (plugins/webkul/support), o ícone ao lado de "Perseu MRP"
está com height: 2rem. Dobre para height: 4rem, mantendo width: auto.

## 2. Renomear "Plugins" para "Módulos" no menu principal (o switcher
de apps, onde aparecem os ícones de Plugins/Configurações/Ajuda)
Localize onde esse label "Plugins" é definido (provavelmente no plugin
plugin-manager, em uma classe de Página ou Plugin do Filament com
getNavigationLabel() ou label equivalente) e troque para "Módulos",
usando __() com chave traduzida (criar em lang/en como "Plugins" e
lang/pt_BR como "Módulos").

## 3. Trocar os ícones desses 3 itens do menu principal
- Módulos (ex-Plugins): heroicon-o-squares-2x2
- Configurações: manter o ícone atual, sem alteração
- Ajuda: heroicon-o-lifebuoy

Confirme que heroicon-o-lifebuoy existe no pacote de heroicons
instalado antes de usar; se não existir, escolha o mais próximo
semanticamente disponível (ex: heroicon-o-phone ou heroicon-o-chat-bubble-left-right)
e me avise qual usou.

Ao final, rode ddev artisan optimize:clear e ddev artisan view:clear.
