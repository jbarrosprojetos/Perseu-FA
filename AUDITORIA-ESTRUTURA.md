# Auditoria de Estrutura — AureusERP / Perseu

Relatório de auditoria da estrutura do projeto, sem alterações de código.
Baseado na versão atual do repositório (branch `master`).

---

## 1. Controle de acesso

### Painéis (Filament)

| Painel | Provider | id | Path | Guard | Observações |
| --- | --- | --- | --- | --- | --- |
| Admin | `app/Providers/Filament/AdminPanelProvider.php` | `admin` | `/admin` | `web` (padrão) | `->default()`, login, password reset, e-mail verification, profile, MFA (app authenticator), `FilamentShieldPlugin` configurado, `topNavigation`, notificações em banco |
| Customer | `app/Providers/Filament/CustomerPanelProvider.php` | `customer` | `/` | `customer` | `->authGuard('customer')`, sem Shield, sem dark mode, home na raiz |

- Ambos os painéis aplicam os middlewares `SetLocale` e `ApplyBrandSettings`.
- O Shield (geração/gestão de permissões) está **somente no painel admin**.
- O painel admin usa `authMiddleware([Authenticate::class])`, que respeita o guard `web`.

### Guards e providers de autenticação (`config/auth.php`)

- **Guard `web`** → provider `users` → `Webkul\Security\Models\User`.
- **Guard `customer`** → provider `customers` → `Webkul\Website\Models\Partner`.
- Password brokers: `users` e `customers` (tabela `password_reset_tokens`).
- O provider usa `env('AUTH_MODEL')`; o `App\Models\User` (base) não é usado diretamente como provider, e sim a classe estendida `Webkul\Security\Models\User`.

### Acesso a painel (FilamentUser)

- `Webkul\Security\Models\User` implementa `FilamentUser` e o método `canAccessPanel()` retorna apenas `is_active` (controle de acesso por status do usuário).
- O usuário customer é o `Webkul\Website\Models\Partner` (com `is_active`, `password` etc.).

### Filament Shield — geração de permissões

- Config global: `config/filament-shield.php`.
  - `auth_provider_model` = `Webkul\Security\Models\User`.
  - `super_admin` desabilitado; `panel_user` habilitado com nome **"Admin"**.
  - Formato de chaves: `lower_snake`, separador `_`, geração automática ligada.
  - `policies.generate = false` (as policies são mantidas manualmente em cada plugin, em `plugins/*/src/Policies`).
  - Abas do RoleResource: pages, widgets, resources (custom_permissions desligada).
- Cada plugin publica seu próprio `config/filament-shield.php` (ex.: `plugins/webkul/security/config/filament-shield.php`), listando em `resources.manage` os métodos e em `resources.exclude` o que não gera permissão.
- **Formato das chaves** definido por `Webkul\PluginManager\PermissionManager` (registrado no `PluginManagerServiceProvider`):
  - **Resources**: `{acao}_plugin_entidade` — ex.: `view_any_security_user`, `create_security_company`, `delete_security_user`.
  - **Pages**: `page_plugin_nome` — ex.: `page_support_manage_branding`.
  - **Widgets**: `widget_plugin_nome`.
- Páginas customizadas usam `HasPageShield` (ex.: `ManageBranding`) e expõem `getPagePermission()`.
- Roles e permissões: Spatie Permission com o model `Webkul\Security\Models\Role` (estende `Spatie\Permission\Models\Role`), que **protege roles de sistema** (`admin`, `super_admin`, `Admin`) contra edição de nome/guard e exclusão.
- Policies por plugin respeitam as chaves geradas (ex.: `plugins/webkul/website/src/Policies/*`).

---

## 2. Estrutura de cadastro de usuários

### Model base — `App\Models\User` (`app/Models/User.php`)

- Extende `Illuminate\Foundation\Auth\User`.
- `fillable`: `name`, `email`, `password`.
- `casts`: `email_verified_at` (datetime), `password` (hashed).
- Usa `HasApiTokens` (Sanctum), `HasFactory`, `Notifiable`.

### Model efetivo — `Webkul\Security\Models\User` (`plugins/webkul/security/src/Models/User.php`)

