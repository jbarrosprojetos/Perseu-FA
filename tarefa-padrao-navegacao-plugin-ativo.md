Releia os resultados das tarefas anteriores (tarefa-investigar-
comportamento-topbar.md e tarefa-investigar-fi-dropdown.md) antes de
começar - esta tarefa consolida e finaliza essa linha de investigação.

## Padrão de UX esperado (confirmado pelo usuário observando o
AureusERP original)

1. O switcher global (ícone de grade ⊞, topo esquerdo) mostra TODOS os
   plugins/módulos disponíveis como ícones - é a "porta de entrada".
2. Nenhum módulo aparece como botão fixo permanente na topbar por
   padrão.
3. Ao SELECIONAR um módulo nesse switcher, o contexto muda: a topbar
   passa a mostrar um botão maior com o NOME do módulo ativo (ex:
   "Projetos"), seguido dos itens de navegação PRINCIPAIS daquele
   módulo especificamente (ex: Projetos, Tarefas, Configurações) -
   não os itens de outros módulos ao mesmo tempo.
4. Dentro desse contexto, se houver mais profundidade (ex:
   Configurações > Project Stages, Milestones, Tags), abre-se um
   submenu VERTICAL na lateral esquerda (visto no exemplo de
   Configurations do plugin projects).

## O que está diferente no Perseu-FA

Hoje, "Comercial" e "Pessoas" aparecem SIMULTANEAMENTE como botões
fixos na topbar, sempre visíveis ao mesmo tempo, independente de qual
módulo está "ativo" - não seguindo o padrão de troca de contexto
descrito acima.

## Investigação necessária

1. Esse comportamento (switcher global + contexto ativo trocando o
   conteúdo da topbar) é uma configuração nativa do Filament
   relacionada a ->topNavigation() combinado com múltiplos
   NavigationGroups/Clusters, ou é uma implementação customizada do
   AureusERP em cima do Filament? Investigue o método
   registerNavigation()/topNavigation() no PanelProvider e como ele
   difere quando há 1 vs. múltiplos grupos de navegação.

2. Compare linha a linha o AdminPanelProvider.php do Perseu-FA com o
   de ~/testes/aureuserp - existe alguma configuração de navegação
   ausente ou diferente (ex: algo relacionado a agrupamento de
   Clusters, ou como os Clusters "webkul" se diferenciam dos nossos
   "perseu" em termos de registro)?

3. Investigue se a diferença é estrutural: no AureusERP, cada "app"
   do switcher (Contacts, Inventory, Projects) pode ser cada um o seu
   próprio conjunto de Clusters/Resources agrupados sob um mesmo
   "grupo de navegação principal" reconhecido pelo Filament como uma
   unidade só - enquanto nossos PessoasCluster e ComercialCluster
   podem estar registrados de forma "solta", sem esse agrupamento de
   nível superior que ativa o comportamento de troca de contexto.

4. Se identificar a causa exata, explique o que seria necessário
   ajustar em nossos plugins (PessoasPlugin.php, ComercialPlugin.php)
   para que eles sigam esse mesmo padrão - MAS NÃO IMPLEMENTE ainda,
   apenas relate a causa raiz e o caminho de correção proposto, para
   eu confirmar antes de aplicar (essa é uma mudança estrutural de
   navegação que afeta a experiência de todo o sistema, quero validar
   antes).

Seja bem específico técnico na resposta - isso vai definir como TODOS
os plugins futuros do Perseu devem ser estruturados dali em diante.
