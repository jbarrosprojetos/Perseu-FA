No trait HasRelationManagerDividers, remova o <hr> que fica entre o
fim do formulário (Section "Endereços") e o footer com os botões
"Salvar alterações"/"Cancelar" — mantenha APENAS o espaçamento (o
gap: 4rem já aplicado ao Form), sem a linha divisória ali.

O <hr> duplo que fica DEPOIS dos botões (antes da área de Contatos)
permanece como está, sem alteração — só o primeiro divisor (antes dos
botões) deve ser removido, mantendo apenas o espaço.

Ao final, rode ddev artisan optimize:clear e ddev artisan filament:assets,
e confirme via HTML renderizado que o <hr> antes do footer não existe
mais, mas o gap de 4rem continua presente.
