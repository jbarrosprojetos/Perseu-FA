Preciso corrigir 4 problemas na tela de cadastro/edição de Empresa
(CompanyResource, plugin support):

## 1. Label "City" em inglês (correção direta)
Trocar por __() com chave traduzida ("Cidade"), seguindo o mesmo
padrão já usado nos outros campos desse formulário.

## 2. "Número de telefone" aparecendo duplicado
Investigue: são dois campos diferentes (ex: telefone fixo e celular/
mobile) usando a MESMA chave de tradução por engano? Se sim, corrija
para que cada campo tenha o label correto e distinto (ex: "Telefone" e
"Celular", ou "Telefone" e "WhatsApp", conforme o que os campos
realmente representam no banco).

## 3. Nomes de países em inglês ("Brazil" em vez de "Brasil")
Isso não é problema de rótulo de tela, é DADO no banco (tabela
countries, campo name, provavelmente semeado em inglês). Antes de
corrigir, verifique se o pacote laravel-lang/native-country-names
já está instalado no projeto (foi instalado como dependência de
laravel-lang/lang anteriormente) — esse pacote pode já fornecer nomes
de países traduzidos, o que evitaria ter que editar manualmente 250
registros. Se existir um caminho para usar esse pacote (ex: um
comando/seeder para popular nomes localizados, ou uma tradução via
código do país + pacote de nomes nativos), use-o. Se não houver
solução simples via esse pacote, apenas documente a situação em
PENDENCIAS-TRADUCAO.md sem alterar os 250 registros manualmente.

## 4. Nome da moeda em inglês ("Brazilian real" em vez de "Real
brasileiro")
Mesma lógica do item 3: investigue se laravel-lang/native-currency-names
(também instalado como dependência) resolve isso. Se sim, aplique. Se
não, documente em PENDENCIAS-TRADUCAO.md.

## 5. Data exibida com mês em inglês ("jan 1, 2000" em vez de formato
brasileiro)
Investigue se o locale do Carbon (usado internamente pelo Laravel para
formatar datas) está sendo definido como pt_BR junto com o APP_LOCALE,
ou se falta uma chamada explícita (ex: Carbon::setLocale() em algum
Service Provider, ou configuração em config/app.php). Corrija a causa
raiz para que datas apareçam no formato brasileiro (ex: "01/01/2000"
ou "1 de janeiro de 2000", conforme o padrão já usado em outros
DatePickers do sistema, se houver).

Para os itens que corrigir diretamente (1, 2, e 3/4/5 se houver solução
via pacote), rode ao final ddev artisan optimize:clear e liste tudo
que foi alterado.