- **Estende** `App\Models\User` e implementa `FilamentUser`, `HasAppAuthentication`, `HasAppAuthenticationRecovery`, `HasEmailAuthentication` (MFA do Filament).
- Traits: `HasOwnershipScope`, `HasRoles` (Spatie), `InteractsWithAppAuthentication*`, `InteractsWithEmailAuthentication`, `SoftDeletes`.
- `$guard_name = ['web', 'sanctum']`.
- Campos adicionais no `$fillable`: `partner_id`, `language`, `creator_id`, `is_active`, `default_company_id`, `resource_permission`, `is_default`.
- Casts: `default_company_id` (int), `resource_permission` (enum `PermissionType`), `is_default`/`is_active` (bool).

### Tabela `users` (colunas resultantes)

- `id`, `is_default` (bool), `name`, `email` (único), `email_verified_at`, `language`, `is_active` (bool), `password`, `resource_permission` (enum: `group|individual|global`, default `individual`), `remember_token`, `creator_id` (FK users), `partner_id` (FK `partners_partners`), `default_company_id` (FK `companies`), `softDeletes`, `timestamps`, além das colunas de MFA (migração `2026_01_23_074142`).
- Migrações: `database/migrations/0001_01_01_000000_create_users_table.php`, `database/migrations/2024_11_26_053234_add_resource_permission_column_to_users_table.php` e as de `plugins/webkul/security/database/migrations/`.

### Relacionamentos do User

- **Roles**: trait `HasRoles` do Spatie Permission → pivot `model_has_roles` (guard `web`). A UI (UserResource) exige pelo menos uma role.
- **Partner**: `partner()` — `belongsTo(Partner::class, 'partner_id')` (sem `CompanyScope`). O `boot()` do model cria/atualiza o Partner automaticamente ao salvar o usuário (`handlePartnerCreation`/`handlePartnerUpdation`), com `sub_type = 'partner'`; avatar vem de `partner.avatar`.
- **Empresas**: `companies()` (hasMany — empresas criadas), `allowedCompanies()` (belongsToMany via pivot `user_allowed_companies`), `defaultCompany()` (belongsTo `default_company_id`).
- **Teams**: `teams()` (belongsToMany via pivot `user_team`).
- **Employee/Department**: `employee()` (hasOne), `departments()` (hasMany).

### Cadastro de usuário (UserResource — `plugins/webkul/security/src/Filament/Resources/UserResource.php`)

- Formulário: `name`, `email`, `password` (+ confirmação), **roles** (múltipla, obrigatória), `resource_permission` (group/individual/global), `teams`, avatar (via partner), `language`, `is_active`, `allowed_companies`, `default_company_id`.
- Proteções: primeiro usuário precisa ser admin; não permite remover o último admin; não permite apagar usuário `is_default` ou a si mesmo.
- Consultas escopadas por ownership (`->ownership()`).
- Configurações: `UserSettings` (`general.default_role_id`, `general.default_company_id`, `enable_user_invitation`, `enable_reset_password`).

---

## 3. Estrutura de Empresas (multi-empresa)

### Model — `Webkul\Support\Models\Company` (`plugins/webkul/support/src/Models/Company.php`)

- Traits: `HasChatter`, `HasCustomFields`, `HasFactory`, `HasOwnershipScope`, `RestrictToAllowedCompanies`, `SoftDeletes`, `SortableTrait`.

### Tabela `companies` (migração `2024_12_10_092657_create_companies_table.php` + alterações)

- `id`
- `parent_id` (FK `companies` — hierarquia **empresa / filial**; métodos `parent()`, `branches()`, `isParent()`, `isBranch()`)
- `currency_id` (FK `currencies`)
- `creator_id` (FK `users`)
- `sort` (ordenável)
- `name` (not null, índice único)
- `company_id` (string, única)
- `tax_id` (string, única, nullable)
- `registration_number` (nullable)
- `email`, `phone`, `mobile`, `website`
- `color`
- `is_active` (bool, default true)
- `founded_date`
- Endereço (migrações `2025_04_04`): `street1`, `street2`, `city`, `zip`, `state_id`, `country_id`
- `logo` (via alterações)
- `partner_id` (migração `2025_01_07_125015`)
- `softDeletes`, `timestamps`

### Como funciona o multi-empresa

