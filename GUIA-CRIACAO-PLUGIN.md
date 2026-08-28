# Guia de Criação de Plugins — AureusERP / Perseu

Passo a passo para criar novos plugins em `plugins/webkul/{nome}/`, seguindo
as convenções observadas nos plugins existentes (`contacts`, `products`,
`support`, `plugin-manager`). Este guia complementa `AUDITORIA-ESTRUTURA.md`.

---

## Convenção de namespace para plugins próprios (Perseu)

- Plugins originais do AureusERP ficam em `plugins/webkul/`.
- Plugins customizados desenvolvidos para este projeto devem ficar em
  `plugins/perseu/` (namespace próprio), mantendo clara a separação entre
  código de terceiros e código próprio — isso protege módulos customizados de
  serem afetados por um futuro `composer update` do AureusERP.
- Confirmado por investigação técnica: o mecanismo de descoberta de plugins
  (tela "Módulos") **NÃO** depende do caminho da pasta ser `plugins/webkul` —
  funciona genericamente para qualquer plugin em `plugins/*/*/` que cumpra os
  requisitos abaixo.

Requisitos confirmados para um plugin novo aparecer corretamente na tela de
Módulos (instalar/desinstalar):

1. `plugins/<vendor>/<pacote>/composer.json` com autoload PSR-4 correto
   (coberto automaticamente pelo glob `plugins/*/*/composer.json` já
   configurado no `composer.json` raiz).
2. ServiceProvider que estende `Webkul\PluginManager\PackageServiceProvider`,
   com `configureCustomPackage()` e **sem** `isCore()`.
3. ServiceProvider registrada em `bootstrap/providers.php`.
4. No método `packageRegistered()` da ServiceProvider, registrar o plugin no
   painel admin: `$panel->plugin(XxxPlugin::make())`.
5. Convenção de nomenclatura obrigatória: o plugin Filament deve se chamar
   `XxxPlugin` e a ServiceProvider `XxxServiceProvider`, no **mesmo** namespace
   (o mecanismo de descoberta depende dessa convenção via `str_replace`).
6. A classe ServiceProvider deve estar em `<plugin>/src/` (o cálculo do caminho
   base usa `dirname()` assumindo essa estrutura).

Após criar um plugin novo, é necessário rodar `composer dump-autoload` e usar a
ação "Sincronizar" na tela de Módulos (ou rodar o seeder correspondente) para
popular a tabela `plugins` no banco.

---

## 1. Estrutura de pastas esperada

Todo plugin mora em `plugins/webkul/{nome}/` e é composto por:

```
plugins/webkul/{nome}/
├── composer.json                          # autoload PSR-4 + provider do Laravel
├── config/
│   └── filament-shield.php                # permissões Shield deste plugin
├── database/
│   ├── factories/                         # factories por model (opcional)
│   ├── migrations/                        # migrações com prefixo {plugin}_ no nome da tabela
│   ├── seeders/                           # DatabaseSeeder e seeders específicos (opcional)
│   └── settings/                          # migrações de settings (opcional)
├── resources/
│   ├── lang/
│   │   ├── en/                            # idioma canônico (obrigatório)
│   │   ├── pt_BR/                         # tradução obrigatória neste projeto
│   │   ├── es/
│   │   └── ar/
│   └── views/                             # blades (apenas se usar hasViews)
├── routes/
│   └── api.php                            # rotas de API (se declarar hasRoutes)
├── src/
│   ├── {Nome}Plugin.php                   # classe Filament Plugin
│   ├── {Nome}ServiceProvider.php          # PackageServiceProvider
│   ├── Enums/                             # enums do plugin
│   ├── Filament/
│   │   ├── Clusters/                      # clusters de navegação
│   │   ├── Pages/                         # páginas avulsas
│   │   ├── Resources/{Recurso}Resource/   # resources (com subpáginas)
│   │   │   ├── Pages/...
│   │   │   ├── Schemas/...                # form/infolist compartilhados (opcional)
│   │   │   └── Tables/...                 # tabelas compartilhadas (opcional)
│   │   └── Widgets/
│   ├── Http/                              # controllers/requests/resources de API (opcional)
│   ├── Models/                            # Eloquent models
│   ├── Observers/                         # observers (opcional)
│   ├── Policies/                          # policies manuais (obrigatório)
│   ├── Settings/                          # classes de settings (opcional)
│   └── Traits/                            # traits internos (opcional)
└── tests/
    ├── Feature/...
    └── Unit/...
```

