# Conceito — Obra, Proposta e Projeto

> Documento vivo. Registra o modelo de negócio conceitual conforme
> discutido com o Julio em 28/08/2026, para orientar o desenho técnico
> quando essas fases forem efetivamente desenvolvidas no plugin
> `perseu/comercial`. Deve ser atualizado/enriquecido ao longo do
> desenvolvimento — não é uma especificação fechada.

## Estado atual da implementação (referência)

Hoje existe apenas o cadastro de **Obra** (recém-renomeado de
"Projeto" — ver `CLAUDE.md` para o histórico do rename), com:

- Numeração automática própria (padrão `AAT####`).
- **Situação da Obra** (`SituacaoObra`) e **Tipo de Obra** (`TipoObra`)
  como cadastros de apoio.

As fases de **Proposta** e **Projeto** descritas abaixo ainda **não
foram implementadas** — este documento existe justamente para
registrar o conceito de negócio antes de chegar a hora de construir
essas telas, para não se perder o raciocínio entre sessões.

## O conceito

O ciclo de vida de um negócio na F.A. Marcenaria não é uma sequência
de cadastros independentes — é **uma única Obra evoluindo por fases**,
cada fase com seu próprio controle de situação e revisões.

```
Obra (número AAT#### — permanente, criado uma única vez)
 │
 ├─ Fase: Proposta
 │   (negociação com o cliente, ainda não fechada)
 │
 └─ Fase: Projeto
     (proposta aprovada e fechada financeiramente — agora em execução)
```

A Obra é a entidade raiz e permanente: nasce no primeiro contato com o
cliente e carrega o mesmo número durante todo o ciclo, independente de
em qual fase (Proposta ou Projeto) o negócio esteja.

### Fase: Proposta

- Momento de negociação, **antes** do fechamento comercial/financeiro.
- Pode ter **várias revisões** enquanto se negocia com o cliente
  (mudanças de escopo, valor, itens) — cada revisão é uma versão da
  Proposta, não uma Proposta nova.
- Antes de a Proposta ser efetivamente **apresentada ao cliente**,
  existe uma **transação interdepartamental**: o Comercial depende do
  departamento de **Projetos** para produzir um levantamento técnico
  inicial — dados de custo de materiais, serviços, transporte e
  execução — que alimenta o valor e o conteúdo da Proposta. Ou seja, a
  Proposta não é só uma estimativa comercial "de cabeça"; ela carrega
  dados técnicos reais desde o início.
- Esse fluxo (Comercial aguardando retorno de Projetos) é, em si, um
  dos estados possíveis da Situação da Proposta.

### Fase: Projeto

- Começa quando a Proposta é **aprovada e fechada financeiramente**
  com o cliente.
- Mesmo fechado, **continua podendo ter revisões** ao longo da
  execução: itens podem ser adicionados, modificados ou excluídos, e
  os valores podem mudar.
- **Regra de negócio importante**: o Projeto e a cobrança devem se
  manter equalizados (sincronizados) até o final da execução — ou
  seja, qualquer alteração de escopo/valor durante a execução precisa
  refletir corretamente no que é cobrado do cliente, sem ficar
  dessincronizado.

## As três dimensões de Situação

Cada fase tem sua própria linha de status, porque representam
momentos e preocupações diferentes — não faz sentido misturar tudo
numa Situação só:

- **Situação da Obra**: o estágio geral do ciclo de vida como um todo
  (ex: em prospecção, em proposta, em execução, concluída, cancelada).
  É o "resumo executivo" de onde a Obra está.
- **Situação da Proposta**: específica da negociação comercial (ex:
  aguardando levantamento técnico do departamento de Projetos, em
  elaboração, enviada ao cliente, em negociação, aprovada, recusada).
- **Situação do Projeto**: específica da fase técnica/execução (ex:
  em levantamento técnico inicial — ver ponto em aberto abaixo — ,
  aprovado internamente, em execução, em revisão, concluído).

