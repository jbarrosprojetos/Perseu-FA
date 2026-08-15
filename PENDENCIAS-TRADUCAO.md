# Pendências de Tradução (pt_BR)

> Gerado por varredura automatizada + conferência manual.
> App rodando com `APP_LOCALE=pt_BR`, `APP_FALLBACK_LOCALE=en`.

## 1. Labels de navegação (item 3)

### 1.1 Labels hardcoded em inglês (sem `__()` — não traduzíveis)

| Plugin | Arquivo | Método | Label |
| --- | --- | --- | --- |
| products | `src/Filament/Resources/PriceListResource.php` | `getNavigationLabel()` | `'Price Lists'` |
| website | `src/Filament/Admin/Pages/WebsiteDashboard.php` | `getNavigationLabel()` | `'Website'` |

### 1.2 Usando `__()` mas com a chave ausente no pt_BR do plugin (cai no fallback EN → mostra o texto inglês)

| Plugin | Arquivo | Método | Chave usada |
| --- | --- | --- | --- |
| partners | `src/Filament/Resources/IndustryResource.php` | `getNavigationLabel()` | `partners::filament/resources/industry.navigation.title` |
| partners | `src/Filament/Resources/TagResource.php` | `getNavigationLabel()` | `partners::filament/resources/tag.navigation.title` |
| support | `src/Filament/Resources/BankResource.php` | `getNavigationLabel()` | `support::filament/resources/bank.navigation.title` |
| support | `src/Filament/Resources/BankResource.php` | `getNavigationGroup()` | `support::filament/resources/bank.navigation.group` |

> Observação: essas 4 chaves também **não existem no `en`** do respectivo plugin — o label renderiza o próprio nome da chave (ex.: `partners::filament/resources/industry.navigation.title`) mesmo em inglês.

### 1.3 Usando `__()` com chave **sem namespace** (resolvida apenas em `lang/` do app, onde não existe → mostra o texto inglês)

| Plugin | Arquivo | Método | Chave usada |
| --- | --- | --- | --- |
| invoices | `src/Filament/Clusters/Settings/Pages/Products.php` | `getNavigationLabel()` | `Manage Products` |
| sales | `src/Filament/Clusters/Configuration/Resources/PackagingResource.php` | `getNavigationLabel()` | `Products` |
| sales | `src/Filament/Clusters/Configuration/Resources/PackagingResource.php` | `getNavigationGroup()` | `Packagings` |
| sales | `src/Filament/Clusters/Configuration/Resources/ActivityTypeResource.php` | `getModelLabel()` | `Activity Type` |
| sales | `src/Filament/Clusters/Configuration/Resources/ActivityTypeResource.php` | `getNavigationGroup()` | `Activities` |
| sales | `src/Filament/Clusters/Orders/Resources/QuotationResource/Pages/ManageInvoices.php` | `getNavigationLabel()` | `Invoices` |

### 1.4 Grupos de navegação hardcoded via propriedade (`$navigationGroup = '...'`)

Contornam o enum `NavigationGroup` (que é traduzido via `admin.navigation.*` em `lang/pt_BR/admin.php`) e renderizam o texto inglês no menu.

