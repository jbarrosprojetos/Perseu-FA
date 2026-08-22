Na tela de Configurações > Funções > Editar (Shield RoleResource), os
nomes das ações de permissão continuam em inglês: "View Any", "View",
"Create", "Update", "Delete", "Delete Any", "Restore", "Restore Any",
"Force Delete", "Force Delete Any" — mesmo com "Marcar todos"/
"Desmarcar todos" já corretos em português.

Investigue de onde vêm exatamente esses labels de ação (provavelmente
do pacote bezhansalleh/filament-shield, em algum arquivo de tradução
própria dele, ou gerados dinamicamente a partir do nome do método da
Policy sem passar por tradução alguma).

Verifique se existe uma pasta lang/pt_BR já publicada/disponível para
esse pacote (vendor/bezhansalleh/filament-shield/resources/lang/pt_BR,
que a auditoria original já tinha encontrado existir) e se ela cobre
esses labels de ação especificamente. Se cobrir, mas não estiver
sendo aplicada, identifique por que (falta publicar via vendor:publish,
falta de override em lang/vendor/, ou outro motivo).

Se a tradução pt_BR do pacote não cobrir esses labels específicos (ou
não existir), crie um override em lang/vendor/filament-shield/pt_BR/
(mesmo padrão já usado para outros pacotes vendor no projeto, como
filament-panels), traduzindo:
- View Any -> Visualizar Todos (ou "Ver Todos", use o termo mais
  natural)
- View -> Visualizar
- Create -> Criar
- Update -> Atualizar
- Delete -> Excluir
- Delete Any -> Excluir Todos
- Restore -> Restaurar
- Restore Any -> Restaurar Todos
- Force Delete -> Excluir Permanentemente
- Force Delete Any -> Excluir Todos Permanentemente
- Reorder -> Reordenar (se aplicável)

Ao final, rode ddev artisan optimize:clear e ddev artisan filament:assets,
e confirme via HTML renderizado que os labels aparecem traduzidos.