## Ponto em aberto (decidir quando for desenhar tecnicamente)

O levantamento técnico inicial que o departamento de Projetos faz
**antes** da Proposta ser apresentada ao cliente (dados de custo de
materiais/serviços/transporte/execução) e o **Projeto de execução**
(a fase pós-aprovação, com revisões de escopo/valor durante a obra em
si) — são:

(a) **o mesmo registro evoluindo ao longo do tempo** (o levantamento
técnico inicial "vira" o Projeto de execução quando a Proposta é
aprovada), ou

(b) **dois registros/momentos distintos** dentro da mesma Obra (um
levantamento preliminar que serve só de insumo para a Proposta, e um
Projeto de execução que nasce separadamente quando a Proposta é
aprovada, possivelmente reaproveitando dados do levantamento inicial
mas sem ser o mesmo registro)?

Essa decisão muda bastante o desenho de tabelas/relacionamentos
quando chegar a hora — não precisa ser resolvida agora, só fica
registrada como pergunta em aberto.

## Fluxo detalhado: da Obra ao Pedido de Compra (registrado em 30/08/2026)

> Esta seção detalha o "meio do caminho" entre a Obra ser cadastrada e
> a geração de um Pedido de Compra — a parte mais rica em integrações
> entre departamentos (Comercial, Projetos, Compras) e com ferramentas
> externas (Promob, SketchUp/Hellomob). Ainda **conceitual**, nada
> aqui foi implementado.

### Visão geral do fluxo

```
Obra cadastrada
 │
 ▼
Proposta criada, itens adicionados
 │
 ├─ cada item pode exigir um XML de composição de custo
 │  (produto padrão OU desenvolvimento novo)
 │
 ▼
Tarefa enviada ao Projetista (departamento de Projetos)
 │  — projetista modela no Promob ou SketchUp+Hellomob
 │  — gera o XML com dados de composição de custo do item
 ▼
XML retorna para o Comercial
 │
 ▼
Comercial finaliza a Proposta
 (preços, frete, condições de pagamento)
 │
 ▼
Apresentação ao cliente → negociação
 │
 ├─── Declinada ─────────────────► FIM (Obra encerra aqui)
 │
 ▼ Vendida/Aprovada
 │
 ▼
Fase de Projeto (execução) começa
 │
 ├─ vistorias na obra (se necessário)
 ├─ projeto executivo (com aprovação do cliente)
 ├─ plano de corte e desenhos técnicos
 │
 ▼
Novo XML gerado (o "XML do projeto final",
diferente do XML de composição de custo da Proposta)
 │
 ▼
Checagem de estoque a partir do XML do projeto final
 │
 ▼
Requisição interna de compra (só do que falta em estoque)
 │
 ▼
Aprovação da requisição
 │
 ▼
Departamento de Compras: cotações
 │
 ▼
Pedido de Compra
 (pode agrupar itens de VÁRIAS requisições,
  e uma requisição pode se dividir em VÁRIOS pedidos,
  conforme fornecedor)
 │
 ▼
[fora de escopo desta seção, só citado para contexto:]
Recebimento → Contas a Pagar → Estoque → Produção → Expedição →
Faturamento → Execução (montagem no cliente) → Contas a Receber
```

### Itens da Proposta e o XML de composição de custo

- Cada item de uma Proposta pode ser:
  - Um **produto padrão** (catálogo já existente de itens/produtos —
    este catálogo ainda não existe no Perseu MRP; será parte de um
    futuro módulo de Produtos/Materiais, mencionado como pendência
    desde a decisão de reverter o campo NCM do cadastro de Pessoa
    Jurídica).
  - Um **desenvolvimento novo** (móvel/peça projetada especificamente
    para aquela Obra), atualmente modelado no **Promob**, com
    possibilidade futura de uso do **SketchUp** com um plugin como o
    **Hellomob** como ferramenta alternativa de modelagem.