Referências: `plugins/webkul/products/` (exemplo completo com migrations,
factories, seeders, settings, API, tests) e `plugins/webkul/contacts/`
(exemplo mínimo, sem database próprio).

## 2. Classe Plugin (contrato do Filament)

Cada plugin declara uma classe que implementa `Filament\Contracts\Plugin`
em `src/{Nome}Plugin.php`. Exemplo baseado em `ProductPlugin`:

```php
<?php

namespace Webkul\Product;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Webkul\PluginManager\Package;

class ProductPlugin implements Plugin
{
    public function getId(): string
    {
        return 'products';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        if (! Package::isPluginInstalled($this->getId())) {
            return;
        }

        $panel
            ->when($panel->getId() == 'admin', function (Panel $panel) {
                $panel
                    ->discoverResources(
                        in: __DIR__.'/Filament/Resources',
                        for: 'Webkul\\Product\\Filament\\Resources'
                    )
                    ->discoverPages(
                        in: __DIR__.'/Filament/Pages',
                        for: 'Webkul\\Product\\Filament\\Pages'
                    )
                    ->discoverClusters(
                        in: __DIR__.'/Filament/Clusters',
                        for: 'Webkul\\Product\\Filament\\Clusters'
                    )
                    ->discoverWidgets(
                        in: __DIR__.'/Filament/Widgets',
                        for: 'Webkul\\Product\\Filament\\Widgets'
                    );
            });
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
```

Pontos importantes:

- **`getId()`** retorna o nome do plugin (plural, snake_case — o mesmo nome
  da pasta e o mesmo usado no banco na tabela `plugins`).
- **`make()`** resolve a classe via container (`app(static::class)`).
- **`register()`**:
  - Verifica `Package::isPluginInstalled(getId())` antes de registrar
    qualquer coisa — plugin desinstalado não deve expor recursos.
  - Só expõe os recursos no painel `admin` (`$panel->getId() == 'admin'`).
  - Usa `discoverResources`/`discoverPages`/`discoverClusters`/`discoverWidgets`
    apontando `in:` para o diretório físico e `for:` para o namespace.
  - Os diretórios que ainda não existem podem ser deixados de fora; os
    métodos de descoberta só encontram classes de fato.
- **`boot()`** pode registrar render hooks, assets etc. (ver
  `SupportPlugin::boot()`), ou ficar vazio.

O plugin deve ser registrado no painel dentro do Service Provider (seção 3),
nunca manualmente no `AdminPanelProvider`.

## 3. Service Provider

Cada plugin estende `Webkul\PluginManager\PackageServiceProvider` (que por
sua vez estende o `PackageServiceProvider` do spatie/laravel-package-tools).
O `Package` usado é `Webkul\PluginManager\Package`.

Exemplo (products — mais completo):

```php
<?php

namespace Webkul\Product;

use Filament\Panel;
use Webkul\Chatter\Services\ChatterCleanupService;
use Webkul\PluginManager\Console\Commands\InstallCommand;
use Webkul\PluginManager\Console\Commands\UninstallCommand;
use Webkul\PluginManager\Package;
use Webkul\PluginManager\PackageServiceProvider;
use Webkul\Product\Models\Category;
use Webkul\Product\Models\Product;
use Webkul\Product\Observers\UOMObserver;
use Webkul\Support\Models\UOM;

class ProductServiceProvider extends PackageServiceProvider
{
    public static string $name = 'products';

    public static string $viewNamespace = 'products';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasViews()
            ->hasTranslations()
            ->hasRoutes(['api'])
            ->hasMigrations([...])           // nomes dos arquivos em database/migrations/
            ->hasSeeder('Webkul\\Product\\Database\\Seeders\\DatabaseSeeder')
            ->runsMigrations()
            ->hasSettings([...])             // nomes dos arquivos em database/settings/
            ->runsSettings()
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->runsMigrations()
                    ->runsSeeders();
            })
            ->hasUninstallCommand(function (UninstallCommand $command) {
                $command->endWith(function () {
                    ChatterCleanupService::purgeForModels([Category::class, Product::class]);
                });
            });
    }

    public function packageBooted(): void
    {
        if (! Package::isPluginInstalled(static::$name)) {
            return;
        }

        UOM::observe(UOMObserver::class);
    }

    public function packageRegistered(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            $panel->plugin(ProductPlugin::make());
        });
    }
}
```

