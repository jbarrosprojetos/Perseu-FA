Faça uma varredura SOMENTE no plugin "security"
(plugins/webkul/security), procurando o mesmo problema identificado
no UserResource: Filament Resources que não definem $modelLabel,
$pluralModelLabel nem getNavigationLabel() customizado, e por isso o
Filament gera o rótulo automaticamente a partir do nome da classe PHP
(sempre em inglês, ex: "User" -> "Users", "Company" -> "Companies",
"Team" -> "Teams", "Role" -> "Roles").

NÃO inclua os plugins "support" (página de Ajuda, que será tratada
separadamente) nem "plugin-manager" (tela de Plugins) nesta varredura.

Para cada Resource encontrado nessa situação, dentro do plugin security:
1. Liste o arquivo e o nome do recurso
2. Corrija diretamente, usando __() com uma chave de tradução
   apropriada, criando a chave em lang/en e lang/pt_BR do plugin
   security se ainda não existir

Como o volume esperado aqui é pequeno (poucos Resources dentro de um
único plugin), pode corrigir direto, sem precisar só documentar antes.

Ao final, rode ddev artisan optimize:clear e liste os arquivos alterados.