- Em ambos os casos, um **XML** é gerado pela ferramenta de modelagem
  contendo informações que ajudam a compor o **custo do item**
  (materiais, ferragens, quantidades, etc. — o conteúdo exato do XML
  do Promob/Hellomob ainda precisa ser estudado tecnicamente quando
  chegarmos nessa fase de implementação).
- Esse XML é o que o Perseu MRP precisará **importar/interpretar**
  para compor o custo de cada item da Proposta.

### O hand-off Comercial ↔ Projetista (via plugin de Tarefas)

- Ao precisar do XML de um item novo, o Comercial (ou o próprio fluxo
  do sistema) cria uma **tarefa** direcionada a um usuário do
  departamento de **Projetos** (o projetista).
- A ideia é usar o plugin de Tarefas (`webkul/projects`) já instalado
  no Perseu-FA para esse hand-off — ou seja, aproveitar a
  infraestrutura de tarefas/atribuição de usuário que já existe, em
  vez de criar um mecanismo de fila próprio.
- O projetista modela o item, gera o XML, e o processo retorna para o
  Comercial dar continuidade à Proposta (precificação, frete,
  condições de pagamento).
- Esse mesmo padrão de "tarefa direcionada a outro departamento" pode
  se repetir mais adiante no fluxo (ex: vistorias na obra, aprovação
  de projeto executivo) — vale desenhar esse mecanismo de forma
  reutilizável quando for implementado, não específico só para o
  hand-off Comercial→Projetista.

### Depois da venda: Projeto de execução

Quando a Proposta é aprovada/vendida, os itens retornam ao
departamento de Projetos para o desenvolvimento do **projeto final**,
que inclui (conforme a necessidade de cada Obra):

- Vistorias na obra.
- Projeto executivo, com aprovação do cliente.
- Plano de corte e desenhos técnicos.

Esse trabalho gera um **segundo XML** — o "XML do projeto final" —
que é diferente do XML de composição de custo gerado na fase de
Proposta (esse segundo XML parece ter um propósito mais amplo: guiar
diretamente a necessidade de materiais para compra/produção, não só
compor um custo estimado).

### Do XML do projeto final até o Pedido de Compra

1. O XML do projeto final indica a necessidade de materiais.
2. O sistema verifica o **estoque** atual contra essa necessidade.
3. Para o que **não há em estoque**, gera-se uma **Requisição Interna
   de Compra**.
4. A requisição passa por uma **aprovação** antes de seguir para o
   departamento de Compras.
5. O departamento de **Compras** realiza cotações com fornecedores.
6. Depois de aprovadas as cotações, monta-se o **Pedido de Compra**.
   Regra importante: a relação entre Requisição e Pedido de Compra é
   **N:N** — um Pedido de Compra pode conter itens de várias
   Requisições diferentes (de Obras diferentes, inclusive), e uma
   única Requisição pode ser atendida por mais de um Pedido de Compra
   (ex: itens de fornecedores diferentes). Isso significa que o
   desenho de dados não pode assumir uma relação simples 1:1 ou 1:N
   nessa etapa.

### Continuação do fluxo (citada, não detalhada agora)

Depois do Pedido de Compra, o fluxo segue por: Recebimento → Contas a
Pagar → Estoque → Produção → Expedição → Faturamento → Execução
(montagem no cliente) → Contas a Receber. Cada uma dessas etapas é um
módulo próprio ainda não desenhado — citadas aqui só para registrar a
visão de ciclo completo, sem detalhamento nesta sessão.

### Pontos em aberto desta seção (não decidir agora)

- Estrutura exata e schema dos XMLs (Promob e, futuramente, SketchUp/
  Hellomob) — provavelmente são formatos diferentes entre si, exigindo
  parsers distintos ou uma camada de normalização.
- Como modelar o catálogo de "produto padrão" vs. "desenvolvimento
  novo" — decisão de arquitetura registrada na seção seguinte ("Itens
  do Projeto: dois tipos, não um cadastro único"); o schema exato
  ainda depende do futuro módulo de Produtos/Materiais, ainda não
  iniciado.