Exemplo mínimo (contacts):

```php
class ContactServiceProvider extends PackageServiceProvider
{
    public static string $name = 'contacts';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasTranslations()
            ->hasInstallCommand(function (InstallCommand $command) {})
            ->hasUninstallCommand(function (UninstallCommand $command) {})
            ->icon('contacts');
    }

    public function packageBooted(): void
    {
        //
    }

    public function packageRegistered(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            $panel->plugin(ContactPlugin::make());
        });
    }
}
```

O que o provider registra/declara:

| Método do Package | O que faz |
| --- | --- |
| `name()` | Nome do plugin (igual ao `$name`, usado como namespace de tradução/views). |
| `isCore()` | Marca plugin core (support, plugin-manager). Plugins de negócio NÃO usam. |
| `hasViews()` | Carrega `resources/views/` no namespace `{viewNamespace}`. |
| `hasTranslations()` | Carrega `resources/lang/` no namespace `{shortName}` (ex.: `products::...`). **Sempre usar.** |
| `hasRoutes([...])` | Carrega `routes/{arquivo}.php`. |
| `hasMigrations([...])` | Declara os nomes dos arquivos de migração (sem `.php`). |
| `runsMigrations()` | Faz as migrações declaradas rodarem automaticamente quando o plugin está instalado. |
| `hasSeeder(...)` / `hasSeeders(...)` | Registra classes de seeders. |
| `runsSeeders()` | Roda os seeders no install. |
| `hasSettings([...])` / `runsSettings()` | Declara migrações de settings (`database/settings/`). |
| `hasInstallCommand(fn (InstallCommand $cmd) => ...)` | Cria o comando `{nome}:install` (seção 7). |
| `hasUninstallCommand(fn (UninstallCommand $cmd) => ...)` | Cria o comando `{nome}:uninstall`. |
| `hasDependency(...)` / `hasDependencies(...)` | Declara dependências entre plugins. |
| `icon()` | Ícone exibido na lista de plugins do plugin-manager. |

O **config de Shield** (`config/filament-shield.php`) é mergeado
automaticamente pelo `PackageServiceProvider::mergeShieldConfig()` — basta o
arquivo existir na pasta `config/` do plugin; não precisa declarar `hasConfigFile`.

- `packageRegistered()` é o lugar para registrar o plugin no Filament:
  `Panel::configureUsing(fn (Panel $panel) => $panel->plugin(SeuPlugin::make()));`
  e também singletons/scoped do container.
- `packageBooted()` é o lugar para observers, gates, registros de policy,
  Livewire components, render hooks etc. — sempre guardando com
  `Package::isPluginInstalled(static::$name)` quando o plugin não é core.

## 4. Como registrar em bootstrap/providers.php

Os plugins NÃO são descobertos automaticamente (não estão em `vendor/`); eles
são mergeados no autoload pelo `wikimedia/composer-merge-plugin` (configurado no
`composer.json` raiz com `merge-plugin.include = ["plugins/*/*/composer.json"]`).

Portanto, **adicione o Service Provider manualmente** em `bootstrap/providers.php`:

```php
use Webkul\Product\ProductServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    CustomerPanelProvider::class,
    // ...
    ProductServiceProvider::class,
    // ...
];
```

E garanta que o autoload reconheça o namespace novo do plugin rodando
`composer dump-autoload` (o composer-merge-plugin mescla o PSR-4 do
`composer.json` do plugin).

O `composer.json` do plugin segue sempre o mesmo esqueleto:

```json
{
    "name": "webkul/{nome}",
    "description": "…",
    "authors": [
        { "name": "Aureus ERP", "email": "support@aureuserp.in" }
    ],
    "extra": {
        "laravel": {
            "providers": ["Webkul\\{Nome}\\{Nome}ServiceProvider"],
            "aliases": {}
        }
    },
    "autoload": {
        "psr-4": {
            "Webkul\\{Nome}\\": "src/",
            "Webkul\\{Nome}\\Database\\Factories\\": "database/factories/",
            "Webkul\\{Nome}\\Database\\Seeders\\": "database/seeders/"
        }
    },
    "autoload-dev": {
        "psr-4": { "Webkul\\{Nome}\\Tests\\": "tests/" }
    }
}
```

## 5. Migrations, Models e Filament Resources

### Migrations

- Ficam em `database/migrations/` com o padrão de nome
  `YYYY_MM_DD_HHMMSS_create_{prefixo}_..._table.php`.
- **Nome das tabelas usa prefixo do plugin**: ex. `products_products`,
  `products_categories`, `partners_partners`. Para o plugin de Pessoas seria
  algo como `pessoas_pessoas_fisicas` / `pessoas_pessoas_juridicas`.
- Cada migration é um anonymous class retornando `up()`/`down()`:
  ```php
  return new class extends Migration
  {
      public function up(): void { Schema::create('products_products', function (Blueprint $table) { ... }); }
      public function down(): void { Schema::dropIfExists('products_products'); }
  };
  ```
- Convenções de colunas vistas nos plugins:
  - `$table->id();`
  - `company_id` → `foreignId()->nullable()->constrained('companies')->nullOnDelete()` (multi-empresa).
  - `creator_id` → `foreignId()->nullable()->constrained('users')->nullOnDelete()`.
  - `$table->softDeletes();` e `$table->timestamps();`.
  - FKs para outras entidades com `constrained(...)`.
- Migrations de settings ficam em `database/settings/` (extendem
  `Spatie\LaravelSettings\Migrations\SettingsMigration`).

### Models

Em `src/Models/`, seguindo `Webkul\Product\Models\Product`:

```php
namespace Webkul\Product\Models;

class Product extends Model implements Sortable
{
    use BelongsToCompany;                    // multi-empresa (webkul/support)
    use HasChatter, HasCustomFields, HasFactory, HasLogActivity, SoftDeletes, SortableTrait;

    public const ACTIVITY_PLAN_PLUGIN = 'products';

    protected $table = 'products_products';

    public function getModelTitle(): string
    {
        return __('products::models/product.title');
    }

    protected $fillable = [...];

    protected $casts = [...];

    protected function getLogAttributeLabels(): array
    {
        return [...];                        // rótulos traduzidos para o log de atividades
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class); // Webkul\Security\Models\User
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class); // Webkul\Support\Models\Company
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->creator_id = Auth::id();
        });
    }

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }
}
```

- Use os traits do core: `BelongsToCompany` (escopo de empresa),
  `HasChatter`/`HasLogActivity` (webkul/chatter), `HasCustomFields`
  (webkul/fields), `SoftDeletes`, `SortableTrait`, `HasFactory`.
- Para models multi-empresa, o `CompanyScope` filtra automaticamente as
  consultas para as empresas ativas do usuário.
- **IMPORTANTE (decisão do projeto Perseu):** o plugin de Pessoas NÃO deve
  mexer no `boot()` de `Webkul\Security\Models\User` — a criação automática
  de Partner ao salvar User continua intacta (ver `CLAUDE.md`).

### Filament Resources

Em `src/Filament/Resources/{Recurso}Resource.php`, seguindo
`CategoryResource`:

- `protected static ?string $model = Recurso::class;`
- `protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-...';`
- `getNavigationGroup()` retorna o enum `Webkul\Support\Enums\NavigationGroup`
  (ex.: `NavigationGroup::Contact`) ou tradução direta.
- Labels sempre via tradução: `getModelLabel()`/`getPluralModelLabel()`
  retornando `__('{nome}::filament/resources/{recurso}.title')`.
- `form()`, `table()`, `infolist()` usam os componentes do Filament v4
  (`Filament\Schemas\Schema`, `Filament\Schemas\Components\Section/Group`,
  `Filament\Tables\Table`, etc.).
- Toda string visível usa tradução no formato
  `__('products::filament/resources/category.form.sections.general.fields.name')`.
