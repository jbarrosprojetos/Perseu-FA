Releia CLAUDE.md e GUIA-CRIACAO-PLUGIN.md antes de começar. Use o
plugin plugins/perseu/pessoas como referência estrutural direta (é o
mesmo padrão de organização, incluindo a convenção de namespace
próprio "Perseu\*", já validado e funcionando).

## Objetivo desta fase (Fase 1 — apenas fundação, SEM telas Filament)

Criar o plugin "Comercial" em plugins/perseu/comercial (namespace PHP
Perseu\Comercial). Nesta fase, criar APENAS: estrutura base do plugin,
migrations, models com relacionamentos, e o serviço de geração do
número de projeto. NÃO criar Filament Resources/Pages ainda.

Este plugin depende de models do plugin Pessoas já existente
(Perseu\Pessoas\Models\PessoaFisica, PessoaJuridica, Endereco) — não é
necessário nenhuma dependência especial no composer.json além do
autoload PSR-4 próprio; apenas referencie as classes diretamente nos
relacionamentos.

## 1. Estrutura base do plugin

Mesmo padrão de plugins/perseu/pessoas:
- plugins/perseu/comercial/composer.json (name: perseu/comercial,
  autoload PSR-4 "Perseu\\Comercial\\" -> "src/")
- plugins/perseu/comercial/src/ComercialServiceProvider.php
- plugins/perseu/comercial/src/ComercialPlugin.php
- Registrar em bootstrap/providers.php
- Comando de instalação comercial:install

## 2. Migrations, em plugins/perseu/comercial/database/migrations/

a) situacoes_projeto: id, descricao (string, not null), timestamps

b) tipos_projeto: id, codigo (string, length 1, not null, unique),
   descricao (string, not null), timestamps

c) projeto_numero_sequencias (tabela de controle, NÃO exposta em
   nenhuma tela — só uso interno do gerador de número): id, ano
   (unsignedSmallInteger), tipo_projeto_id (FK constrained), 
   ultimo_sequencial (unsignedInteger, default 0), timestamps.
   Adicionar unique(['ano', 'tipo_projeto_id']).

d) projetos: id, pessoa_fisica_id (FK nullable, constrained a
   perseu_pessoas.pessoas_fisicas — confirme o nome exato da tabela),
   pessoa_juridica_id (FK nullable, constrained a pessoas_juridicas),
   contato_pessoa_fisica_id (FK nullable, constrained a
   pessoas_fisicas — este é o "contato principal" registrado para o
   projeto, sem regra de validação de cargo, apenas referência),
   tipo_projeto_id (FK constrained, not null), endereco_id (FK
   nullable, constrained a enderecos), descricao (string, not null —
   nome da obra), revisao (unsignedInteger, default 0), numero_projeto
   (string, unique, not null — formato AATNNNN, ex: "2610001"),
   data_cadastro (dateTime, not null), timestamps, softDeletes

e) projeto_situacao (pivot): projeto_id (FK cascade on delete),
   situacao_projeto_id (FK cascade on delete), chave primária composta

## 3. Service de geração do número do projeto

Criar plugins/perseu/comercial/src/Services/GeradorNumeroProjeto.php,
com um método estático (ou instância) gerar(int $ano, TipoProjeto
$tipo): string que:

1. Abre uma transação de banco (DB::transaction)
2. Busca (ou cria, se não existir) a linha correspondente em
   projeto_numero_sequencias para (ano, tipo_projeto_id), usando
   lockForUpdate() para evitar condição de corrida entre usuários
   simultâneos
3. Incrementa ultimo_sequencial em 1 e salva
4. Retorna a string formatada: 2 dígitos do ano (ex: 26 para 2026) +
   código do tipo (1 caractere) + sequencial com 4 dígitos e zeros à
   esquerda (ex: "2610001" para ano=2026, tipo codigo="1",
   sequencial=1)

Escreva um teste simples via tinker (ou descreva o comando para eu
rodar) confirmando que gerar() duas vezes seguidas para o mesmo
ano/tipo retorna sequenciais consecutivos (0001, depois 0002), e que
um ano ou tipo diferente reinicia em 0001.

## 4. Models, em plugins/perseu/comercial/src/Models/

- SituacaoProjeto.php: $fillable completo; belongsToMany Projeto (via
  pivot projeto_situacao)
- TipoProjeto.php: $fillable completo; hasMany Projeto
- Projeto.php: $fillable completo (exceto numero_projeto e
  data_cadastro, que nunca devem ser preenchíveis diretamente pelo
  usuário — gerados automaticamente); casts (data_cadastro: datetime);
  SoftDeletes; relacionamentos: pessoaFisica() belongsTo
  Perseu\Pessoas\Models\PessoaFisica, pessoaJuridica() belongsTo
  Perseu\Pessoas\Models\PessoaJuridica, contatoPessoaFisica() belongsTo
  Perseu\Pessoas\Models\PessoaFisica (using 'contato_pessoa_fisica_id'),
  tipoProjeto() belongsTo TipoProjeto, endereco() belongsTo
  Perseu\Pessoas\Models\Endereco, situacoes() belongsToMany
  SituacaoProjeto.
  No evento "creating" do Model (boot method), preencher
  automaticamente numero_projeto (usando GeradorNumeroProjeto) e
  data_cadastro (now()), caso ainda não estejam preenchidos.

## 5. Validação final

1. composer dump-autoload
2. ddev artisan comercial:install
3. Confirme via SHOW TABLES que as 5 tabelas foram criadas
4. Confirme que o plugin aparece na tabela "plugins" do banco
5. Teste via tinker: criar um TipoProjeto (codigo="1", descricao=
   "Corporativo"), depois criar dois Projetos desse tipo no mesmo ano
   — confirme que os numero_projeto gerados são sequenciais e
   corretos, e que data_cadastro foi preenchida automaticamente
6. Não crie nenhuma tela Filament — isso fica para a Fase 2

Me relate os arquivos criados e o resultado dos testes.