| Plugin | Arquivo | Valor |
| --- | --- | --- |
| manufacturing | `src/Filament/Clusters/Settings/Pages/ManagePlanning.php` | `'Manufacturing'` |
| manufacturing | `src/Filament/Clusters/Settings/Pages/ManageOperations.php` | `'Manufacturing'` |
| employees | `src/Filament/Clusters/Configurations/Resources/SkillTypeResource.php` | `'Employee'` |
| sales | `src/Filament/Clusters/Settings/Pages/ManagePricing.php` | `'Sales'` |
| sales | `src/Filament/Clusters/Settings/Pages/ManageProducts.php` | `'Sales'` |
| sales | `src/Filament/Clusters/Settings/Pages/ManageInvoice.php` | `'Sales'` |
| sales | `src/Filament/Clusters/Settings/Pages/ManageQuotationAndOrder.php` | `'Sales'` |
| inventories | `src/Filament/Clusters/Settings/Pages/ManageTraceability.php` | `'Inventory'` |
| inventories | `src/Filament/Clusters/Settings/Pages/ManageProducts.php` | `'Inventory'` |
| inventories | `src/Filament/Clusters/Settings/Pages/ManageOperations.php` | `'Inventory'` |
| inventories | `src/Filament/Clusters/Settings/Pages/ManageWarehouses.php` | `'Inventory'` |
| inventories | `src/Filament/Clusters/Settings/Pages/ManageLogistics.php` | `'Inventory'` |
| projects | `src/Filament/Clusters/Settings/Pages/ManageTime.php` | `'Project'` |
| projects | `src/Filament/Clusters/Settings/Pages/ManageTasks.php` | `'Project'` |
| website | `src/Filament/Admin/Clusters/Settings/Pages/ManageContacts.php` | `'Website'` |
| accounting | `src/Filament/Clusters/Settings/Pages/ManageTaxes.php` | `'Accounting'` |
| accounting | `src/Filament/Clusters/Settings/Pages/ManageCustomerInvoice.php` | `'Accounting'` |
| accounting | `src/Filament/Clusters/Settings/Pages/ManageDefaultAccounts.php` | `'Accounting'` |
| accounting | `src/Filament/Clusters/Settings/Pages/ManageProducts.php` | `'Accounting'` |
| purchases | `src/Filament/Admin/Clusters/Settings/Pages/ManageOrders.php` | `'Purchase'` |
| purchases | `src/Filament/Admin/Clusters/Settings/Pages/ManageProducts.php` | `'Purchase'` |
| invoices | `src/Filament/Clusters/Settings/Pages/Products.php` | `'Invoices'` |

### 1.5 Model labels hardcoded (afetam títulos de página/navegação)

| Plugin | Arquivo | Propriedade | Valor |
| --- | --- | --- | --- |
| time-off | `src/Filament/Clusters/Configurations/Resources/PublicHolidayResource.php` | `$modelLabel` | `'Public Holiday'` |
| employees | `src/Filament/Clusters/Reportings/Resources/EmployeeSkillResource.php` | `$pluralModelLabel` | `'Skills'` |

## 2. Campos de formulário (item 4)

### 2.1 Labels/títulos hardcoded (sem `__()`)

| Plugin | Arquivo | Componente | Texto |
| --- | --- | --- | --- |
| partners | `src/Filament/Resources/PartnerResource/Schemas/PartnerForm.php` | `Fieldset::make` | `Address`, `Sales`, `Others` |
| partners | `src/Filament/Resources/PartnerResource/Schemas/PartnerForm.php` | `placeholder` | `e.g. 29ABCDE1234F1Z5`, `e.g. CEO` |
| partners | `src/Filament/Resources/PartnerResource/Schemas/PartnerInfolist.php` | `Section::make` | `Sales`, `Others` |
| partners | `src/Filament/Resources/PartnerResource/Schemas/PartnerInfolist.php` | `Fieldset::make` | `Address` |
| accounts | `src/Filament/Resources/FiscalPositionResource.php` | `Tab::make` | `Tax Mapping`, `Account Mapping` |
| accounts | `src/Filament/Resources/TaxResource.php` | `description()` | `Define how this tax affects accounts for invoices and refunds.` |
| accounts | `src/Filament/Resources/TaxResource.php` | `Section::make` | `Invoice & Refund Distribution` |
| accounts | `src/Filament/Resources/TaxResource.php` | `Tab::make` | `Repartition Lines`, `Descriptions` |
| employees | `src/Filament/Resources/EmployeeResource.php` | `Fieldset::make` | `Approvers` |
| projects | `src/Filament/Clusters/Configurations/Resources/ActivityPlanResource.php` | `Section::make` | `General Information` |
| time-off | `src/Filament/Clusters/Management/Resources/AllocationResource.php` | `Fieldset::make` | `Validity Period` |
| purchases | `src/Filament/Admin/Clusters/Orders/Resources/OrderResource.php` | `hint()` | `Test` (provável resto de desenvolvimento) |
| security | `src/Filament/Resources/RoleResource.php` | `Tab::make` | `resources`, `pages`, `widgets` |
| website | `src/Filament/Admin/Clusters/Settings/Pages/ManageContacts.php` | `placeholder` | `support@example.com`, `+1234567890`, `username` |
| maintenance | `src/Filament/Clusters/Maintenance/Resources/MaintenanceRequestResource.php` | `placeholder` | `00:00` |
| manufacturing | `src/Filament/Clusters/Configurations/Resources/OperationResource.php` | `placeholder` | `60:00` |
| manufacturing | `src/Filament/Clusters/Configurations/Resources/WorkCenterResource.php` | `placeholder` | `00:00` |
| manufacturing | `src/Filament/Clusters/Operations/Resources/WorkOrderResource.php` | `placeholder` | `00:00` |

