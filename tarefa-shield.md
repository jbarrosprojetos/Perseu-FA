Preciso proteger todas as "Settings Pages" do projeto com o Filament Shield.

Tarefa: encontre todas as classes PHP que estendem `Filament\Pages\SettingsPage`
dentro de plugins/webkul/*/src/Filament/**/Settings/Pages/*.php (ou caminho
equivalente). Para CADA uma dessas classes:

1. Adicione o import: use BezhanSalleh\FilamentShield\Traits\HasPageShield;
2. Adicione `use HasPageShield;` dentro do corpo da classe, logo no início
3. Não altere mais nada no arquivo

Depois de processar todas, me liste quais arquivos foram modificados,
sem executar nenhum comando artisan.
