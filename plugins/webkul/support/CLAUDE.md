# Plugin `webkul/support`

> Convenções e decisões específicas deste plugin. Para o que vale para
> o projeto inteiro (convenção de Model de cadastro de negócio, regras
> de nomenclatura do sistema, comandos DDEV, etc.), veja o `CLAUDE.md`
> da raiz. Para o histórico narrado (o "porquê" de uma decisão antiga),
> veja a seção "Ver também" no final deste arquivo.

Plugin CORE do AureusERP (`->isCore()`, fora do grafo de
`plugin_dependencies`) — mas com customizações reais do Perseu por
cima: Company/Branch localizados pro padrão brasileiro, Branding, o
enum `NavigationGroup` (usado por TODOS os plugins `perseu/*` para
declarar sua entrada na topbar), e o suporte a idioma/RTL.

## Distinção entre Favicon e Logo (Branding)

`Webkul\Support\Settings\BrandSettings` — "favicon" representa a
identidade do PRODUTO Perseu (o software em si) e é usado em lugares
que remetem ao "sistema" (ex: página de Ajuda). "light_logo"/
"dark_logo" representam a identidade da EMPRESA CLIENTE que usa o
sistema (ex: topbar). Ao adicionar imagens de marca em novas telas,
considere qual das duas identidades faz sentido em cada contexto.

## `CompanyResource` duplicado (`support` vs. `security`)

`Filament\Resources\CompanyResource` existe em DOIS plugins:
`plugins/webkul/support/src/Filament/Resources/CompanyResource.php` E
`plugins/webkul/security/src/Filament/Resources/CompanyResource.php`.
**O de `support` é o que efetivamente serve as rotas** — o de
`security` tem `shouldRegisterNavigation = false`. Antes de editar
qualquer um dos dois, confirme com `route:list` ou busca por nome de
classe qual versão está realmente ativa, para não editar a versão
errada (regra geral, vale para qualquer Resource duplicado que
apareça em outro plugin no futuro).

## Localização do cadastro de Empresa (Company) pro padrão brasileiro

`Webkul\Support\Models\Company` (Configurações → Empresas) — Model
CORE do AureusERP, integrado a multi-tenancy/segurança — foi
LOCALIZADO pro padrão brasileiro de Pessoa Jurídica (CNPJ, busca
automática via BrasilAPI, endereço com CEP/UF), decisão consciente de
adaptar os campos em vez de substituir o Model por `PessoaJuridica`
(mexer na integração de segurança/multi-empresa seria arriscado demais
pro ganho). Filial (`Branch`) NÃO é um Model separado — é a MESMA
tabela `companies`, auto-referencial via `parent_id` — por isso
`BranchesRelationManager` recebeu exatamente o mesmo tratamento, com o
próprio `form()`/`infolist()` (não herda do `CompanyResource`, é uma
classe irmã que duplica a estrutura desde a origem do AureusERP).

Pontos que exigiram levantamento cuidadoso antes de mexer (detalhamento
completo no histórico, ver "Ver também"):
- `registration_number` e `company_id` (coluna própria, string única —
  NÃO a PK numérica) são usados internamente (`Company::boot()` sincroniza
  pro Partner, API pública, seeders/factories) — campos ESCONDIDOS do
  formulário (`->hidden()`), NÃO removidos.
- `tax_id` foi reaproveitado como CNPJ (em vez de criar uma coluna
  nova) — já tinha `unique()->nullable()` e já sincronizava pro
  Partner; nenhum PDF/e-mail do sistema exibe `tax_id` hoje.
- A COLUNA `name` nunca foi renomeada (só o LABEL do formulário virou
  "Razão Social") — ela aparece no `company-switcher.blade.php` e em
  seis templates de impressão/PDF diferentes via `$record->company->name`.
- Endereço nos PDFs vem de `Company->partner->{campo}`, não de
  `Company->{campo}` diretamente — `Company::boot()` sincroniza
  `street1`/`street2`/`city`/`zip`/`state_id`/`country_id` pro Partner
  vinculado. `bairro`/`numero` (as duas colunas genuinamente novas)
  NÃO entram nesse sync ainda (nenhum consumidor precisa deles) —
  **estender `Company::boot()` quando a emissão de NF-e for
  implementada** (ver "Pendências" abaixo).
- Nenhuma Policy/Guard/scope de multi-tenancy depende do VALOR desses
  campos, só do `id` numérico.
- **Bug pré-existente do AureusERP, não corrigido**: `Company` NUNCA
  teve uma relação/accessor `address` (o infolist de
  `BranchesRelationManager` foi corrigido pra usar `street1`/`city`/
  etc. diretamente, mas um bug idêntico persiste em
  `plugins/webkul/sales/resources/views/sales/quotation.blade.php`,
  `$record->company->address`, sempre `null` — fora do escopo dos
  plugins `perseu/*`, não corrigido).