### 2.2 Usando `__()` com chave sem namespace (não existe em `lang/pt_BR.json`/`lang/pt_BR/*` → mostra o texto inglês)

| Plugin | Arquivo | Componente | Texto/chave |
| --- | --- | --- | --- |
| accounts | `src/Filament/Resources/CashRoundingResource.php` | `label()` | `Profit Account` (2×) |
| accounts | `src/Filament/Resources/CashRoundingResource.php` | `label()` | `Loss Account` (2×) |
| accounts | `src/Filament/Resources/FiscalPositionResource.php` | `label()` | `Foreign VAT` |
| accounts | `src/Filament/Resources/FiscalPositionResource.php` | `placeholder()` | `Name` |
| invoices | `src/Filament/Clusters/Settings/Pages/Products.php` | `label()` | `Unit of Measure` |
| invoices | `src/Filament/Clusters/Settings/Pages/Products.php` | `helperText()` | `Sell and purchase products in different units of measure` |
| recruitments | `src/Filament/Clusters/Applications/Resources/CandidateResource.php` | `label()` | `Status` (2×) |
| sales | `src/Filament/Clusters/Configuration/Resources/TagResource.php` | `placeholder()` | `Name` |
| sales | `src/Filament/Clusters/Configuration/Resources/TeamResource/Pages/ListTeams.php` | `Tab::make` | `All`, `Archived` |
| sales | `src/Filament/Clusters/Orders/Resources/QuotationResource.php` | `Section::make` | `Optional Products` (2×) |

### 2.3 Usando `__()` mas com a chave ausente no pt_BR do plugin

| Plugin | Arquivo | Componente | Chave usada |
| --- | --- | --- | --- |
| recruitments | `src/Filament/Clusters/Configurations/Resources/JobPositionResource.php` | `modalHeading()` | `recruitments::filament/clusters/configurations/resources/job-position.form.sections.employment-information.fields.company-modal-title` |

> Essa chave também não existe no `en` — o modal de criar empresa mostra o nome da chave.

### 2.4 Mensagens de validação / tooltips em inglês (hardcoded)

| Plugin | Arquivo | Tipo | Texto |
| --- | --- | --- | --- |
| support | `src/Filament/Resources/CompanyResource.php` | `validationMessages` | `Company name already exists. Please use a unique name.` |
| support | `src/Filament/Resources/CompanyResource/RelationManagers/BranchesRelationManager.php` | `validationMessages` | `Branch name already exists. Please use a unique name.` |
| support | `src/Filament/Resources/CompanyResource.php` | `hintIcon(tooltip:)` | `The Company ID is a unique identifier for your company.` |
| support | `src/Filament/Resources/CalendarResource.php` | `hintIcon(tooltip:)` | `Enable alternating two-week work schedule` |

