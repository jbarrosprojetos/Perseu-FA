Preciso reescrever completamente a página de Ajuda do sistema
(plugins/webkul/support/src/Filament/Pages/Help.php e a view Blade
correspondente, support::pages.help), removendo toda referência à
Aureus/Webkul e substituindo pelo conteúdo do sistema "Perseu MRP".

## 1. Cabeçalho da página (título, heading, subheading)

Substitua o conteúdo atual por:

Título/Heading: "Perseu MRP"
Subheading: "Planejamento Estratégico de Recursos, Suprimentos,
Engenharia e Usinagens"

Adicione, como texto descritivo abaixo do heading (novo bloco, se não
houver um lugar natural para isso, crie um):

"Tecnologia e Precisão desde a Venda, Projetos, Fábrica, execução ao
Faturamento.

O Perseu MRP é um software de gestão de produção desenvolvido
especificamente para o setor de marcenarias e indústrias de móveis.
Personalizado que integra todas as etapas do negócio, eliminando o
desperdício de matéria-prima, otimizando o tempo e garantindo a máxima
lucratividade e controle em cada projeto."

## 2. Substituir o método services() (os 3 cards atuais: Hospedagem em
nuvem, Suporte e manutenção, Serviços pagos) por 6 novos cards, um
para cada módulo do sistema:

1. Ícone: algo como heroicon-o-currency-dollar ou heroicon-o-chat-bubble-left-right
   Título: "Comercial"
   Descrição: "Orçamentos Rápidos: Cálculo automatizado de custos com
   base em insumos (chapas de MDF, ferragens, fitas de borda). Gestão
   de Contratos: Controle de prazos de entrega, condições de pagamento
   e histórico de relacionamento com o cliente. Funil de Vendas:
   Acompanhamento desde o primeiro contato até o fechamento do
   projeto."

2. Ícone: algo como heroicon-o-squares-2x2 ou heroicon-o-cube-transparent
   Título: "Projetos"
   Descrição: "Integração Promob/Sketchup: Cadastro automático de
   produtos, Importação de listas de corte. Explosão de Materiais
   (BOM): exportação automática do projeto técnico em uma lista real
   de necessidades de compras e ferragens. Aprovação Técnica:
   Checkpoint digital para garantir que o projeto comercial está
   viável para a produção."

3. Ícone: heroicon-o-archive-box
   Título: "Controle de Estoque"
   Descrição: "Gestão de Chapas e Sobras: Rastreamento inteligente de
   retalhos e sobras de MDF para reutilização em projetos futuros.
   Ponto de Ressuprimento: Alertas automáticos para compra de
   ferragens e insumos críticos antes que faltem na fábrica.
   Inventário Dinâmico: Atualização em tempo real do saldo de estoque
   integrado diretamente ao consumo da produção."

4. Ícone: heroicon-o-cog-6-tooth
   Título: "Produção"
   Descrição: "Sequenciamento de Ordens de Produção (OP): Cronograma
   visual (Gantt/Kanban) para todos os processos de produção: corte,
   colagem de borda, furação e montagem. Apontamento no Chão de
   Fábrica: Painel para os marceneiros darem início e fim às tarefas
   via tablet ou código de barras. Eficiência Produtiva: Controle de
   gargalos, produtividade da equipe e tempo gasto por módulo ou
   ambiente."

5. Ícone: heroicon-o-truck
   Título: "Recebimento e Faturamento"
   Descrição: "Conferência automática: Entrada de insumos via
   importação de XML da Nota Fiscal, garantindo que o que foi comprado
   é o que foi entregue. Faturamento Integrado: Emissão automatizada
   de Notas Fiscais (NF-e) vinculadas à entrega do projeto. Logística
   de Entrega: Controle de romaneios de carga e status do envio dos
   móveis até a casa do cliente."

6. Ícone: heroicon-o-wrench
   Título: "Execução da Obra (Montagem in loco)"
   Descrição: "Cronograma de Instalação: alinhado com a data de
   término da produção. Controle de Colaboradores: Escala de equipes
   de montadores profissionais, controle de horas trabalhadas e
   diárias. Gestão de Ferramentas e Insumos: Checklist digital da
   montagem e de ferramentas necessárias para o dia (parafusadeiras,
   níveis, serras) e insumos de acabamento (silicones, colas, parafusos
   extras). Logística, Translados e Fretes: Gestão integrada, custos
   de frete terceirizado ou próprio, combustível, pedágios e despesas
   de deslocamento da equipe técnica."

Antes de usar os nomes de ícone sugeridos acima, CONFIRME que existem
no pacote de heroicons instalado no projeto (blade-ui-kit/blade-heroicons).
Se algum nome sugerido não existir, escolha o ícone disponível mais
semanticamente próximo.

Cada um desses 6 cards deve ter um botão com o texto "Ver Detalhes",
mas SEM link/href funcional (sem apontar para lugar nenhum por
enquanto) — pode ser um botão desabilitado visualmente ou sem ação
(href="#" sem comportamento, ou disabled), à sua escolha técnica, desde
que não gere erro nem navegue para lugar nenhum.

## 3. Remover completamente a seção "Recursos e Documentação" atual
(os cards de Módulos/Explorar módulos, Documentação dev, Guia do
usuário) — esses apontam para aureuserp.com, devdocs.aureuserp.com e
docs.aureuserp.com, que não fazem sentido para este sistema. Remova
essa seção inteira (ou, se preferir manter a estrutura de grid por
enquanto vazia para o futuro, apenas remova os 3 cards específicos e
me avise que a seção ficou vazia).

## 4. Botão "Fale Conosco" no rodapé/banner "Ainda precisa de ajuda?"

Mantenha o texto "Ainda precisa de ajuda?" e o botão "Fale Conosco",
mas REMOVA o link/href atual do botão (deixe sem destino por enquanto,
mesmo tratamento do item 2).

## Sobre tradução

Este conteúdo é específico da marca (Perseu MRP) e não precisa
necessariamente seguir o padrão __() de tradução do resto do sistema
— pode escrever o texto diretamente em português no Blade/PHP, já que
não haverá versão em inglês deste conteúdo por enquanto. Use seu
critério: se for mais simples/consistente usar __() mesmo assim
(populando só a chave pt_BR), pode fazer, mas não é obrigatório.

Ao final, rode ddev artisan optimize:clear e ddev artisan view:clear.
Me diga se algo do pedido não foi possível de implementar exatamente
como descrito, e por quê.