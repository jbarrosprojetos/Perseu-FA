Duas questões na tela de Empresas (Companies) do painel admin:

## 1. "Companies" continua em inglês, mesmo após correção anterior

Você reportou ter adicionado getModelLabel()/getPluralModelLabel() ao
CompanyResource (plugins/webkul/security), mas a tela ainda mostra
"Companies" no breadcrumb e título da página
(https://perseu-fa.ddev.site/admin/companies).

Investigue:
- Confirme que o arquivo CompanyResource.php realmente contém a
  alteração (talvez não tenha sido salva, ou exista mais de um
  CompanyResource em plugins diferentes, e o que está registrado/ativo
  não é o que foi editado)
- Verifique se há cache de rotas/config do Filament que precisa ser
  limpo além do optimize:clear já rodado (ex: filament:cache-components,
  route:clear)
- Rode `ddev artisan route:list` ou equivalente para confirmar qual
  classe está de fato servindo essa rota

Corrija a causa raiz encontrada.

## 2. "Branches" em inglês dentro do formulário de edição de Empresa

Ao abrir o cadastro de uma empresa, a seção/aba de Filiais aparece com
o rótulo "Branches" em inglês. Isso provavelmente vem de um
RelationManager (localizado como
plugins/webkul/support/src/Filament/Resources/CompanyResource/RelationManagers/BranchesRelationManager.php
segundo investigação anterior, ou local equivalente).

Investigue o método $title / getTitle() desse RelationManager e
corrija para usar __() com uma chave traduzida ("Filiais"), criando a
chave em lang/en e lang/pt_BR do plugin correspondente se necessário.

Ao final, rode ddev artisan optimize:clear e me explique a causa raiz
de cada um dos dois problemas.
