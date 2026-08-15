<?php

namespace Webkul\Support\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Infolists\Components\ViewEntry;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Webkul\Support\Enums\NavigationGroup;
use Webkul\Support\Settings\BrandSettings;

class Help extends Page
{
    use HasPageShield;

    protected string $view = 'support::pages.help';

    protected static ?string $slug = 'help';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('support::filament/pages/help.navigation.label');
    }

    public static function getNavigationGroup(): string|\UnitEnum
    {
        return NavigationGroup::Help;
    }

    public function getTitle(): string
    {
        return 'Perseu MRP';
    }

    public function getHeading(): string|Htmlable
    {
        $brand = settings(BrandSettings::class);

        $faviconUrl = $this->resolveBrandAsset($brand->favicon);

        $logo = '';

        if (filled($faviconUrl)) {
            $logo = '<img src="'.e($faviconUrl).'" alt="Perseu MRP" style="height: 4rem; width: auto;" class="h-auto object-contain">';
        }

        return new HtmlString('<span class="inline-flex items-center gap-3">'.$logo.'<span>Perseu MRP</span></span>');
    }

    protected function resolveBrandAsset(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//')) {
            return $path;
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        return asset($path);
    }

    public function getSubheading(): ?string
    {
        return 'Planejamento Estratégico de Recursos, Suprimentos, Engenharia e Usinagens';
    }

    public function servicesInfolist(Schema $schema): Schema
    {
        return $schema->components([
            $this->cardsGrid($this->services()),
        ]);
    }

    protected function cardsGrid(array $cards): Grid
    {
        return Grid::make([
            'default' => 1,
            'md'      => 2,
            'xl'      => 3,
        ])->schema(
            array_map(
                fn (array $card, int $index): ViewEntry => ViewEntry::make("card_{$index}")
                    ->hiddenLabel()
                    ->view('support::pages.partials.help-card', ['card' => $card]),
                $cards,
                array_keys($cards),
            )
        );
    }

    protected function services(): array
    {
        return [
            [
                'icon'        => 'heroicon-o-currency-dollar',
                'title'       => 'Comercial',
                'description' => 'Orçamentos Rápidos: Cálculo automatizado de custos com base em insumos (chapas de MDF, ferragens, fitas de borda). Gestão de Contratos: Controle de prazos de entrega, condições de pagamento e histórico de relacionamento com o cliente. Funil de Vendas: Acompanhamento desde o primeiro contato até o fechamento do projeto.',
                'url'         => null,
                'button'      => 'Ver Detalhes',
            ],
            [
                'icon'        => 'heroicon-o-squares-2x2',
                'title'       => 'Projetos',
                'description' => 'Integração Promob/Sketchup: Cadastro automático de produtos, Importação de listas de corte. Explosão de Materiais (BOM): exportação automática do projeto técnico em uma lista real de necessidades de compras e ferragens. Aprovação Técnica: Checkpoint digital para garantir que o projeto comercial está viável para a produção.',
                'url'         => null,
                'button'      => 'Ver Detalhes',
            ],
            [
                'icon'        => 'heroicon-o-archive-box',
                'title'       => 'Controle de Estoque',
                'description' => 'Gestão de Chapas e Sobras: Rastreamento inteligente de retalhos e sobras de MDF para reutilização em projetos futuros. Ponto de Ressuprimento: Alertas automáticos para compra de ferragens e insumos críticos antes que faltem na fábrica. Inventário Dinâmico: Atualização em tempo real do saldo de estoque integrado diretamente ao consumo da produção.',
                'url'         => null,
                'button'      => 'Ver Detalhes',
            ],
            [
                'icon'        => 'heroicon-o-cog-6-tooth',
                'title'       => 'Produção',
                'description' => 'Sequenciamento de Ordens de Produção (OP): Cronograma visual (Gantt/Kanban) para todos os processos de produção: corte, colagem de borda, furação e montagem. Apontamento no Chão de Fábrica: Painel para os marceneiros darem início e fim às tarefas via tablet ou código de barras. Eficiência Produtiva: Controle de gargalos, produtividade da equipe e tempo gasto por módulo ou ambiente.',
                'url'         => null,
                'button'      => 'Ver Detalhes',
            ],
            [
                'icon'        => 'heroicon-o-truck',
                'title'       => 'Recebimento e Faturamento',
                'description' => 'Conferência automática: Entrada de insumos via importação de XML da Nota Fiscal, garantindo que o que foi comprado é o que foi entregue. Faturamento Integrado: Emissão automatizada de Notas Fiscais (NF-e) vinculadas à entrega do projeto. Logística de Entrega: Controle de romaneios de carga e status do envio dos móveis até a casa do cliente.',
                'url'         => null,
                'button'      => 'Ver Detalhes',
            ],
            [
                'icon'        => 'heroicon-o-wrench',
                'title'       => 'Execução da Obra (Montagem in loco)',
                'description' => 'Cronograma de Instalação: alinhado com a data de término da produção. Controle de Colaboradores: Escala de equipes de montadores profissionais, controle de horas trabalhadas e diárias. Gestão de Ferramentas e Insumos: Checklist digital da montagem e de ferramentas necessárias para o dia (parafusadeiras, níveis, serras) e insumos de acabamento (silicones, colas, parafusos extras). Logística, Translados e Fretes: Gestão integrada, custos de frete terceirizado ou próprio, combustível, pedágios e despesas de deslocamento da equipe técnica.',
                'url'         => null,
                'button'      => 'Ver Detalhes',
            ],
        ];
    }
}
