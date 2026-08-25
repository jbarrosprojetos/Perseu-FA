Comparação visual confirmada: o Cluster "Project" (plugin
webkul/projects) exibe seus itens internos (Projetos, Tarefas,
Configurações) como ABAS HORIZONTAIS na topbar, seguindo o nome do
módulo ativo. Já nossos Clusters PessoasCluster e ComercialCluster
exibem os itens internos (Categorias, Pessoas Físicas, Pessoas
Jurídicas) como um MENU LATERAL VERTICAL, dentro da página, em vez de
horizontal na topbar.

Investigue:
1. Qual configuração do Cluster do Filament controla esse
   comportamento (navegação horizontal/tabs vs. sidebar vertical)?
   Procure por algo relacionado a propriedades como
   $clusterNavigationTabs, $navigationStyle, ou uma interface/trait
   específica do Cluster.
2. Compare a classe Webkul\Project\Filament\Clusters\Project (ou
   equivalente) com nosso PessoasCluster/ComercialCluster - qual
   propriedade/método está presente em um e ausente no outro?
3. Confirme também comparando com o Cluster de "Configurações"
   (Settings, em plugins/webkul/security ou support) - lembre-se que
   ele TAMBÉM exibe seus itens (Funções, Empresas, Equipes, Usuários)
   como abas horizontais na topbar, então deve compartilhar a mesma
   configuração que falta em nossos Clusters.

Depois de identificar a causa exata, aplique a correção em
PessoasCluster e ComercialCluster para que sigam o mesmo padrão
horizontal (Nome do módulo -> Item1, Item2, Item3... na topbar, não
menu lateral).

Ao final, rode ddev artisan optimize:clear e ddev artisan filament:assets.
Confirme via HTML renderizado que Categorias/Pessoas Físicas/Pessoas
Jurídicas aparecem como itens horizontais na topbar (não mais menu
lateral), e teste o mesmo em Comercial.

Documente essa correção no CLAUDE.md como uma regra a seguir em
QUALQUER Cluster futuro do Perseu.