- Mecanismo genérico de "tarefa entre departamentos" (hand-off) — se
  será construído em cima do plugin de Tarefas existente de forma
  direta, ou se precisará de uma camada própria no `perseu/comercial`/
  `perseu/projetos` (a definir) que apenas dispara tarefas no plugin
  existente.
- Regras de aprovação da Requisição Interna (quem aprova, critérios) —
  ainda não discutido.
- Como as revisões de Proposta/Projeto (já registradas na seção
  anterior deste documento) interagem com XMLs já gerados — um XML
  gerado antes de uma revisão de escopo fica obsoleto? precisa
  regenerar?

## Itens do Projeto: dois tipos, não um cadastro único (registrado em 02/09/2026)

> Avança o ponto em aberto "Como modelar o catálogo de 'produto
> padrão' vs. 'desenvolvimento novo'", da seção anterior — não resolve
> o schema exato (isso continua para uma tarefa futura específica),
> mas registra a decisão de arquitetura que vai orientar esse desenho,
> pra não perder o racional entre sessões.

Ao detalhar o conteúdo de um Projeto (peças/produtos que serão
fabricados/entregues), ficou claro que existem DOIS tipos distintos de
"item", e o sistema precisa suportar ambos, não forçar um modelo
único:

1. **Item vinculado a um Produto do cadastro convencional** — o
   "produto padrão" já citado na seção anterior. Usado quando o item é
   algo padronizado, de linha de produção, que já tem toda sua
   estrutura definida previamente (ficha técnica, composição de
   matéria-prima/serviços, tabela de preços) no futuro cadastro de
   Produtos. Neste caso o item do Projeto apenas referencia esse
   Produto existente (FK), sem duplicar dados.
2. **Item avulso, exclusivo daquele Projeto** — o "desenvolvimento
   novo" já citado na seção anterior. Usado quando o item é um
   desenvolvimento sob medida, específico para aquele cliente/projeto,
   que NÃO deve virar um cadastro de Produto reutilizável. Este é o
   caso mais comum na marcenaria: cada móvel é pensado e dimensionado
   milimetricamente para um cliente específico, raramente se repetindo
   de forma idêntica em outro projeto (exceto alguns itens
   convencionais de linha, que se enquadram no caso 1). Este item
   existe só dentro do Projeto, com sua própria composição de
   matéria-prima e serviços utilizados (usando a Referência de Preços
   como base de custos/fatores), sem nunca gerar um registro
   correspondente no cadastro de Produtos.

**Implicação de design**: a estrutura de "Item do Projeto" não pode
assumir que todo item tem um Produto de cadastro por trás. Precisa
suportar um item "solo" com sua própria descrição e composição,
coexistindo na mesma listagem com itens que apontam para o cadastro de
Produtos. O desenho detalhado de campos/tabelas fica para uma tarefa
futura específica — este registro é só a decisão de arquitetura que
orienta esse desenho.

## Templates de Proposta/Contrato/Termos (registrado em 30/08/2026)

> A partir do estudo da planilha real hoje usada pela F.A. Marcenaria
> (`260000 Cliente Padrão Proposta 00 TP.xlsm`), ficaram claras
> algumas decisões de arquitetura para os futuros itens de
> Referências (Propostas, Contratos, Termos de Entrega, Termos de
> Garantia):

- **Templates são só layout de impressão.** Todo o cálculo (preços,
  impostos, descontos, totais) é sempre feito pelo Perseu MRP — o
  template nunca recalcula nada, só formata/apresenta os valores já
  calculados pelo sistema. Isso evita ter a lógica de negócio
  duplicada entre o sistema e um arquivo externo (planilha/documento).
- **Proposta e Contrato podem ser o mesmo documento final, ou não,
  dependendo do template escolhido.** A planilha atual da empresa
  combina os dois num único documento (cabeçalho comercial + cláusulas
  contratuais completas), mas o sistema deve permitir templates
  diferentes por critério (ex: tipo de cliente) — a empresa pode ter
  um conjunto de templates que junta tudo, e outro que separa Proposta
  de Contrato.
