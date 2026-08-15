<x-filament-panels::page>
    <div class="flex flex-col gap-8">
        <div class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <p class="text-sm font-semibold text-gray-950 dark:text-white">
                Tecnologia e Precisão desde a Venda, Projetos, Fábrica, execução ao Faturamento.
            </p>

            <p class="text-sm text-gray-500 dark:text-gray-400">
                O Perseu MRP é um software de gestão de produção desenvolvido especificamente para o setor de marcenarias e indústrias de móveis. Personalizado que integra todas as etapas do negócio, eliminando o desperdício de matéria-prima, otimizando o tempo e garantindo a máxima lucratividade e controle em cada projeto.
            </p>
        </div>

        <div class="flex flex-col gap-4">
            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                Módulos do Sistema
            </span>

            {{ $this->servicesInfolist }}
        </div>

        <div class="from-primary-600 to-primary-500 flex flex-wrap items-center justify-between gap-4 rounded-xl bg-gradient-to-r p-6">
            <div class="flex items-center gap-4">
                <div class="size-12 flex shrink-0 items-center justify-center rounded-full bg-white/20">
                    <x-filament::icon icon="heroicon-o-chat-bubble-left-right" class="size-6 text-white" />
                </div>

                <div>
                    <div class="text-base font-semibold text-white">
                        Ainda precisa de ajuda?
                    </div>
                    <div class="text-sm text-white/80">
                        Fale com nossa equipe sobre hospedagem, implementação, desenvolvimento customizado ou qualquer outra necessidade.
                    </div>
                </div>
            </div>

            <div class="flex shrink-0 flex-wrap items-center gap-3">
                <button
                    type="button"
                    disabled
                    class="text-primary-600 inline-flex shrink-0 items-center gap-2 whitespace-nowrap rounded-lg bg-white px-5 py-2.5 text-sm font-semibold shadow-sm opacity-60"
                >
                    <span>Fale Conosco</span>
                    <x-filament::icon icon="heroicon-m-arrow-right" class="size-4" />
                </button>
            </div>
        </div>
    </div>
</x-filament-panels::page>
