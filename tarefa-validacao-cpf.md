## Objetivo: validação real de CPF (dígito verificador)

O campo CPF em PessoaFisicaResource tem apenas máscara de formato
(999.999.999-99), sem validar se o CPF é matematicamente válido.

## 1. Criar a regra de validação

Crie plugins/perseu/pessoas/src/Rules/CpfValido.php, implementando o
algoritmo padrão brasileiro de validação de CPF:
- Remover caracteres não numéricos antes de validar
- Rejeitar CPFs com todos os dígitos iguais (ex: 111.111.111-11,
  000.000.000-00)
- Calcular e validar os dois dígitos verificadores pelo algoritmo
  padrão (módulo 11)
- Implementar a interface de Rule do Laravel (Illuminate\Contracts\
  Validation\ValidationRule ou equivalente da versão do Laravel usada
  no projeto)

## 2. Aplicar ao campo CPF

No PessoaFisicaResource, aplique essa regra ao campo cpf via ->rule()
ou ->rules([new CpfValido()]), mantendo a máscara já existente.
Mensagem de erro traduzida: "CPF inválido" em pt_BR, "Invalid CPF" em
en (criar as chaves necessárias nos arquivos de idioma do plugin).

## 3. Criar também a regra de CNPJ (para uso futuro em Pessoa Jurídica)

Crie plugins/perseu/pessoas/src/Rules/CnpjValido.php, seguindo o mesmo
princípio (algoritmo padrão de validação de CNPJ, módulo 11 com pesos
específicos, rejeitar sequências repetidas). Não precisa ser aplicada
a nenhum campo ainda (Pessoa Jurídica ainda não foi criada) — só
deixar pronta para reaproveitar depois.

## 4. Testes

Escreva um teste (ou valide via tinker, já que os testes PHPUnit do
projeto têm um problema de bootstrap pré-existente que você já
encontrou antes) confirmando:
- Um CPF válido conhecido (ex: gerar um matematicamente válido, ou usar
  111.444.777-35, que é um CPF de teste comumente usado e válido)
  passa na validação
- Um CPF com dígito verificador errado (ex: 159.559.438-89 alterando
  o último dígito) é rejeitado
- Um CPF com todos os dígitos iguais (111.111.111-11) é rejeitado

Ao final, rode ddev artisan optimize:clear e ddev artisan filament:assets.
Relate os resultados dos testes.
