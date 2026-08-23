Ajustes de texto e tipografia no formulário de ProjetoResource:

## 1. Labels de texto

- numero_projeto: label muda de "Número do Projeto" para apenas
  "Projeto" (pt_BR) e ajustar equivalente em en
- data_cadastro: label muda de "Data de Cadastro" para "Cadastrado
  em:" (pt_BR) e ajustar equivalente em en
- Radio tipo_contratante: reverter as opções de "Física"/"Jurídica"
  de volta para o texto completo "Pessoa Física"/"Pessoa Jurídica"

## 2. Tipografia dos labels (Placeholder) não bate com os demais campos

Os labels de numero_projeto, revisao, data_cadastro, contato_email e
contato_telefone (todos implementados como Placeholder, campos
somente leitura) estão com uma tipografia/peso visual diferente dos
labels de campos editáveis normais (ex: "Nome da Obra", "Tipo de
Projeto"). Investigue a causa (Placeholder pode renderizar o label
com uma classe CSS diferente de TextInput/Select) e corrija para que
a tipografia do label seja visualmente idêntica em todos os campos,
editáveis ou não.

## 3. Espaçamento entre as opções do Radio (Pessoa Física / Pessoa
Jurídica)

Aumente o espaço entre as duas opções do Radio em pelo menos o
equivalente a 2 caracteres de largura (considerando também que o
texto de cada opção ficou mais longo agora, no item 1 acima).
Investigue a opção correta do componente Radio do Filament para
controlar esse espaçamento entre opções inline (pode ser um método
próprio do componente, ou via CSS customizado se não houver método
nativo).

## Validação

1. Rode ddev artisan optimize:clear e ddev artisan filament:assets
2. Confirme via HTML renderizado os novos textos e a tipografia
   equalizada
3. Teste a submissão do formulário para garantir que nada quebrou

Me relate o resultado e a causa raiz da diferença de tipografia
encontrada.
