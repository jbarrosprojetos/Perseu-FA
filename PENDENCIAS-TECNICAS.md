# Pendências Técnicas Gerais

> Diferente de `PENDENCIAS-INTEGRACOES.md` (só APIs externas), este
> documento registra decisões de escopo/arquitetura adiadas de
> propósito — coisas identificadas durante uma tarefa mas que só fazem
> sentido resolver quando outra peça do sistema existir. Ver `CLAUDE.md`
> para as convenções gerais do projeto.

## Reversão de processos/transações de negócio complexos — fora de escopo da Lixeira Central

Registrado durante a implementação da Lixeira Central (Configurações →
Lixeira, 2026-08-29), que agrega Restaurar/Excluir Permanentemente de
registros com `SoftDeletes` (Obra, Pessoa Jurídica, Pessoa Física).

**Isso é um problema DIFERENTE** de reverter um PROCESSO de negócio já
concluído que envolve múltiplos módulos — exemplo citado na tarefa:
cancelar um pedido de compra já recebido, o que exigiria reverter
entrada de estoque + contas a pagar + qualquer outro efeito colateral
já disparado por aquele processo. Uma Lixeira de registro excluído
(soft delete de UM registro, restaurado como estava) não é o mecanismo
certo pra isso — estorno/cancelamento de transação de negócio é
tipicamente uma AÇÃO PRÓPRIA do próprio módulo de processo (ex.: um
botão "Cancelar Pedido" no pedido de compra, que sabe quais efeitos
colaterais desfazer e em que ordem), não uma operação genérica de
"restaurar registro apagado".

**Não implementado, e não deve ser confundido com a Lixeira Central
quando os módulos de Estoque/Compras/Financeiro forem criados** — só
fará sentido desenhar isso quando esses módulos existirem de verdade
(hoje nenhum dos três existe no projeto). Quando chegar a hora,
avaliar como um problema de "estorno/cancelamento de transação"
dentro do próprio módulo de Compras (ou onde o processo mora), não
como uma expansão da Lixeira Central de registros soft-deleted.
