Preciso testar temporariamente o efeito de desativar o tema Bonsai
(qalainau/bonsai-theme) no painel admin, para avaliar se vale a pena
removê-lo definitivamente.

1. No AdminPanelProvider, comente (não apague) a linha
   ->plugin(BonsaiThemePlugin::make()) e o import correspondente, de
   forma facilmente reversível.
2. Rode ddev artisan optimize:clear e ddev artisan filament:assets.
3. Não corrija mais nada agora - o objetivo é só ver o efeito visual
   sem o Bonsai ativo, em várias telas.

Me confirme quando estiver desativado e pronto para eu conferir
visualmente.