- **Achado que exigiu atenção do usuário, não corrigido
  silenciosamente**: o registro real "Fa Marcenaria" (única Empresa
  cadastrada) tinha `tax_id = "Inscrição"` — claramente um valor de
  teste/placeholder. Como o campo agora tem `->rule(new CnpjValido())`,
  a PRÓXIMA tentativa de salvar esse registro falha a validação até o
  CNPJ real ser digitado ali (ver "Pendências" abaixo).

### Reaproveitamento da lógica de CNPJ — generalização mínima

`Perseu\Pessoas\Support\BrasilApiCnpjLookup::fill()` ganhou um 4º
parâmetro opcional `string $razaoSocialField = 'razao_social'`
(default preserva 100% o comportamento pra quem já chama sem
informá-lo — ver `plugins/perseu/pessoas/CLAUDE.md`). Nova classe
`Webkul\Support\Support\CompanyCnpjLookup::fillEndereco()` reaproveita
`BrasilApiCnpjLookup::buscar()`/`enderecoFrom()` só pra fazer o
mapeamento Company-específico (`logradouro`→`street1`,
`complemento`→`street2`, `municipio`→`city`, `cep`→`zip`, `bairro`/
`numero` diretos, `uf`→`state_id` via
`State::where('code', $uf)->whereHas('country', code BR)`). Ficou aqui
(não em `perseu/pessoas`) pra não misturar conhecimento do schema
legado do AureusERP dentro do plugin de Pessoas.

`Perseu\Pessoas\Enums\RegimeTributario`/`IndicadorContribuinteIcms` e o
cast correspondente em `Company::$casts` foram REUTILIZADOS
diretamente de `perseu/pessoas`, não duplicados — seguro porque
`webkul/support->isCore()` está fora do grafo de
`plugin_dependencies`, então importar uma classe de `perseu/pessoas`
não introduz risco de dependência circular (mesma preocupação que
motivou a Lixeira Central em `perseu/auditoria` não usar uma `VIEW` de
banco). `SituacaoCadastralBadge` foi DUPLICADA como classe própria
aqui em vez de reaproveitada da Resource de Pessoa Jurídica — só 15
linhas, sem estado, não fazia sentido puxar uma classe de renderização
específica de outro plugin.

### Migration

`2026_08_30_100000_add_brazilian_fields_to_companies_table` adiciona:
`nome_fantasia`, `cnae`, `cnae_descricao`, `regime_tributario`,
`porte`, `descricao_porte`, `situacao_cadastral`,
`descricao_situacao_cadastral`, `indicador_contribuinte_icms`,
`bairro`, `numero` — todas `nullable()`. REAPROVEITADAS sem migration:
`name`, `tax_id`, `founded_date`, `street1`/`street2`/`city`/`zip`,
`state_id`/`country_id`.

## Idioma / RTL — implementação (decisão de negócio no `CLAUDE.md` da raiz)

`Webkul\Support\Traits\HasRtlSupport` e o language-switcher da topbar
(`resources/views/filament/components/language-switcher.blade.php`)
implementam o suporte a múltiplos idiomas descrito na seção "Idioma"
do `CLAUDE.md` da raiz — leia aquela seção para a decisão de negócio
(só `pt_BR`/`en`, `es`/`ar` removidos); este plugin só contém o
MECANISMO (lê `config('app.supported_locales')` como fonte única).

## `Webkul\Support\Enums\NavigationGroup`

Enum consumido por TODO plugin `perseu/*` (e outros customizados) para
declarar `getNavigationGroup()` — ver "Navegação: Cluster vs. grupo
achatado" no `CLAUDE.md` da raiz para o mecanismo completo de como um
novo caso desse enum vira um dropdown na topbar.

## Pendências

- **Company/Branch e NF-e**: `bairro`/`numero` de Company não são
  sincronizados para o Partner vinculado hoje (nenhum consumidor
  precisa ainda) — estender `Company::boot()` quando a emissão de NF-e
  for implementada.
- **CNPJ de teste no registro real de Company**: o registro real tem
  `tax_id` de teste/placeholder — precisa do CNPJ real da F.A.
  Marcenaria na próxima edição (a validação `CnpjValido` vai bloquear
  até lá).

## Ver também (histórico narrado, `HISTORICO-DESENVOLVIMENTO.md`)

- "Localização do cadastro de Empresa (Company) pro padrão brasileiro
  — Empresa e Filiais" (30/08/2026)
