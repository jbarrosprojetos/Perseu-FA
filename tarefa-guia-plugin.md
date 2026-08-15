Preciso de um guia de referência sobre como criar plugins neste projeto
AureusERP. Baseando-se no código de 2-3 plugins já existentes (sugiro
"contacts" e "products" como exemplos) e nas convenções observadas,
documente o passo a passo completo:

1. Estrutura de pastas esperada
2. Classe Plugin (o que precisa implementar)
3. Service Provider (o que precisa registrar)
4. Como registrar em bootstrap/providers.php
5. Estrutura de migrations, models e Filament Resources dentro do plugin
6. Comando de instalação (artisan {nome}:install)
7. Como registrar permissões via Filament Shield para as entidades do
   plugin (baseando-se no formato descrito em AUDITORIA-ESTRUTURA.md)

Salve isso em um arquivo GUIA-CRIACAO-PLUGIN.md na raiz do projeto.

Depois, atualize o arquivo CLAUDE.md, adicionando este guia à lista de
"Antes de qualquer tarefa de código, consulte" (se ainda não estiver lá).
