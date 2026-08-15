Preciso mudar um padrão GLOBAL do sistema: as páginas de Visualizar e
Editar de TODOS os Resources (não só Companies) atualmente mostram o
título como "{Ação} {Nome do Registro}" (ex: "Visualizar FA Marcenaria
Ltda", "Editar João Silva") — é o comportamento padrão do Filament.

Quero que o título mostre apenas o nome do registro, sem o prefixo da
ação, em TODO o sistema.

Antes de editar qualquer Resource individualmente, investigue se esse
padrão de título vem de uma STRING DE TRADUÇÃO genérica do Filament
(algo como uma chave tipo "filament-panels::resources/pages/
view-record.title" ou "edit-record.title", combinando um placeholder
de label da ação com o registro). Se for esse o caso, a correção mais
elegante é publicar/sobrescrever essa tradução (em lang/pt_BR e lang/en,
no namespace correto), ajustando o formato da string para mostrar
apenas o nome do registro — isso resolveria em TODO o sistema de uma
vez, sem precisar editar cada Resource.

Se não existir esse mecanismo centralizado (ou se cada Resource realmente
sobrescreve getTitle() de forma independente, sem herdar de uma string
comum), me explique isso antes de tentar qualquer solução "em massa" —
nesse caso, não altere nada ainda, apenas relate a descoberta para
decidirmos juntos o melhor caminho (pode ser criar uma trait reutilizável
que os Resources futuros usem, por exemplo).

Ao final, rode ddev artisan optimize:clear.
