Preciso de um estudo minucioso do plugin "projects" do AureusERP
original, para avaliar se algo dele serve de referência ou pode ser
aproveitado no nosso sistema Perseu.

IMPORTANTE: investigue o código em /home/projeto_studio/testes/aureuserp
(a cópia de referência intocada do AureusERP original), NÃO no projeto
atual (~/Perseu-FA). NÃO altere nada em nenhum dos dois - esta tarefa é
só leitura e documentação.

## O que investigar (plugins/webkul/projects em ~/testes/aureuserp)

1. **Modelos e tabelas**: liste todos os Models do plugin, suas
   colunas principais e relacionamentos (Project, Task, Milestone, e
   qualquer outro encontrado)
2. **Estrutura de Tarefas (Task)**: como uma tarefa é modelada -
   status/estágio, responsável (assignee), prazo, prioridade,
   descrição, vínculo com Projeto e/ou Milestone
3. **Kanban**: o plugin tem uma visualização Kanban de tarefas? Se
   sim, como é implementada (é um recurso nativo do Filament, ou uma
   customização)? Como as tarefas mudam de coluna/status?
4. **Timesheets**: como funciona o registro de horas trabalhadas,
   vínculo com Task/Employee
5. **Chatter**: o que é exatamente esse sistema de "comunicação em
   tempo real" mencionado no marketing do produto - é um plugin
   separado (plugins/webkul/chatter) usado pelo Projects, ou embutido?
   Como funciona tecnicamente (comentários em um registro, notificação,
   etc.)?
6. **Widgets/Dashboard**: existe algum Widget ou Dashboard específico
   mostrando tarefas por usuário, ou visão geral de projetos? Se sim,
   descreva a estrutura (é isso que mais nos interessa, já que
   queremos um "Dashboard de usuários" para acompanhar tarefas)
7. **Dependências**: o plugin projects depende de outros plugins
   (Employees, Security, etc.)? Quais?

## Entregável

Crie um arquivo ESTUDO-PLUGIN-PROJECTS.md na raiz do projeto Perseu-FA
(~/Perseu-FA, não no local investigado), documentando tudo isso de
forma organizada, e terminando com uma seção "## Avaliação de
integração ao Perseu", contendo sua análise honesta sobre:
- O que poderia ser reaproveitado como referência de arquitetura
  (ex: como eles estruturam Task/Kanban/Widget) mesmo que
  reconstruído do zero em nosso próprio plugin
- O que seria arriscado ou trabalhoso demais de integrar diretamente
  (deles pra nosso sistema) devido a acoplamento com outros plugins do
  AureusERP (Employees, etc.) que talvez não usemos
- Uma sugestão de como estruturar um plugin próprio "Tarefas" ou
  "FollowUp" no Perseu, vinculado ao nosso model Perseu\Comercial\Projeto
  (não ao Project deles), para atender à necessidade descrita: um
  dashboard onde cada usuário vê suas tarefas pendentes, vinculadas
  aos projetos/obras que já cadastramos

Não crie nenhum código além deste arquivo de estudo.
