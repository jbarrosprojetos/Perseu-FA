Preciso condicionar a exibição da barra de Debug (barryvdh/laravel-debugbar)
para aparecer SOMENTE quando o usuário autenticado tiver uma Role
(Função) atribuída especificamente sob o guard "sanctum" (via Spatie
Permission), independente do valor de APP_DEBUG no .env.

## Investigação necessária primeiro

1. Confirme como o pacote barryvdh/laravel-debugbar está configurado
   neste projeto (config/debugbar.php, se publicado, ou o padrão do
   pacote) e como controlar sua exibição programaticamente em runtime
   (ex: \Debugbar::enable() / \Debugbar::disable(), ou
   config(['debugbar.enabled' => bool]) antes do boot do pacote).
2. Confirme como verificar, para o usuário autenticado, se ele possui
   QUALQUER role com guard_name = 'sanctum' (via Spatie Permission -
   Auth::user()->roles() filtrando por guard_name, já que o model
   User usa $guard_name = ['web', 'sanctum'] conforme documentado na
   AUDITORIA-ESTRUTURA.md).

## Implementação

Crie um Middleware (ex: app/Http/Middleware/ControlDebugbarVisibility.php,
ou dentro de um dos plugins Security/Support se fizer mais sentido
arquiteturalmente - use seu critério, mas prefira não modificar o core
do AureusERP se puder resolver via um plugin próprio) que, a cada
requisição autenticada:
- Se o usuário tiver ao menos uma role com guard_name='sanctum':
  habilita a Debugbar
- Caso contrário: desabilita a Debugbar

Registre esse middleware no painel admin (AdminPanelProvider), de
forma que rode após a autenticação.

## Documentação (registrar para o manual futuro do sistema)

Adicione ao CLAUDE.md uma nova seção "## Controle de visibilidade da
Debugbar via Role com Guard Sanctum", explicando:
- O mecanismo: a barra de Debug (ferramenta de desenvolvimento, mostra
  queries SQL, models carregados, etc.) fica condicionada a possuir
  uma Role com guard_name='sanctum', não ao ambiente (APP_DEBUG)
- Como criar/atribuir essa role para um usuário que precise ver a
  barra (passo a passo simples: criar uma Função em Configurações >
  Funções, com Guard = Sanctum, e atribuí-la ao usuário)
- Por que essa abordagem foi escolhida em vez de só usar APP_DEBUG
  (permite controle granular por usuário, útil inclusive em produção
  para diagnosticar problemas pontuais sem expor a barra a todo mundo)

## Validação

1. Rode ddev artisan optimize:clear
2. Teste via tinker: usuário SEM role sanctum -> Debugbar deve estar
   desabilitada; usuário COM role sanctum -> habilitada
3. Confirme visualmente logando com os dois tipos de usuário

Me relate o resultado e onde exatamente o middleware foi registrado.
