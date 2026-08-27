# Pendências de Integrações Externas

> Registradas durante a implementação da busca de CNPJ via BrasilAPI no
> plugin `perseu/pessoas` (2026-08-27, ampliada em 2026-08-28 para
> viabilizar emissão de NF-e). Ver `CLAUDE.md` para as convenções de
> layout/arquitetura desse plugin.

## BrasilAPI (https://brasilapi.com.br/docs) — já em uso

- `GET /api/cnpj/v1/{cnpj}` — busca automática de dados cadastrais da
  Pessoa Jurídica ao digitar o CNPJ (razão social, nome fantasia,
  telefone, e-mail, CNAE+descrição, data de abertura, porte+descrição,
  regime tributário inferido, situação cadastral, endereço).
  Implementado em `Perseu\Pessoas\Support\BrasilApiCnpjLookup`.

## NCM — removido do cadastro de Pessoa Jurídica, reservado para o futuro cadastro de Produto/Material

`GET /api/ncm/v1` / `GET /api/ncm/v1/{codigo}` foi implementada em
2026-08-27 para um campo `ncm` em Pessoa Jurídica, e **removida em
2026-08-28** por equívoco de escopo: NCM é classificação de
produto/mercadoria, não de empresa (ver CLAUDE.md, "NCM removido do
cadastro de Pessoa Jurídica"). A classe
`Perseu\Pessoas\Support\BrasilApiNcmLookup` foi **mantida no código**
(comentário "RESERVADO PARA USO FUTURO" no topo do arquivo) — reaproveitar
quando o cadastro de Produto/Material for criado, conectando-a a um
`Select` searchable lá (mesmo padrão já implementado, só muda o ponto de
uso).

## Feriados nacionais — reservado para uso futuro (não implementado)

`GET /api/feriados/v1/{ano}` retorna a lista de feriados nacionais do
ano informado (data + nome). Avaliada nesta tarefa, mas **fora de
escopo** — o uso previsto é em outro módulo (provavelmente
`Perseu\Comercial`, para cálculo de prazos/dias úteis em Projetos), não
no plugin de Pessoas. Não há código relacionado ainda.

## Bancos + PIX — fase 2, ligada a um futuro cadastro de conta corrente

`GET /api/pix/v1/participants` foi avaliada nesta tarefa. Retorna a
lista de instituições participantes do arranjo PIX (`ispb`, `nome`,
`nome_reduzido`, `modalidade_participacao`, `tipo_participacao`,
`inicio_operacao`) — **não** existe endpoint público para consultar os
dados de uma chave PIX específica de terceiros (não haveria como
existir, por privacidade/segurança: uma chave PIX resolve para dados
bancários de uma pessoa/empresa, e isso só é acessível pelas próprias
instituições financeiras via o arranjo PIX, não por uma API pública
sem autenticação).

A utilidade real desse endpoint, quando a fase 2 for planejada, é
popular um **Select de banco/instituição** (por `ispb`/nome) ao
cadastrar uma conta corrente do cliente/fornecedor — não descobrir a
chave PIX de alguém. Não implementado agora; sem código relacionado
ainda.
