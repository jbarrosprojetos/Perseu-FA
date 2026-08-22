Testei manualmente: ativar/desativar a permissão "Pessoas Cluster" (uma
permissão de Página, visível em Configurações > Funções > aba Páginas)
para o papel "Comercial" não produz NENHUMA diferença no comportamento
do usuário zeman (associado a esse papel) — o item "Pessoas" continua
aparecendo no menu independente do estado dessa permissão.

Investigue plugins/perseu/pessoas/src/Filament/Clusters/PessoasCluster.php:
1. A classe usa a trait HasPageShield (mesmo mecanismo que já corrigimos
   antes na página de Ajuda, lá no AureusERP original)? Minha suspeita é
   que NÃO, e por isso a permissão existe no banco mas nunca é
   efetivamente checada.
2. Confirme comparando com outro Cluster existente no sistema (ex:
   Settings, em plugins/webkul/security ou support) que já tenha esse
   comportamento funcionando corretamente — como esse Cluster faz a
   checagem de permissão funcionar de verdade?

Se confirmado que falta HasPageShield (ou mecanismo equivalente),
corrija adicionando à PessoasCluster.php, seguindo exatamente o mesmo
padrão do Cluster de referência.

## Validação obrigatória

1. Rode ddev artisan optimize:clear
2. Teste: com a permissão "Pessoas Cluster" desmarcada para o papel
   Comercial, confirme (via tinker, simulando o usuário zeman) que
   $user->can('page_pessoas_...') [ou o nome exato da permissão gerada]
   é false, e que isso agora realmente impede o acesso/visualização do
   Cluster.
3. Me dê o passo a passo exato para eu confirmar na tela real: com a
   permissão desmarcada, o item "Pessoas" deve sumir do menu do zeman.

Relate a causa raiz confirmada.
