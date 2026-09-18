<?php

// Config NOVO deste plugin (2026-09-14, dropdown "Otimizadores" do
// Plano de Corte — ver CLAUDE.md) — primeira vez que este plugin
// precisa de um valor de configuração próprio (nenhum `config/`
// existia antes). Registrado via `mergeConfigFrom()` em
// `ComercialServiceProvider::packageRegistered()`.
return [
    /**
     * Motor externo `fontanf/packingsolver` (MIT), modo
     * `rectangleguillotine` — só usado quando o usuário escolhe
     * "Packing Solver" no dropdown "Otimizadores" da tela de
     * "Produção" (`EditProjeto`). O algoritmo NATIVO
     * (`PlanoCorteNestingService`, opção padrão "Nativa") não depende
     * de nada disto.
     */
    'packingsolver' => [
        // Caminho ABSOLUTO do binário compilado (Windows: um .exe;
        // Linux: o executável). Sem isso configurado (ou se o caminho
        // não existir/não for executável), escolher "Packing Solver"
        // no dropdown falha com uma mensagem clara, orientando a usar
        // "Nativa" enquanto isso — nunca falha silenciosamente nem
        // derruba a geração do PDF sem explicação.
        'binario' => env('PACKINGSOLVER_BINARIO'),

        // Tempo máximo (segundos) que o solver pode gastar tentando
        // encaixar o que sobrou de peças em CADA chapa nova (o
        // processo é repetido chapa a chapa — ver docblock de
        // `PlanoCortePackingSolverService`). Grupos com muitas peças
        // podem se beneficiar de um valor maior; o padrão é
        // deliberadamente curto pra não deixar o clique em "Produção"
        // demorado.
        'tempo_limite_segundos' => (float) env('PACKINGSOLVER_TEMPO_LIMITE', 8),
    ],
];
