Releia a seção de largura de campos no CLAUDE.md antes de começar.

Problema: campos do tipo Select (dropdown/lista suspensa) usados com
HasCompactFieldWidth::compact()/compactByLabel() estão com o texto do
valor selecionado sendo sobreposto pelo ícone de limpar ("X") e/ou pela
seta do dropdown, porque a largura calculada não reserva espaço extra
para esses dois ícones internos (diferente de um TextInput comum, que
não tem nenhum ícone concorrendo com o texto).

Ajuste HasCompactFieldWidth::compact() (e compactByLabel(), se
aplicável) para detectar quando o componente é um Select (ou
equivalente com ícones internos, como campos com ->searchable() que
mostram ambos "X" e seta), e adicionar um slack extra específico para
esse tipo de componente (além do slack padrão de +2 já usado), o
suficiente para os dois ícones não sobreporem o texto.

Aplique essa correção de forma centralizada no trait, para funcionar
automaticamente em qualquer Select compacto do sistema (não só nos do
ProjetoResource) — reveja se plugins/perseu/pessoas também tem algum
Select afetado pelo mesmo problema (ex: estado_civil, sexo em
PessoaFisicaResource) e confirme/corrija se necessário.

Ao final, rode ddev artisan optimize:clear e ddev artisan filament:assets.
Confirme via HTML renderizado os novos valores de largura para os
Selects afetados (tipo_projeto_id, contato_pessoa_fisica_id,
pessoa_juridica_id/pessoa_fisica_id em ProjetoResource; estado_civil/
sexo em Pessoa Física/Jurídica).
