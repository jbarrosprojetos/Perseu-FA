A correção anterior (SubNavigationPosition::Top) resultou em algo
DIFERENTE do que precisamos: criou "abas em pílula" ABAIXO do
cabeçalho da página (sub-navigation tabs), mas o comportamento
correto - confirmado visualmente comparando com o Cluster "Inventory"
(Estoque) do plugin webkul/inventories - é os itens aparecerem DENTRO
da própria barra superior colorida (topbar), na mesma linha do nome
do módulo ativo (ex: "Estoque | Visão geral | Operações | Produtos |
Relatórios | Configurações"), não como um componente separado abaixo.

## Reverta primeiro

Reverta a adição de $subNavigationPosition em PessoasCluster e
ComercialCluster (volte ao estado antes dessa mudança específica) -
isso não é o mecanismo certo para o resultado desejado.

## Investigue o mecanismo real

1. Confirme se o AdminPanelProvider.php tem ->topNavigation()
   habilitado no painel (isso já foi mencionado na
   AUDITORIA-ESTRUTURA.md como configuração existente - confirme).
2. Quando topNavigation() está ativo, como o Filament decide "quais
   itens aparecem na barra superior junto com o Cluster ativo"? Isso
   é automático (baseado nos Resources que pertencem ao mesmo
   Cluster/NavigationGroup), ou existe alguma configuração adicional
   necessária?
3. Compare EXATAMENTE a estrutura de arquivos e classes de
   plugins/webkul/inventories/src/Filament/Clusters/Inventory (ou
   nome equivalente) com plugins/perseu/pessoas/src/Filament/Clusters/
   PessoasCluster - existe alguma diferença de registro, herança, ou
   propriedade que explique por que um aparece "achatado" na topbar e
   o outro não?
4. Also investigue Webkul\Project\Filament\Clusters\Project (o
   Cluster do plugin de tarefas) - ele também aparece corretamente
   "achatado" na topbar (confirmado nas imagens anteriores desta
   sessão) - use como segunda referência de comparação.

Depois de identificar a causa raiz exata, aplique a correção correta
em PessoasCluster e ComercialCluster para que Categorias/Pessoas
Físicas/Pessoas Jurídicas (e Projetos/Situações/Tipos de Projeto)
apareçam DENTRO da topbar colorida, na mesma linha do nome do
Cluster - exatamente como Estoque e Projetos aparecem.

Ao final, rode ddev artisan optimize:clear e ddev artisan filament:assets.
Confirme via HTML renderizado a estrutura final. Atualize CLAUDE.md
removendo a orientação anterior (SubNavigationPosition::Top estava
incorreta para este objetivo) e documentando o mecanismo correto
descoberto agora.
