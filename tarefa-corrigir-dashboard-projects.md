O plugin projects está instalado (decisão consciente, mantido por
agora para demonstração). A página Webkul\Project\Filament\Pages\Dashboard
sequestrou a rota principal do painel (filament.admin.pages.dashboard),
e o widget TaskByStageChart nela quebra com
"Method ...TaskByStageChart::canAccess does not exist" ao carregar.

Preciso de uma correção MÍNIMA e reversível: impedir que essa página
Dashboard específica do plugin projects seja descoberta/registrada,
para que a rota principal do painel volte a ser o dashboard original
do Perseu, sem remover o resto do plugin (Resources de Project, Task,
Milestone, Tag continuam disponíveis normalmente no menu).

Investigue onde o ProjectPlugin/ProjectServiceProvider chama
discoverPages() (provavelmente apontando pra toda a pasta
Filament/Pages do plugin) e ajuste para EXCLUIR especificamente o
arquivo Dashboard.php dessa descoberta (sem apagar o arquivo em si -
só impedir o registro automático), da forma mais simples e reversível
possível.

Ao final, rode ddev artisan optimize:clear e ddev artisan filament:assets.
Confirme via route:list que admin/project não aparece mais como
filament.admin.pages.dashboard, e que o dashboard original do Perseu
voltou a ser a home do painel. Não desinstale nem remova nenhuma outra
parte do plugin.
