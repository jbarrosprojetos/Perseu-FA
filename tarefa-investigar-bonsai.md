Investigação: o gap: 6rem aplicado via style inline na tag <form>
(HasRelationManagerDividers) não está produzindo efeito visual —
suspeita-se que o tema qalainau/bonsai-theme (instalado e ativo neste
projeto) esteja sobrescrevendo esse espaçamento, possivelmente via
regras CSS com !important voltadas para compactar o painel.

Investigue o CSS do pacote Bonsai (procure em
vendor/qalainau/bonsai-theme, ou no CSS publicado/compilado usado
pelo tema) por regras relacionadas a "gap" (ex: .fi-sc-form, gap-*,
ou seletores genéricos de espaçamento) que usem !important ou alta
especificidade que possam estar vencendo o style inline aplicado.

Se encontrar a causa, corrija de uma das formas:
1. Se for possível aumentar a especificidade/prioridade do nosso
   estilo (ex: usar !important também no nosso style inline, como
   exceção pontual e documentada), aplique isso.
2. Se o Bonsai tiver alguma opção de configuração para excluir
   elementos específicos da compactação, considere essa rota.
3. Se nenhuma das opções acima for viável de forma limpa, relate a
   descoberta e não aplique nenhuma correção ainda — deixe para
   decidirmos juntos.

Ao final (se corrigir), rode ddev artisan optimize:clear e
ddev artisan filament:assets, e confirme via HTML/CSS renderizado que
o espaçamento agora é visualmente efetivo.