- Subpáginas ficam em `Pages/` dentro da pasta do Resource e são registradas
  em `getPages()`:
  ```php
  public static function getPages(): array
  {
      return [
          'index'  => ListProducts::route('/'),
          'create' => CreateProduct::route('/create'),
          'edit'   => EditProduct::route('/{record}/edit'),
          'view'   => ViewProduct::route('/{record}'),
      ];
  }
  ```
- Relation managers ficam em `RelationManagers/` dentro da pasta do Resource.
- Clusters de configuração ficam em `src/Filament/Clusters/...` (ver
  `contacts/src/Filament/Clusters/Configurations.php`).

## 6. Como registrar permissões no Shield

O formato de chave é definido em
`plugins/webkul/plugin-manager/src/PermissionManager.php` e segue:

- **Resources:** `{acao}_plugin_entidade` — ex. `view_any_product_product`,
  `create_product_category`, `delete_security_user`.
- **Pages:** `page_plugin_nome` — ex. `page_support_manage_branding`.
- **Widgets:** `widget_plugin_nome`.

Cada plugin publica seu próprio `config/filament-shield.php`:

```php
use Webkul\Product\Filament\Resources\AttributeResource;
use Webkul\Product\Filament\Resources\CategoryResource;
use Webkul\Product\Filament\Resources\PackagingResource;
use Webkul\Product\Filament\Resources\PriceListResource;
use Webkul\Product\Filament\Resources\ProductResource;

$basic = ['view_any', 'view', 'create', 'update'];
$delete = ['delete', 'delete_any'];
$forceDelete = ['force_delete', 'force_delete_any'];
$restore = ['restore', 'restore_any'];
$reorder = ['reorder'];

return [
    'resources' => [
        'manage' => [
            CategoryResource::class  => [...$basic, ...$delete],
            AttributeResource::class => [...$basic, ...$delete, ...$restore, ...$forceDelete, ...$reorder],
            PackagingResource::class => [...$basic, ...$delete, ...$reorder],
            PriceListResource::class => [...$basic, ...$delete, ...$reorder],
            ProductResource::class   => [...$basic, ...$delete, ...$restore, ...$forceDelete, ...$reorder],
        ],
    ],
];
```

- `resources.manage` mapeia cada Resource para os métodos de permissão que
  devem ser gerados. Esse array é **mergeado** na config global
  (`config/filament-shield.php`) pelo `PackageServiceProvider::mergeShieldConfig()`.
- `resources.exclude` e `pages.exclude`/`widgets.exclude` removem entidades
  (ex.: clusters de configuração não geram permissão própria — ver
  `contacts/config/filament-shield.php`).
- As permissions são geradas pelo comando `shield:generate --all --option=permissions
  --panel=admin`, disparado automaticamente ao final de cada `{nome}:install`.
- `policies.generate = false` na config global: as **policies são mantidas
  manualmente** em `src/Policies/`. Cada policy verifica a chave exata gerada
  pelo Shield:

```php
class CategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_product_category');
    }

    public function view(User $user, Category $category): bool
    {
        return $user->can('view_product_category');
    }

    public function create(User $user): bool
    {
        return $user->can('create_product_category');
    }

    // update, delete, deleteAny, restore, forceDelete, reorder...
}
```

## 7. Comando de instalação (`artisan {nome}:install`)

O comando é criado automaticamente pelo `Package::hasInstallCommand()` do
`configureCustomPackage()`. A classe real é
`Webkul\PluginManager\Console\Commands\InstallCommand`, com assinatura
`{shortName}:install` (ex.: `products:install`).

Configuração dentro do `hasInstallCommand(fn (InstallCommand $command) => ...)`:

```php
$package->name(static::$name)
    ->hasMigrations([...])
    ->runsMigrations()
    ->hasSeeder('Webkul\\Product\\Database\\Seeders\\DatabaseSeeder')
    ->hasInstallCommand(function (InstallCommand $command) {
        $command
            ->runsMigrations()   // roda as migrations + settings declaradas
            ->runsSeeders();     // roda os seeders declarados
    })
    ->hasUninstallCommand(function (UninstallCommand $command) {
        $command->endWith(fn () => ...); // cleanup opcional ao desinstalar
    });
```