- **Montagem do PDF final é modular.** No momento de enviar a Proposta
  ao cliente, o usuário escolhe quais templates/modelos usar (ex:
  Proposta + Termos de Garantia + Termos de Entrega), e o sistema
  monta um único PDF final combinando os modelos escolhidos. Isso
  reforça a decisão de manter Preços, Propostas, Contratos, Termos de
  Entrega e Termos de Garantia como itens separados dentro de
  Referências (mesmo que às vezes sejam combinados na hora de gerar o
  PDF) — cada um continua sendo seu próprio template/cadastro.
- **A planilha atual tem uma cadeia de cálculo mais rica do que o
  cadastro de Preços implementado até agora**: além de Laminação,
  Corte, Hora de Produção, Hora de Execução e Retenção Técnica (RT) —
  já implementados —, ela também considera Imposto (sobre o produto),
  Despesas Variáveis e Despesas Fixas. Margem de Lucro NÃO entra como
  campo de entrada — é um resultado esperado (lucro bruto), não um
  parâmetro configurado na Referência de Preços.
- A planilha também depende de arquivos externos (`XLOOKUP` para
  "Contato Base" etc.) que hoje estão quebrados (`#REF!`) — não são
  mais necessários no Perseu, já que o sistema tem seus próprios
  cadastros de Pessoas/Endereços para essas buscas.

## Replanejamento: Proposta como registro separado ficou redundante (registrado em 01/09/2026)

> Uma primeira tentativa de implementação (Cluster "Propostas" com um
> cadastro `Proposta` separado, referenciando a Obra via dropdown de
> busca e replicando os dados do cabeçalho da Obra como somente
> leitura) foi **implementada, testada e depois descartada** — o
> código chegou a existir e funcionar tecnicamente, mas o usuário
> percebeu, ao usar, que o desenho ficou redundante e foi revertido de
> propósito (ver histórico de commits/CLAUDE.md sobre essa reversão).
> Esta seção registra o motivo, para não repetir o mesmo desenho.

**O problema identificado**: o número da Obra (ex: `AAT2610001`) já é
único, permanente e criado desde o primeiro contato com o cliente —
ele já FUNCIONA como o identificador da negociação desde o início. Ter
um cadastro `Proposta` separado, cujo único papel é apontar de volta
para uma Obra (via um campo de busca "Número — Descrição") e replicar
os dados do cabeçalho da Obra como somente leitura, criava uma camada
adicional sem ganho real — na prática, virou "a Obra, só que com um
clique a mais e um formulário a mais para preencher".

**Caminho a explorar quando retomarmos** (ainda não decidido, só
registrado como direção): a fase de Proposta provavelmente deve viver
**mais integrada à própria Obra** — como campos adicionais na mesma
tela/registro (ex: uma aba "Proposta" dentro do próprio `ObraResource`)
em vez de um Model/Resource totalmente separado que só referencia a
Obra de volta. Isso preserva a ideia de "Obra evoluindo por fases" (já
registrada na seção "O conceito" deste documento) de forma mais
literal: a MESMA tela/registro ganha uma fase nova, em vez de um novo
registro satélite.

O que ainda deve ser preservado da tentativa anterior (não descartar
ao redesenhar):
- **Situação de Proposta** como conceito de cadastro de apoio (mesmo
  padrão de Situação de Obra) provavelmente continua fazendo sentido,
  independente de onde o campo "Situação da Proposta" vai morar
  fisicamente (na própria Obra ou em algo separado).
- **Revisão** continua sendo um conceito da fase de Proposta (não da
  Obra em si) — só a forma de amarração ao registro de Obra que
  precisa ser repensada.
