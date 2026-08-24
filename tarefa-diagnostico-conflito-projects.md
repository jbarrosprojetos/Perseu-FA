Preciso tentar instalar o plugin plugins/webkul/projects (código já
existe fisicamente no projeto, só não está ativo) neste projeto
Perseu-FA, e capturar com precisão qual conflito acontece com nosso
plugin plugins/perseu/comercial (que também tem um conceito de
"Projeto").

Rode:
ddev artisan projects:install

Capture a mensagem de erro completa, se houver. Se a instalação
"aparentar" funcionar mas gerar sobreposição visual (ex: dois itens
de menu parecidos, rota duplicada, classe com mesmo nome, conflito de
slug/rota), identifique exatamente onde:

1. Existe colisão de nome de tabela no banco (ex: alguma tabela do
   projects colide com alguma nossa)?
2. Existe colisão de nome de classe/namespace?
3. Existe colisão de rota (slug da URL) entre o Cluster/Resource deles
   e o nosso ProjetoResource?
4. Existe colisão de label de navegação (os dois aparecendo com o
   mesmo texto "Projetos" no menu, mesmo sendo coisas diferentes)?

NÃO corrija nada ainda - apenas diagnostique e relate com precisão a
causa raiz exata do problema, para decidirmos o menor ajuste possível
(não necessariamente renomear o plugin inteiro).

Se a instalação gerar algum erro que impeça de prosseguir, eu já sei
que vamos reverter depois via backup, então pode tentar à vontade.
