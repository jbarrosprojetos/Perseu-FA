Antes de começar, releia GUIA-CRIACAO-PLUGIN.md e AUDITORIA-ESTRUTURA.md
(já referenciados no CLAUDE.md). Use como modelo de referência a
estrutura de um plugin simples já existente (ex: plugins/webkul/partners
ou plugins/webkul/products) para os arquivos de ServiceProvider e Plugin
class.

## Objetivo desta fase (Fase 1 — apenas fundação, SEM telas Filament)

Criar o plugin "Pessoas" em plugins/perseu/pessoas (namespace PHP
Perseu\Pessoas), seguindo a convenção de namespace próprio documentada
no GUIA-CRIACAO-PLUGIN.md. Nesta fase, criar APENAS: estrutura base do
plugin, migrations, enums, e models com relacionamentos. NÃO criar
Filament Resources, Pages nem Relation Managers ainda (isso é a Fase 2
e 3, feitas depois, em prompts separados).

## 1. Estrutura base do plugin

- plugins/perseu/pessoas/composer.json (name: perseu/pessoas, autoload
  PSR-4 "Perseu\\Pessoas\\" -> "src/")
- plugins/perseu/pessoas/src/PessoasServiceProvider.php — estende
  Webkul\PluginManager\PackageServiceProvider, com configureCustomPackage()
  (NÃO marcar como core), e packageRegistered() registrando o plugin
  Filament no painel admin
- plugins/perseu/pessoas/src/PessoasPlugin.php — implementa o contrato
  de Plugin do Filament (getId, register, boot), seguindo o mesmo
  padrão de um plugin existente
- Registrar PessoasServiceProvider em bootstrap/providers.php
- Comando de instalação pessoas:install (mesmo padrão dos outros
  plugins — roda migrations e sincroniza)

## 2. Enums (PHP backed enums, int-backed), em
plugins/perseu/pessoas/src/Enums/

- EstadoCivil: Solteiro=1, Casado=2, Divorciado=3, Viuvo=4,
  UniaoEstavel=5
- Sexo: Masculino=1, Feminino=2, Outro=3
- RegimeTributario: SimplesNacional=1, LucroPresumido=2, LucroReal=3,
  Mei=4
- TipoEndereco: Residencial=1, Comercial=2, Cobranca=3, Obra=4,
  Entrega=5, Outro=6

Cada enum deve ter um método getLabel() retornando o texto via __(),
com chaves criadas em lang/en e lang/pt_BR do plugin (ex:
pessoas::enums.estado-civil.casado). Os enums devem implementar a
interface HasLabel do Filament (Filament\Support\Contracts\HasLabel)
para funcionar automaticamente em Select/Radio do Filament nas fases
seguintes.

## 3. Migrations, em plugins/perseu/pessoas/database/migrations/

a) categorias_pessoa: id, descricao (string), aplica_pf (boolean,
   default false), aplica_pj (boolean, default false), timestamps

b) pessoas_fisicas: id, nome (string, NOT NULL), telefone (string, NOT
   NULL), telefone_whatsapp (boolean, default false), email (string,
   nullable), data_nascimento (date, nullable), rg (string, nullable),
   cpf (string, nullable, unique), estado_civil (unsignedTinyInteger,
   nullable), sexo (unsignedTinyInteger, nullable), profissao (string,
   nullable), observacoes (text, nullable), timestamps, softDeletes

c) pessoas_juridicas: id, razao_social (string, nullable), nome_fantasia
   (string, NOT NULL), cnpj (string, nullable, unique), inscricao_estadual
   (string, nullable), cnae (string, nullable), regime_tributario
   (unsignedTinyInteger, nullable), data_abertura (date, nullable), email
   (string, nullable), telefone (string, NOT NULL), observacoes (text,
   nullable), timestamps, softDeletes

d) enderecos: id, cep (string, nullable), uf (string, length 2,
   nullable), municipio (string, nullable), bairro (string, nullable),
   logradouro (string, nullable), numero (string, nullable), complemento
   (string, nullable), timestamps

e) pessoa_fisica_categoria (pivot): pessoa_fisica_id (FK, cascade on
   delete), categoria_pessoa_id (FK, cascade on delete), chave primária
   composta pelos dois

f) pessoa_juridica_categoria (pivot): pessoa_juridica_id (FK, cascade
   on delete), categoria_pessoa_id (FK, cascade on delete), chave
   primária composta

g) pessoa_fisica_endereco (pivot com atributos extras, id próprio):
   id, pessoa_fisica_id (FK, cascade on delete), endereco_id (FK,
   cascade on delete), tipo (unsignedTinyInteger), principal (boolean,
   default false), timestamps

h) pessoa_juridica_endereco (mesmo padrão de g, para pessoa_juridica_id)

i) contatos: id, pessoa_fisica_id (FK, cascade on delete),
   pessoa_juridica_id (FK, cascade on delete), cargo (string,
   nullable), timestamps

## 4. Models, em plugins/perseu/pessoas/src/Models/

- CategoriaPessoa.php: $fillable completo; relacionamentos
  pessoasFisicas() e pessoasJuridicas() (belongsToMany via as pivots
  correspondentes)
- PessoaFisica.php: $fillable completo; casts (telefone_whatsapp:
  boolean, data_nascimento: date, estado_civil: EstadoCivil::class,
  sexo: Sexo::class); usar SoftDeletes; relacionamentos: categorias()
  belongsToMany CategoriaPessoa, enderecos() belongsToMany Endereco
  ->withPivot('tipo', 'principal')->withTimestamps(), contatos()
  hasMany Contato
- PessoaJuridica.php: mesmo padrão de PessoaFisica, com casts
  (regime_tributario: RegimeTributario::class, data_abertura: date);
  relacionamentos categorias(), enderecos(), contatos() hasMany Contato
- Endereco.php: $fillable completo; relacionamentos pessoasFisicas() e
  pessoasJuridicas() belongsToMany (via os pivots correspondentes,
  ->withPivot('tipo','principal')->withTimestamps())
- Contato.php: $fillable completo; belongsTo PessoaFisica, belongsTo
  PessoaJuridica

## 5. Validação final

Depois de criar tudo:
1. Rode composer dump-autoload
2. Rode ddev artisan pessoas:install (ou equivalente comando de
   instalação criado)
3. Confirme via ddev mysql -e "SHOW TABLES;" que todas as 9 tabelas
   foram criadas
4. Confirme que o plugin aparece na tabela "plugins" do banco
   (ou rode a sincronização necessária)
5. Não crie nenhuma tela Filament (Resource/Page/RelationManager) —
   isso fica para as próximas fases

Me relate: quais arquivos foram criados, o resultado da instalação, e
se o plugin apareceu corretamente na tela de Módulos como instalado.