- Os aprendizados técnicos da tentativa (ex: `firstOrCreate` resiliente
  para o valor inicial de Situação, em vez de uma busca que falha se o
  registro sumir) valem para quando redesenharmos.

## Notas gerais

- Revisões (tanto de Proposta quanto de Projeto) provavelmente
  precisarão de uma estrutura própria de versionamento — histórico de
  itens e valores por revisão, não só o estado atual.
- A numeração AAT#### da Obra é única e não muda entre as fases —
  Proposta e Projeto, quando implementados, devem reaproveitar esse
  mesmo número, não criar numeração própria.
- Este documento deve ser revisado e expandido conforme o
  desenvolvimento avança e mais detalhes de negócio forem surgindo.

## Renomeação do plugin de acompanhamento + visão de motor de workflow (registrado em 02/09/2026)

> Investigação completa do plugin `webkul/projects` feita em
> `ANALISE-PLUGIN-TAREFAS.md` (raiz do projeto). Achados-chave: NÃO
> existe Kanban (é agrupamento com reordenação manual, não quadro
> arrastável entre colunas); "Plano de Atividade" é um recurso
> secundário (checklist reutilizável que gera atividades agendadas),
> não o núcleo do plugin; o rótulo "Projetos" no menu vem de
> `lang/pt_BR/admin.php`, fora do plugin — rename de baixo risco.

**Decisão de nome**: o plugin será renomeado para **"Gestão de
Processos"**. Esse nome foi escolhido deliberadamente mirando não só o
que o plugin faz hoje (organizar Projetos/Tarefas com etapas e
marcos), mas o que o usuário pretende construir em cima dele:

**Visão de motor de workflow (futuro, não implementado ainda)**: usar
a base de registros desse plugin (Projeto, Tarefa, Etapa, Marco) como
fundação para um motor que:
- Cria automaticamente Tarefas/Atividades a partir de eventos de
  negócio do sistema (ex: uma Obra ou Proposta mudando de Situação
  dispara a criação das tarefas certas no departamento certo — o
  mesmo tipo de hand-off Comercial↔Projetos já descrito na seção de
  fluxo Obra→Proposta deste documento, mas automatizado em vez de
  criado manualmente).
- Avalia sozinho quando um estágio foi concluído (com base em
  resultados/dados do sistema), avançando o processo automaticamente,
  sem depender de alguém marcar manualmente "concluído".
- Reduz a interação humana ao mínimo necessário: só pede decisão no
  nível de diretoria/gestão (ex: quem deve executar algo), não em
  cada passo operacional.

Isso é uma extensão grande e ainda não desenhada tecnicamente — fica
registrado aqui como direção de longo prazo, para orientar decisões
menores (como este rename) e para não ser esquecida entre sessões.
Quando chegar a hora de desenhar o motor de workflow em si, este
documento deve ganhar uma seção própria e detalhada para isso.

## Decisão final de nomenclatura: Processos / Projeto / Proposta como Situação (registrado em 02/09/2026)

> Substitui o entendimento anterior das seções "Replanejamento" e
> "Renomeação do plugin". Esta é a decisão final de nomenclatura.

Depois de considerar as opções, a solução que elimina a ambiguidade
pela raiz é:

1. **O plugin `webkul/projects`** (acompanhamento de tarefas/etapas/
   marcos, hoje rotulado "Projetos" no menu) passa a se chamar
   **"Processos"** — nome curto, consistente com o estilo dos outros
   itens do menu principal (Pessoas, Comercial, Configurações), e que
   também serve de base para a visão futura de motor de workflow
   (ver seção anterior).
2. **Isso libera a palavra "Projeto"** para nomear a entidade de
   negócio real, hoje chamada **Obra** em `perseu/comercial`. Ou seja,
   vamos fazer o rename inverso do que fizemos antes: **Obra →
   Projeto** (a entidade raiz, permanente, com numeração AAT####,
   volta a se chamar Projeto — mas agora sem o conflito de nome com o
   plugin de tarefas, já que este virou "Processos").