### 2.5 Notas (namespace de vendor, fora dos plugins)

| Namespace | Chave | Observação |
| --- | --- | --- |
| filament-panels | `filament-panels::auth/pages/edit-profile.multi_factor_authentication.label` | Sem publicação de lang do vendor em `lang/vendor/filament-panels` — usada no `support/src/Filament/Pages/Profile.php`; renderiza EN até publicar. |
| filament-shield | `filament-shield::filament-shield.field.team`, `...field.team.placeholder` | Ausentes em `lang/vendor/filament-shield/pt_BR/filament-shield.php` (demais chaves `field.*` existem). |

## 3. Dados em inglês no banco (países e moedas) — documentado, NÃO alterado

Os nomes de países (`countries.name`, ex.: "Brazil") e moedas (`currencies.full_name`, ex.: "Brazilian real") são **dados semeados em inglês** no banco, exibidos diretamente em selects e colunas (`titleAttribute: 'name'` / `'full_name'`).

### 3.1 Países (`countries`)

- Total no banco: **250** registros, todos com `code` ISO-2 (ex.: `BR`).
- Pacote `laravel-lang/native-country-names` **instalado** (dependência de laravel-lang/lang). `CountryNames::get('pt_BR')` fornece nome localizado (`BR → "Brasil"`, `US → "Estados Unidos"`).
- **Porém**: o pacote só cobre **87/250** países da tabela (a base dele é agrupada por código de idioma, não país). Atualizar o banco com esse pacote deixaria 163 países em inglês — resultado **inconsistente**.
- Não há solução simples via esse pacote → **parcialmente resolvido** — apenas os itens de uso prático foram traduzidos, o restante da lista permanece em inglês por decisão do cliente.
- Traduzidos em `plugins/webkul/security/src/Data/countries.json` e no banco: `BR → "Brasil"`, `US → "Estados Unidos"`, `PY → "Paraguai"`, `UY → "Uruguai"`. Já estavam em português: `AR → "Argentina"`, `PT → "Portugal"`.

### 3.2 Moedas (`currencies`)

- Total no banco: **169** registros. Coluna com o ISO é `name` (ex.: `BRL`); `full_name` guarda o nome em inglês ("Brazilian real").
- Pacote `laravel-lang/native-currency-names` **instalado**. `CurrencyNames::get('pt_BR')` fornece `BRL → "Real brasileiro"`.
- **Porém**: o pacote só cobre **69/169** moedas da tabela (mesmo motivo do item 3.1).
- Não há solução simples via esse pacote → **parcialmente resolvido** — apenas os itens de uso prático foram traduzidos, o restante da lista permanece em inglês por decisão do cliente.
- Traduzidos em `plugins/webkul/security/src/Data/currencies.json` e no banco: `BRL → "Real brasileiro"`, `USD → "Dólar americano"`, `EUR → "Euro"` (já em português).

### 3.3 Formato de data ("jan 1, 2000" com aparência de mês em inglês)

- **Causa raiz investigada:** o locale do Carbon **já está em pt_BR** — o `ServiceProvider` do pacote `nesbot/carbon` escuta o evento `LocaleUpdated` e chama `Carbon::setLocale()` automaticamente quando o app muda de locale (o middleware `app/Http/Middleware/SetLocale.php` chama `App::setLocale()`). Não faltava chamada explícita.
- O que gerava "jan 1, 2000" é o **formato default do Filament** `M j, Y` (renderizado em JS no DatePicker e como `translatedFormat` em colunas de tabela). Com Carbon pt_BR, `M j, Y` → "jan 1, 2000" (jan = janeiro em pt_BR), mas com layout americano.
- **Correção aplicada:** defaults globais locale-aware no `AppServiceProvider` (`DatePicker`/`DateTimePicker`/`Table` usam `d/m/Y` e `d/m/Y H:i:s` quando o locale for `pt_BR`).