Fluxo do `InstallCommand::handle()`:

1. `startWith` (hook opcional).
2. Dependências (se declaradas e `installDependencies()`).
3. `publish()` de tags (config, views, migrations).
4. `runsMigrations()` → roda as migrations e settings pendentes via
   `artisan migrate --path=...`.
5. `runsSeeders()` → roda os seeders via `db:seed --class=...`.
6. `Package::updateOrCreate()` → marca o plugin como instalado/ativo na
   tabela `plugins`.
7. Sincroniza dependências na pivot `plugin_dependencies`.
8. `endWith` (hook opcional).
9. `regenerateAdminPanelPermissions()` → roda `shield:generate` para gerar
   as permissions deste plugin e sincroniza com a primeira role.
10. `Package::refreshPluginCaches()`.

> Para um plugin que NÃO precisa de migrations/seeders (ex.: contacts),
> basta `hasInstallCommand(function (InstallCommand $command) {})` — o
> comando ainda marca o plugin como instalado e regenera as permissões.

Fluxo de desinstalação (`UninstallCommand::handle()`): valida que não há
dependentes instalados, pede confirmação, faz `down()` de todas as migrations
(e settings) na ordem inversa, deleta o registro da tabela `plugins` e
refresca caches. O comando de desinstalação existe em `{nome}:uninstall`.

Para instalar manualmente:

```bash
php artisan products:install
```

Também é possível instalar pela UI: PluginResource (plugin-manager) executa
`{nome}:install --no-interaction` no backend.

## 8. Traduções em pt_BR desde a criação do plugin

O idioma canônico é o `en`; o projeto (Perseu) usa `pt_BR` como idioma padrão
(`APP_LOCALE=pt_BR` no `.env`). Idiomas suportados (`config/app.php` →
`supported_locales`): `en`, `ar`, `es`, `pt_BR`.

Para registrar traduções:

1. No Service Provider, chame `->hasTranslations()` no `configureCustomPackage()`
   (o `PackageServiceProvider` carrega `resources/lang/` com o namespace
   `{shortName}` — ex.: `products::`).
2. Crie os arquivos em `resources/lang/en/...` e espelhe em
   `resources/lang/pt_BR/...` desde o primeiro dia.
3. Padrão de estrutura e de uso:
   - `resources/lang/en/filament/resources/category.php` → usado via
     `__('products::filament/resources/category.table.columns.name')`.
   - `resources/lang/en/models/product.php` → usado via
     `__('products::models/product.title')`.
   - `resources/lang/en/enums/...` → labels de enums.
   - `resources/lang/pt_BR/...` → os mesmos arquivos, traduzidos para
     português do Brasil (mantendo a MESMA estrutura/chaves do `en`).
4. Crie os diretórios `ar` e `es` também (o checker de traduções exige que
   todos os locales suportados tenham os arquivos), ou pelo menos `en` +
   `pt_BR` antes de rodar o validador.

Validação:

```bash
php artisan translations:check
```

Esse comando (`FindMissingTranslations`) compara `en` (canônico) com os
demais locales: arquivos ausentes, chaves ausentes/sobresalentes, ordem das
chaves e estrutura dos arquivos. Plugins novos devem passar nessa checagem.

### Checklist de tradução para um recurso novo

1. Criar `resources/lang/en/filament/resources/{recurso}.php` com as chaves
   de form/table/infolist/actions usadas no Resource e nas subpáginas.
2. Copiar para `resources/lang/pt_BR/filament/resources/{recurso}.php` e
   traduzir os valores (mantendo estrutura e ordem idênticas).
3. Usar `__('{nome}::filament/resources/{recurso}....')` em todos os labels,
   títulos, placeholders, notificações.
4. Rodar `php artisan translations:check --locale=pt_BR --plugin={nome}` para
   confirmar que está consistente.

---

## Resumo do fluxo para criar um plugin novo

1. Criar `plugins/webkul/{nome}/` com `composer.json` (autoload PSR-4 +
   provider) e a estrutura de pastas da seção 1.
