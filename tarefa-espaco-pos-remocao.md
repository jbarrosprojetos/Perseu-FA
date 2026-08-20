Depois de remover a Section placeholder "Endereços" dos formulários
(PessoaFisicaResource e PessoaJuridicaResource), o espaçamento
gap: 4rem (aplicado via HasRelationManagerDividers::getFormContentComponent())
não está mais gerando respiro visível entre o último campo do
formulário (agora "Observações") e o footer com os botões "Salvar
alterações"/"Cancelar" — voltou a ficar colado.

Investigue por que a remoção do placeholder afetou esse espaçamento
(confirme se o gap: 4rem ainda está presente no HTML da tag <form>, e
se sim, por que o efeito visual não está aparecendo — pode ser que o
Textarea de Observações, sendo um campo de altura variável/maior,
esteja se comportando diferente da Section removida em relação ao
container flex).

Corrija para que volte a haver respiro visual claro (equivalente ao
que já tínhamos) entre Observações e os botões, em ambas as páginas de
Edição (Pessoa Física e Pessoa Jurídica).

Ao final, rode ddev artisan optimize:clear e ddev artisan filament:assets,
e confirme via HTML renderizado a causa e a correção aplicada.
