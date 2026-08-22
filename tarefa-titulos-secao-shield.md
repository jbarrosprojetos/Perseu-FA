Na tela de edição de Função (Configurações > Funções > Editar), os
títulos das seções que agrupam permissões por Resource aparecem em
inglês: "Field", "Partner" (e possivelmente outros: PluginManager,
Security, Support também podem estar sem tradução, verificar todos).
"Pessoas" já está correto, então o mecanismo de tradução funciona
quando configurado - falta aplicar aos demais.

Investigue de onde vem esse título de seção no Shield RoleResource
(provavelmente FilamentShield::getLocalizedResourceLabel() ou
mecanismo similar em HasLabelResolver, usando o $modelLabel ou
$pluralModelLabel do próprio Resource correspondente).

Para cada um que estiver em inglês (Field, Partner, e confirme se
PluginManager/Security/Support também precisam), localize o Resource
correspondente (ex: FieldResource, PartnerResource, plugins
plugins/webkul/fields e plugins/webkul/partners respectivamente) e
adicione/corrija getModelLabel()/getPluralModelLabel() via __() com
chave traduzida, seguindo o mesmo padrão já usado em User/Company/
Team/Role (correção anterior) - sem esquecer de verificar se há
Resources duplicados entre plugins (like o caso Company security vs.
support que já identificamos antes - documentado no CLAUDE.md).

Ao final, rode ddev artisan optimize:clear e ddev artisan filament:assets,
e liste quais seções foram corrigidas, com os arquivos alterados.