2. Criar `src/{Nome}ServiceProvider.php` estendendo `PackageServiceProvider`,
   declarando `$name`, migrations/settings/seeders, `hasTranslations()` e os
   comandos de install/uninstall.
3. Criar `src/{Nome}Plugin.php` implementando o contrato do Filament
   (seção 2) e registrá-lo em `packageRegistered()`.
4. Adicionar o provider em `bootstrap/providers.php` e rodar
   `composer dump-autoload`.
5. Criar migrations (tabelas com prefixo `{nome}_`), models (traits do core)
   e Filament Resources com labels traduzidos.
6. Criar as Policies em `src/Policies/` usando o formato
   `{acao}_{plugin}_{entidade}`.
7. Criar `config/filament-shield.php` listando os resources e os métodos de
   permissão.
8. Criar as traduções `en`/`pt_BR` (e `ar`/`es`) em `resources/lang/`.
9. Rodar `php artisan {nome}:install` (gera migrations, seeders e as
   permissões do Shield).
10. Validar com `php artisan translations:check`.

## 9. Auditoria + Lixeira (convenção para todo Model de cadastro de negócio)

Desde a criação do plugin `perseu/auditoria` (ver CLAUDE.md, seção
"Auditoria (log de atividade) + Lixeira completa"), todo Model NOVO de
cadastro de negócio (Pessoas, Comercial, ou qualquer plugin `perseu/*`
futuro) deve, por padrão:

1. Usar `Illuminate\Database\Eloquent\SoftDeletes`.
2. Usar `Perseu\Auditoria\Traits\LogsBusinessActivity` (`use
   LogsBusinessActivity;` no Model — não escrever
   `getActivitylogOptions()` manualmente, a não ser que precise de algo
   diferente do padrão do projeto).
3. Se o Model tem uma relação `BelongsToMany` de Endereço e/ou `HasMany`
   de Contato (ou dado análogo sem `SoftDeletes` próprio): usar
   `Perseu\Pessoas\Traits\CascadesRelatedDataOnForceDelete` (ou o mesmo
   padrão — hook em `forceDeleting`, NUNCA em `deleting`, pra não
   apagar dado relacionado ao simplesmente mover o registro pai pra
   lixeira) — sem isso, uma exclusão definitiva deixa Endereço/Contato
   órfãos no banco.
4. O Resource correspondente precisa ter página de Edit/View dedicada
   (`getPages()` com `'edit' => EditXxx::route(...)`, não o padrão
   `ManageRecords` de página única) — é isso que viabiliza a aba de
   Atividades (RelationManager exige uma página própria de
   registro). Um cadastro simples o bastante pra usar `ManageRecords`
   (ex.: uma tabela de tags/categorias) fica sem Lixeira/aba visual por
   limitação estrutural — ainda assim auditado (item 2 não depende de
   `SoftDeletes` nem de página de Edit), mas sem UI de restaurar/ver
   atividades. Reestruturar pra List+Edit só quando houver necessidade
   real de Lixeira/Atividades nesse cadastro específico, não
   preventivamente.
5. No `table()` do Resource: `Filament\Tables\Filters\TrashedFilter`
   em `->filters([...])`, `RestoreAction`/`ForceDeleteAction` em
   `->recordActions([...])`, e `RestoreBulkAction`/
   `ForceDeleteBulkAction`/`DeleteBulkAction` num `BulkActionGroup` em
   `->toolbarActions([...])` — mesmo padrão do AureusERP original (ver
   ex.: `SkillsRelationManager` do plugin `employees`).
6. `Rmsramos\Activitylog\RelationManagers\ActivitylogRelationManager::class`
   em `getRelations()` do Resource — a aba de Atividades. Não precisa
   de nenhuma policy/permissão extra no Resource: a visibilidade já é
   controlada centralmente por `Perseu\Auditoria\Policies\ActivityPolicy`
   (`view_any_auditoria_auditoria`), separada da permissão de ver/editar
   o próprio registro.
7. `config/filament-shield.php` do plugin: incluir `restore`/
   `restore_any`/`force_delete`/`force_delete_any` (junto do
   `$basic`/`$delete` já usuais) pro Resource em questão, e rodar
   `shield:generate` (ou reinstalar o plugin) pra sincronizar essas
   permissões com a role Admin.
