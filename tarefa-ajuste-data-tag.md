Dois ajustes no formulário de ProjetoResource:

## 1. "Cadastrado em" não deve mostrar o horário

O Placeholder de data_cadastro atualmente exibe data e hora (ex:
"22/08/2026 18:25"). Ajuste para exibir SOMENTE a data (ex:
"22/08/2026"), sem o horário. O valor no banco (dateTime) continua
guardando data+hora normalmente - é só a formatação de exibição que
muda.

## 2. Tipografia do badge/tag em Situações está menor que o resto

O campo situacoes (Select multiple, exibindo os valores selecionados
como badges/tags, ex: "Negociação") está com o texto dentro do badge
visualmente menor que a tipografia padrão dos outros campos do
formulário. Investigue a classe CSS usada pelos badges/chips de um
Select multiple (procure algo relacionado a "choice" ou "tag" no
Filament, provavelmente da biblioteca Choices.js usada internamente),
e verifique se o Bonsai está sobrescrevendo o tamanho de fonte desse
elemento especificamente (mesmo padrão de investigação já usado nas
tarefas anteriores desta sessão). Corrija para igualar ao tamanho de
fonte padrão dos demais campos (13px, mesmo valor já usado nas
correções anteriores).

Ao final, rode ddev artisan optimize:clear e ddev artisan filament:assets.
Confirme via HTML/CSS renderizado as duas correções.
