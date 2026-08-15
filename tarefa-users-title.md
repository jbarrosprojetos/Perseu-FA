No UserResource (plugins/webkul/security), o método getNavigationLabel()
já está correto e traduzido ("Usuários"). Porém, o título da página e o
breadcrumb continuam mostrando "Users" em inglês — isso indica que
$modelLabel e/ou $pluralModelLabel não estão definidos (ou não estão
usando __()).

Adicione ao UserResource:
- protected static ?string $modelLabel (singular, ex: "usuário")
- protected static ?string $pluralModelLabel (plural, ex: "usuários")

Ambos usando __() com chaves no namespace do plugin security,
reaproveitando o arquivo de idioma já criado
(security::filament/resources/role.php serviu de referência para o
Role — crie o equivalente para User se não existir: um arquivo
user.php em lang/en e lang/pt_BR).

Depois, faça a MESMA verificação (getNavigationLabel presente mas
modelLabel/pluralModelLabel ausentes) nos outros 3 Resources do plugin
security que você já confirmou terem getNavigationLabel (Company, Team,
Role) — corrija os que tiverem o mesmo problema.

Ao final, rode ddev artisan optimize:clear e liste os arquivos alterados.
