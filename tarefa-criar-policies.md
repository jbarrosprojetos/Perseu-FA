Investigação confirmada por teste real: um usuário com role "Comercial"
(sem NENHUMA permissão marcada na seção "Pessoas") consegue acessar,
criar e editar CategoriaPessoa, PessoaFisica e PessoaJuridica
livremente — as permissões configuradas na tela de Funções não estão
sendo respeitadas.

Investigue primeiro: confirme se plugins/perseu/pessoas tem alguma
classe de Policy (deveria estar em src/Policies/, seguindo o mesmo
padrão de outro plugin existente, ex: plugins/webkul/website/src/Policies/
ou plugins/webkul/partners/src/Policies/). Minha suspeita é que NENHUMA
Policy foi criada para os models deste plugin, e por isso o Laravel
libera acesso por padrão (sem Policy = sem restrição).

Se confirmado que não há Policies:

1. Crie CategoriaPessoaPolicy, PessoaFisicaPolicy e PessoaJuridicaPolicy
   em plugins/perseu/pessoas/src/Policies/, seguindo EXATAMENTE o
   mesmo padrão estrutural de uma Policy já existente no projeto (os
   métodos viewAny, view, create, update, delete, deleteAny, restore,
   restoreAny, forceDelete, forceDeleteAny, reorder — verificando as
   permissões via $user->can('acao_pessoas_entidade'), no formato de
   chave já documentado no CLAUDE.md/AUDITORIA-ESTRUTURA.md:
   {acao}_plugin_entidade).

2. Registre essas Policies no PessoasServiceProvider (ou onde for o
   local correto, seguindo o padrão de outro plugin), mapeando cada
   Model à sua Policy correspondente.

3. Rode ddev artisan shield:generate --all novamente (painel admin)
   para garantir que as permissões já existentes no banco continuam
   consistentes com essas novas Policies.

## Validação obrigatória

1. Rode ddev artisan optimize:clear
2. Teste via tinker: simule a verificação de permissão para um usuário
   com role "Comercial" (sem nada marcado em Pessoas) contra a policy
   de PessoaFisica (ex: $user->can('view_any_pessoas_pessoa_fisica')
   ou o nome exato da permissão gerada) — deve retornar false.
3. Ao final, oriente exatamente os passos para eu testar na tela de
   verdade com o usuário "zeman" (role Comercial): ele deveria agora
   ser IMPEDIDO de ver "Pessoas" no menu e bloqueado se tentar acessar
   a URL diretamente.

Me relate o resultado, incluindo se a suspeita sobre a ausência de
Policies foi confirmada.