- Todo registro de negócio carrega `company_id`; o **`CompanyScope`** (`plugins/webkul/support/src/Models/Scopes/CompanyScope.php`) filtra automaticamente as consultas para as empresas ativas do usuário (`whereIn(company_id, activeIds)` **ou** `company_id null`), exceto em console/testes e para quem tem o gate `bypass_company_scope`.
- **`CompanyContext`** (`plugins/webkul/support/src/Services/CompanyContext.php`): resolve as empresas permitidas do usuário (`allowedCompanies`), a empresa padrão (`default_company_id`) e as empresas ativas da sessão (`active_company_ids`). `seesAllCompanies()` = quem tem o gate de bypass.
- Usuário ↔ empresas: pivot **`user_allowed_companies`** (`user_id`, `company_id`), relação `allowedCompanies()`; e `default_company_id` no usuário.
- Ao criar/atualizar uma Company, um **Partner** (`sub_type = 'company'`) é criado/atualizado em sincronia (inclui endereço, e-mail, tax_id etc.).
- Gestão via `CompanyResource` do plugin security (permite `reorder`, soft delete/restore/force delete) + seletor de empresa `company-switcher` no painel.

---

## 4. Branding (logo, cores, favicon)

### Onde ficam as configurações

- **Settings class**: `Webkul\Support\Settings\BrandSettings` (grupo `branding`, pacote Spatie Laravel Settings).
  - Campos: `primary_color`, `gray_color`, `danger_color`, `info_color`, `success_color`, `warning_color`, `light_logo`, `dark_logo`, `favicon`, `logo_height`.
- **Migration das settings**: `plugins/webkul/support/database/settings/2026_06_12_000001_create_brand_settings.php`.
  - Defaults: cores Blue/Zinc/Red/Blue/Green/Amber; `light_logo = images/logo.svg`; `dark_logo = images/logo.svg`; `favicon = images/favicon.ico`; `logo_height = 2rem`.
- **Página de gestão**: `plugins/webkul/support/src/Filament/Clusters/Settings/Pages/ManageBranding.php`.
  - `SettingsPage` no cluster `Settings`, com `HasPageShield` e permissão `page_support_manage_branding`.
  - Uploads (`FileUpload`, disk `public`, diretório `branding`): `light_logo`, `dark_logo`, `favicon`; campo `logo_height`; seis `ColorPicker` para as cores.

### Como o branding é aplicado

- Middleware **`App\Http\Middleware\ApplyBrandSettings`** (`app/Http/Middleware/ApplyBrandSettings.php`), registrado nos dois painéis (admin e customer):
  - Lê `BrandSettings` e registra as cores via `FilamentColor::register` (gera paletas OKLCH a partir de um hex âncora).
  - Aplica `brandLogo` (light), `darkModeBrandLogo`, `favicon` e `logo_height` dinamicamente no painel.
- **Fallback estático** nos providers dos painéis:
  - `AdminPanelProvider` e `CustomerPanelProvider`: `->favicon(asset('images/favicon.ico'))`, `->brandLogo(asset('images/logo.svg'))`, `->brandLogoHeight('2rem')` e cor primária `Color::Blue`.
- Arquivos padrão: `public/images/favicon.ico`, `public/images/logo.svg`, `public/images/logo-full-light.svg`, `public/images/logo-full-dark.svg`.

---

## Resumo rápido (mapa de arquivos)

| Assunto | Arquivos principais |
| --- | --- |
| Painel admin | `app/Providers/Filament/AdminPanelProvider.php` |
| Painel customer | `app/Providers/Filament/CustomerPanelProvider.php` |
| Guards/providers | `config/auth.php` |
| Config Shield global | `config/filament-shield.php` |
| Formato de permissões | `plugins/webkul/plugin-manager/src/PermissionManager.php` |
| Roles | `plugins/webkul/security/src/Models/Role.php` + Shield `RoleResource` |
| Model User base | `app/Models/User.php` |
| Model User efetivo | `plugins/webkul/security/src/Models/User.php` |
| Cadastro de usuário | `plugins/webkul/security/src/Filament/Resources/UserResource.php` |
| Model Company | `plugins/webkul/support/src/Models/Company.php` |
| Scope multi-empresa | `plugins/webkul/support/src/Models/Scopes/CompanyScope.php`, `AllowedCompanyScope.php`, `plugins/webkul/support/src/Services/CompanyContext.php` |
| Branding (settings) | `plugins/webkul/support/src/Settings/BrandSettings.php` + migration `.../create_brand_settings.php` |
| Branding (UI) | `plugins/webkul/support/src/Filament/Clusters/Settings/Pages/ManageBranding.php` |
| Branding (aplicação) | `app/Http/Middleware/ApplyBrandSettings.php` |