3. **"Proposta" deixa de ser um cadastro/registro separado.** Ela vira
   apenas um dos **valores possíveis de Situação** do Projeto (ex:
   "Proposta", "Projeto Inicial", "Em Negociação", e outros a definir
   junto com o departamento Comercial). Isso elimina de vez a
   redundância identificada na tentativa anterior de Cluster
   "Propostas" separado — não existe mais um registro satélite
   apontando de volta para a Obra/Projeto, só um campo de Situação com
   mais valores possíveis.

**Ordem de execução combinada com o usuário (por etapas, não tudo de
uma vez)**:
1. Rename do plugin `webkul/projects`: "Projetos" → "Processos" (rótulo
   apenas, baixo risco — já em andamento/concluído nesta sessão).
2. Rename completo de `Obra` → `Projeto` em `perseu/comercial` (classe,
   tabela, colunas, rotas, permissões, traduções — mesmo rigor do
   rename anterior Projeto→Obra, agora invertido).
3. Detalhamento dos valores de Situação (incluindo "Proposta" como um
   deles) — a ser definido em conversa com o usuário Comercial da
   empresa, ainda não feito.

Isso torna as seções anteriores ("Replanejamento: Proposta como
registro separado ficou redundante" e partes da seção "Renomeação do
plugin") historicamente relevantes mas **superadas** por esta decisão
final — mantidas no documento por rastreabilidade do raciocínio, não
como estado atual.

## Correção da decisão de nomenclatura (registrado em 02/09/2026, mesma sessão)

> Substitui o entendimento da seção anterior sobre o nome do plugin —
> o raciocínio de fundo (liberar a palavra "Projeto" para a entidade
> de negócio) continua válido, mas os nomes finais são diferentes do
> que foi registrado ali.

Quadro final de nomenclatura:

- **"Projeto"** — a entidade de negócio raiz em `perseu/comercial`
  (numeração AAT####, antes chamada "Obra"). Rename Obra → Projeto
  **concluído em 02/09/2026** — ver seção "Rename Obra → Projeto
  concluído" abaixo.
- **"Processo"** — a entidade interna do plugin `webkul/projects`
  (hoje "Project"/"Projeto" internamente no código), que passa a ter
  as **Tarefas** dentro dela. Um Processo representa **todo o ciclo
  operacional de ações sobre um Projeto**: início, negociação,
  alterações, revisões, fechamento, compras, produção, até a
  finalização. Rename completo (classe `Project`→`Processo`, tabela,
  rotas, permissões, traduções) a ser feito no plugin
  `webkul/projects`.
- **O plugin em si continua se chamando "Gestão de Processos"** no
  menu principal (não "Processos" sozinho — correção do que havia
  sido registrado antes).
- **"Proposta"** continua sendo um valor de Situação do Projeto (não
  um cadastro separado), conforme já registrado.

**Relação entre Projeto (Comercial) e Processo (Gestão de Processos)**:
ainda não desenhada tecnicamente — a ideia é que cada Projeto tenha (ou
gere) um Processo correspondente, que acompanha seu ciclo de
vida operacional completo via Tarefas/Etapas/Marcos do plugin de
Gestão de Processos. Como exatamente essas duas entidades se conectam
(1:1? o Processo é criado automaticamente quando o Projeto muda de
Situação? etc.) é uma decisão de desenho técnico futura, não resolvida
agora.

**Ordem de execução (atualizada)**:
1. ~~Rename interno `Project` → `Processo` dentro de `webkul/projects`
   (classe/tabela/rotas/permissões/traduções), mantendo o rótulo do
   menu do plugin como "Gestão de Processos".~~ **Concluído em
   02/09/2026** — ver seção "Rename interno Project → Processo
   concluído" abaixo e `CLAUDE.md` para o detalhamento técnico
   completo.
2. ~~Rename completo `Obra` → `Projeto` em `perseu/comercial`.~~
   **Concluído em 02/09/2026** — ver seção "Rename Obra → Projeto
   concluído" abaixo e `HISTORICO-DESENVOLVIMENTO.md` para o
   detalhamento técnico completo.
3. Desenho da relação entre Projeto e Processo. **Próximo passo, ainda
   não feito.**
4. Detalhamento dos valores de Situação (incluindo "Proposta").

## Rename interno Project → Processo concluído (02/09/2026)

O passo 1 da ordem de execução acima está **feito**: o plugin
`webkul/projects` teve sua entidade interna renomeada de `Project`
para `Processo` de ponta a ponta — Model, tabelas (`projects_projects`
→ `projects_processos`, e as 3 tabelas/colunas relacionadas), Filament
Resource (`ProcessoResource`), páginas, permissões Shield
(`*_project_processo`), e traduções nos 4 idiomas. O rótulo do menu
principal do plugin continua "Gestão de Processos" (do rename
anterior, só de label). Detalhamento técnico completo (arquivos
alterados, decisões de escopo sobre a camada de API REST, exceções
conscientes como o enum `ProjectVisibility` e a propriedade de
settings `enable_project_stages`) está registrado no `CLAUDE.md`, seção
"Rename interno Project → Processo no plugin webkul/projects".

**Pendência explícita aberta por esta tarefa**: a camada de API REST
do plugin (`Http/Controllers/API/V1/*`, `Http/Resources/V1/*`,
`Http/Requests/*` — ex.: `ProjectController`, rota
`admin/api/v1/projects/projects`) e a suíte de testes automatizados
(`tests/`) **deliberadamente NÃO foram renomeadas** — decisão tomada
com o usuário para não ampliar o escopo desta tarefa, já que nada no
Perseu consome essa API hoje. Só as referências internas (chamadas ao
Model/colunas renomeados) foram corrigidas nesses ~18 arquivos, o
suficiente para não quebrar em runtime — nomes de classe, rotas e
contrato JSON da API continuam com "project"/"projects" (com uma
pequena exceção aceita: o campo `processo_id` no payload JSON, já que
a coluna em si mudou de nome). Se um dia essa API for exposta a algum
consumidor real, revisitar esse rename como uma tarefa própria.

## Rename Obra → Projeto concluído (02/09/2026)

O passo 2 da ordem de execução acima está **feito**: o cadastro de
negócio central de `perseu/comercial` teve sua entidade interna
renomeada de `Obra` para `Projeto` de ponta a ponta — Model, tabelas
(`obras` → `projetos`, e as 4 tabelas/colunas relacionadas), Filament
Resources (`ProjetoResource`/`SituacaoProjetoResource`/
`TipoProjetoResource`), páginas, permissões Shield
(`*_comercial_projeto`), traduções (pt_BR/en) e as referências
cruzadas em `perseu/auditoria` (`SubjectTypeCatalog`, `TrashCatalog`,
Central de Auditoria, Lixeira Central — incluindo a atualização dos
`activity_log.subject_type` já gravados, pra não perder o histórico de
auditoria anterior ao rename). Detalhamento técnico completo
(arquivos alterados, migration, achado sobre `activity_log`, exceções
conscientes como `TipoEndereco::Obra` e `fator_mao_obra`) está
registrado no `HISTORICO-DESENVOLVIMENTO.md`, seção "Rename Obra →
Projeto no plugin perseu/comercial".

`obras.revisao` não mudou de significado — só acompanhou o rename da
tabela (virou `projetos.revisao`). A decisão de Revisão se tornar um
atributo formal de uma futura Situação "Proposta" continua sendo a
etapa 4 deste plano, ainda não feita.

**Não confundido com o rename anterior `Project → Processo`** (passo
1, `webkul/projects`) — namespaces e tabelas totalmente distintos, sem
FK entre si; a única relação entre os dois é conceitual (ver "Relação
entre Projeto (Comercial) e Processo (Gestão de Processos)" acima,
ainda não desenhada tecnicamente — próximo passo, etapa 3).
