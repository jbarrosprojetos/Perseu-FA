Preciso de uma investigação e correção de 4 pendências neste projeto
AureusERP/Perseu. Trabalhe uma de cada vez, na ordem abaixo, e me avise
o resultado de cada uma antes de seguir para a próxima.

## 1. Moeda Real (BRL) ausente

Investigue a tabela "currencies" e o(s) seeder(s) responsável(is) por
popular moedas. Confirme se BRL (Real brasileiro) já existe como
registro. Se não existir, adicione. Se existir mas não estiver ativo/
disponível para seleção, ative. Verifique também se existe um campo de
"moeda padrão do sistema" (em Settings) e, se sim, aponte para BRL.

## 2. Busca de cidade trazendo dados de outro país

No cadastro de Empresa (Companies), investigue os campos "state_id" e
o autocomplete/busca de cidade. Descubra:
- De qual tabela/fonte de dados vem a lista de cidades
- Se essa fonte está filtrada por um país específico (country_id) que
  não é o Brasil, ou se não há filtro nenhum
- Se as tabelas "states" e "cities" (ou equivalente) têm registros do
  Brasil populados

Não corrija ainda — apenas me relate o que encontrou, pois a correção
pode exigir eu decidir entre "adicionar dados do Brasil que faltam" ou
"apenas mudar o filtro/padrão para Brasil".

## 3. Itens de menu/submenu em inglês

Faça uma varredura nos arquivos de navegação (getNavigationLabel,
getNavigationGroup, etc.) de todos os plugins instalados atualmente, e
identifique quais labels não estão usando a função de tradução __()
(ou seja, estão com texto em inglês fixo no código, não traduzível) e
quais estão usando __() mas faltam a chave correspondente no arquivo
pt_BR daquele plugin.

Liste os achados em um arquivo PENDENCIAS-TRADUCAO.md, sem corrigir
ainda.

## 4. Campos dentro de formulários em inglês

Mesma investigação do item 3, mas para os campos de formulário
(TextInput, Select, etc.) dentro dos Resources dos plugins instalados.
Adicione os achados ao mesmo PENDENCIAS-TRADUCAO.md.

Ao final dos 4 itens, me apresente um resumo do que foi corrigido
automaticamente (itens 1) e do que ficou documentado para eu decidir
(itens 2, 3 e 4).
