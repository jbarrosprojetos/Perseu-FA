## 1. Corrigir truncamento de campos compactos

Os campos com max-width calculado em "ch" estão cortando conteúdo:
- Data de Nascimento: "10/11/1971" aparece cortado como "10/11/197"
  (falta espaço para o ícone de calendário nativo do navegador)
- Sexo: "Masculino" aparece cortado como "Mascul" (falta espaço para
  a seta nativa do dropdown)

No trait HasCompactFieldWidth, aumente a folga (slack) especificamente
para:
- Campos do tipo DatePicker: slack adicional de pelo menos +4ch (além
  do +2 padrão), para acomodar o ícone de calendário nativo
- Campos do tipo Select: slack adicional de pelo menos +3ch (além do
  +2 padrão), para acomodar a seta nativa do dropdown

Ajuste os valores de chars/extraSlack no PessoaFisicaResource
(Data de Nascimento, Estado Civil, Sexo) de acordo, e teste
renderizando o HTML real para confirmar que o texto completo caberia
(meça a largura calculada vs. o comprimento do texto + ícone estimado).

## 2. Adicionar validação real de CPF (dígito verificador)

O campo CPF em PessoaFisicaResource tem apenas máscara de formato,
sem validar se o CPF é matematicamente válido (algoritmo padrão
brasileiro de dígito verificador, módulo 11).

Implemente essa validação:
1. Crie uma regra de validação reutilizável (Rule customizada do
   Laravel, ex: app/Rules/CpfValido.php ou dentro do próprio plugin em
   plugins/perseu/pessoas/src/Rules/CpfValido.php) implementando o
   algoritmo padrão de validação de CPF (verificar os dois dígitos
   verificadores, rejeitar sequências repetidas tipo 111.111.111-11).
2. Aplique essa regra ao campo CPF do formulário via ->rule() ou
   ->rules([...]), com uma mensagem de erro traduzida ("CPF inválido")
   em pt_BR e en.
3. Essa regra deve ser reutilizável para o campo CNPJ de Pessoa
   Jurídica no futuro (crie também, na mesma pasta Rules, uma
   CnpjValido seguindo o mesmo princípio, para reaproveitar depois,
   mesmo que ainda não seja aplicada agora).

Ao final, rode ddev artisan optimize:clear e ddev artisan filament:assets.
Teste especificamente: um CPF com dígito verificador errado deve
mostrar mensagem de erro ao tentar salvar.
