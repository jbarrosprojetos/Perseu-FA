<?php

namespace Perseu\Comercial\Filament\Clusters\Comercial\Resources\ProjetoResource\Concerns;

/**
 * Guarda o resultado da rotina "Checar Total" (ver
 * `ProjetoResource::processarUploadPromob()`) pra exibir dentro do
 * PRÓPRIO modal de upload do Promob, sem fechar/recarregar a página —
 * mesmo padrão já usado por `EditProjeto::$itensCarregados` (property
 * pública na página, lida via `$livewire` injetado no Schema, em vez
 * de tentar guardar isso no estado do formulário/Action). Usado tanto
 * por `CreateProjeto` quanto por `EditProjeto` (upload do Promob não
 * depende de o Projeto já estar salvo, diferente de "Item Avulso"),
 * por isso é um trait, não uma property só em `EditProjeto`.
 */
trait HasPromobResultado
{
    /**
     * `null` = modal ainda não processou nada nesta sessão (ou acabou
     * de ser reaberto — ver `mountUsing()` da Action). Formato completo
     * em `PromobChecagemTotal::checar()`, com a chave `erro` a mais
     * quando o processamento falha antes de conseguir comparar nada
     * (XML inválido, nenhum XML "000" enviado etc.).
     *
     * @var array<string, mixed>|null
     */
    public ?array $promobResultado = null;

    /**
     * "Criar Itens" (ver CLAUDE.md, "Fluxo Promob" — fluxo de criação
     * real dos Itens, 2026-09-06) fica DESABILITADO até "Checar Total"
     * rodar com sucesso PELO MENOS UMA VEZ nesta sessão do modal — não
     * basta ter um XML "000" válido selecionado (`promobTemXmlGeralValido()`,
     * checagem só de NOME de arquivo), precisa ter processado de fato.
     * Resetado em `mountUsing()` de `inserirItemPromob`, mesma lição de
     * nunca confiar em estado de uma sessão anterior do modal.
     */
    public bool $promobChecagemFeitaComSucesso = false;
}
