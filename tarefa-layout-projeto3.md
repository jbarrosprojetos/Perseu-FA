Reorganizar o formulário de ProjetoResource (plugin Comercial) em 3
linhas distintas:

## Linha 1
numero_projeto, revisao, data_cadastro, descricao ("Nome da Obra"),
tipo_projeto_id, situacoes — mantendo as larguras já calibradas na
tarefa anterior.

## Linha 2 (nova composição - juntar tudo relacionado ao
contratante/contato numa linha só)
tipo_contratante (Radio "Física"/"Jurídica"), o Select de Cliente
(pessoa_fisica_id OU pessoa_juridica_id, o que estiver visível),
contato_pessoa_fisica_id, contato_email, contato_telefone — todos
nessa mesma linha, nesta ordem, mantendo as larguras já calibradas
(Radio 22ch, Contato/Email/Telefone conforme ajustado na tarefa
anterior). O Select de Cliente pode manter crescimento (grow) dentro
do espaço restante dessa linha.

## Linha 3
endereco_id (Endereço da Obra), sozinho, em sua própria linha.

Ao final, rode ddev artisan optimize:clear e ddev artisan filament:assets.
Confirme via HTML renderizado a nova estrutura de 3 linhas.
