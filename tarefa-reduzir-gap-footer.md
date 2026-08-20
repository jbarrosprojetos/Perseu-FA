Agora que o gap: 6rem !important está funcionando de verdade (o Bonsai
não está mais zerando), o espaço ficou grande demais visualmente.

Reduza o valor de formFooterGap() em HasRelationManagerDividers de
6rem para 2rem, mantendo o !important.

Ao final, rode ddev artisan optimize:clear e ddev artisan filament:assets.
