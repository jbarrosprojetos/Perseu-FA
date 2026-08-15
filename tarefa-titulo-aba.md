O título da aba do navegador está sendo composto como
"{Título da Página} - {Nome do App}" (ex: "Marca - Perseu"). Preciso
remover o prefixo do título da página, deixando a aba mostrar apenas
o nome do app (ex: apenas "Perseu"), em todas as páginas do painel
admin.

Investigue onde esse padrão de título é montado — pode ser em um
layout Blade (procure por algo como <title>...</title> combinando
$title com config('app.name') ou similar), em uma configuração do
Filament, ou em algum PanelProvider.

Ajuste para que a aba do navegador mostre apenas o nome do app,
sem o prefixo do título da página. Não altere o título que aparece
DENTRO da página (o cabeçalho "Marca" continua normal ali) — a
mudança é só no texto da aba do navegador.

Ao final, rode ddev artisan optimize:clear e me explique exatamente
onde encontrou e o que mudou.
