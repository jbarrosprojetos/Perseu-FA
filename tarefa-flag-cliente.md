Releia CLAUDE.md antes de começar. No plugin Pessoas
(plugins/perseu/pessoas), adicionar um novo campo booleano
"e_cliente" à tabela categoria_pessoa (e ao Model CategoriaPessoa),
para marcar qual(is) categoria(s) representam "Cliente" — usado
futuramente para filtrar quem pode ser Contratante em Projetos.

1. Criar uma nova migration (não alterar a original) adicionando a
   coluna e_cliente (boolean, default false) à tabela
   categoria_pessoa.
2. Adicionar e_cliente ao $fillable do Model CategoriaPessoa.
3. No CategoriaPessoaResource, adicionar um Toggle "e_cliente" no
   formulário, label "É categoria de Cliente?", ao lado dos toggles
   já existentes (aplica_pf/aplica_pj).
4. Adicionar uma coluna IconColumn (boolean) na tabela de listagem
   também.
5. Traduções via __() nos dois idiomas, seguindo a convenção já usada.

Ao final, rode ddev artisan migrate, ddev artisan optimize:clear e
ddev artisan filament:assets. Teste via tinker marcar uma categoria
existente como e_cliente=true e confirme a persistência.
