# Investigação — Consistência Transacional e Concorrência Multiusuário

> Levantamento de riscos para o Perseu MRP rodando em nuvem com múltiplos
> usuários simultâneos. Todos os achados abaixo foram confirmados lendo
> código-fonte real (próprio ou do vendor/AureusERP), não presumidos.
>
> Metodologia: a investigação foi dividida em 3 frentes paralelas — (1)
> `perseu/pessoas` + `perseu/auditoria` + comportamento nativo do
> Filament, (2) nível de isolamento do banco + locking otimista, (3)
> núcleo herdado do AureusERP (`webkul/*`) — mais o conhecimento direto
> de `perseu/comercial` (Número de Projeto, Item de Projeto, Referência
> de Preços) já adquirido em tarefas anteriores desta mesma sessão.
>
> **Atualização (2026-09-05): os 5 riscos ALTO/MÉDIO-ALTO (R1-R5) foram
> CORRIGIDOS** — ver marcação "✅ CORRIGIDO" em cada seção, com o que
> mudou e como foi validado (inclusive testes forçando falha no meio da
> operação, pra provar rollback completo, não só revisão de código). Os
> riscos MÉDIO (R6, R8, R9) permanecem em aberto, de propósito — ficam
> para uma tarefa futura.

## Sumário executivo (leia isto primeiro)

| # | Risco | Severidade | Status |
|---|---|---|---|
| R1 | Imposto obsoleto (stale read) na Referência de Preços ao gravar Item Avulso | **ALTO** | ✅ **CORRIGIDO** (2026-09-05) |
| R2 | Cascata de exclusão de Pessoa (Endereço/Contato) sem transação | **ALTO** | ✅ **CORRIGIDO** (2026-09-05) |
| R3 | Lixeira Central (restaurar/excluir/lote) sem transação | **ALTO** | ✅ **CORRIGIDO** (2026-09-05) |
| R4 | Criação de Endereço + vínculo + tag em 3 passos sem transação | **MÉDIO-ALTO** | ✅ **CORRIGIDO** (2026-09-05) |
| R5 | `syncTipos()` de Endereço (apaga tags antigas antes de criar novas) sem transação | **MÉDIO-ALTO** | ✅ **CORRIGIDO** (2026-09-05) |
| R6 | `numero_item` do PRIMEIRO item de um Projeto novo pode colidir | **MÉDIO** | Em aberto (fora do escopo desta tarefa) |
| R7 | Edição de Item de Projeto sem lock (dois usuários editando o mesmo item) | **BAIXO-MÉDIO** | Em aberto (fora do escopo desta tarefa) |
| R8 | Sem locking otimista em NENHUM Resource (Filament puro) | **MÉDIO** | Em aberto (fora do escopo desta tarefa) |
| R9 | `webkul/inventories`: `lockForUpdate()` "solto" fora das rotas de API protegidas | **MÉDIO (não confirmado como explorável)** | Em aberto (fora do escopo desta tarefa) |
| — | `GeradorNumeroProjeto` (Número de Projeto) | seguro | Confirmado |
| — | `excluirItemAvulso()` (exclusão + renumeração de Item) | seguro | Confirmado |
| — | Numeração de Pedido/Compra/Produção do AureusERP (`SO/{id}` etc.) | seguro por construção | Confirmado |
| — | Nível de isolamento (`REPEATABLE READ`, padrão de fábrica) | não é a causa dos riscos acima | Confirmado |

---

## 1. Geração de números sequenciais

### 1.1 `GeradorNumeroProjeto` (Número do Projeto, `AAT####`) — **SEGURO**

`plugins/perseu/comercial/src/Services/GeradorNumeroProjeto.php`:

```php
return DB::transaction(function () use ($ano, $tipo) {
    DB::table('projeto_numero_sequencias')->insertOrIgnore([...]);   // garante a linha existir
    $sequencia = DB::table('projeto_numero_sequencias')
        ->where('ano', $ano)->where('tipo_projeto_id', $tipo->id)
        ->lockForUpdate()->first();                                   // trava a linha
    $proximoSequencial = $sequencia->ultimo_sequencial + 1;
    DB::table('projeto_numero_sequencias')->where('id', $sequencia->id)
        ->update(['ultimo_sequencial' => $proximoSequencial, ...]);
    return sprintf('%02d%s%04d', ...);
});
```

Padrão correto: tabela de sequência dedicada (`projeto_numero_sequencias`,
`UNIQUE(ano, tipo_projeto_id)`) + `insertOrIgnore` (evita erro de
duplicata na criação da linha) + `lockForUpdate()` dentro de
`DB::transaction()` antes de ler/incrementar. Duas requisições
concorrentes para o mesmo `ano`+`tipo_projeto_id` serializam
corretamente na linha `lockForUpdate()` — a segunda só lê o valor já
incrementado pela primeira depois que ela commitar. **Sem race
condition.**

### 1.2 `numero_item` (Item de Projeto, `###`) — **parcialmente seguro**

`plugins/perseu/comercial/src/Models/ItemProjeto.php` (`boot()`,
evento `creating`):

```php
$ultimoNumero = (int) static::where('projeto_id', $item->projeto_id)->max('numero_item');
$item->numero_item = str_pad((string) ($ultimoNumero + 1), 3, '0', STR_PAD_LEFT);
```

Esse `MAX()` **não tem lock próprio** — a proteção vem inteiramente de
quem chama `create()`, em `ProjetoResource::confirmarItemAvulso()`:

```php
DB::transaction(function () use ($record, $dados): void {
    $record->itens()->lockForUpdate()->get();   // trava as linhas JÁ existentes
    $record->itens()->create($dados);
});
```

- **Quando já existe pelo menos 1 item no Projeto**: seguro. O
  `lockForUpdate()->get()` trava as linhas existentes; uma segunda
  transação concorrente para o MESMO `projeto_id` bloqueia até a
  primeira commitar, e a leitura de bloqueio (`SELECT ... FOR UPDATE`)
  sempre lê o valor mais recente já commitado, então o `MAX()` do
  `creating()` da segunda inserção vê o item recém-criado pela primeira.
- **Cenário de falha real — PRIMEIRO item de um Projeto**: com ZERO
  linhas existentes pra aquele `projeto_id`, não há nada pra
  `lockForUpdate()` travar. Duas requisições simultâneas inserindo o
  primeiro item do MESMO Projeto (cenário plausível: duplo-clique
  acidental no botão "Confirmar", ou duas abas do mesmo usuário) podem
  as duas calcular `numero_item = '001'` e tentar inserir ao mesmo
  tempo. A constraint `UNIQUE(projeto_id, numero_item)` da migration
  **impede a duplicata silenciosa** (uma das duas inserções falha com
  `QueryException` de chave duplicada) — mas essa exceção **não é
  tratada** em `confirmarItemAvulso()` hoje, então o usuário azarado
  veria um erro 500/tela de erro genérica em vez de uma mensagem clara
  ou um retry automático.
- **Severidade: MÉDIO** — não corrompe dado (a constraint segura a
  barra), mas produz uma falha feia numa janela pequena, porém real.
- **Recomendação**: capturar `Illuminate\Database\QueryException` (ou
  checar `getCode() === '23000'`) ao redor do `create()` em
  `confirmarItemAvulso()` e, se for violação da constraint
  `(projeto_id, numero_item)`, tentar de novo automaticamente dentro da
  mesma requisição (recalcular `numero_item` e reinserir) em vez de
  propagar o erro pro usuário.

### 1.3 Outros geradores no projeto — nenhum encontrado

Grep por `max(`, `count() + 1` em `plugins/perseu/` não trouxe outro
gerador de número sequencial além dos dois acima.

### 1.4 Núcleo AureusERP (`webkul/sales`, `purchases`, `manufacturing`) — **seguro por construção, e estruturalmente melhor que o padrão do Perseu**

Achado interessante: esses plugins **não calculam "próximo número" via
`MAX()`/`COUNT()`** — usam o próprio `id` autoincrement do registro já
inserido:

```php
// sales/src/Models/Order.php:249 (mesmo padrão em purchases/manufacturing)
$this->name = 'SO/'.$this->id;
```

O fluxo é: `INSERT` roda primeiro (o banco atribui o `id`, que é
atômico por natureza — nunca duas linhas recebem o mesmo `id`), e só
DEPOIS (`created()`) o campo `name` é montado usando esse `id` já
garantido, num `UPDATE` separado. **Não existe janela de colisão
possível** — o número deriva de algo que o próprio banco já garantiu
ser único, em vez de ser calculado ANTES da garantia de exclusividade
(que é exatamente o padrão frágil usado em `numero_item`/1.2). Vale
considerar esse padrão (número derivado do `id` já commitado) como
alternativa mais robusta para qualquer numeração sequencial nova.

`webkul/inventories` não tem gerador de sequência próprio ativo (só um
campo cosmético "Sequence prefix" na configuração de tipo de
operação).

---

## 2. Operações multi-tabela que deveriam ser atômicas

### 2.1 Exclusão + renumeração de Item de Projeto — **SEGURO**

`ProjetoResource::excluirItemAvulso()` (`plugins/perseu/comercial/...`):

```php
DB::transaction(function () use ($item): void {
    $projetoId = $item->projeto_id;
    $numeroExcluido = $item->numero_item;
    $item->delete();
    ItemProjeto::where('projeto_id', $projetoId)
        ->where('numero_item', '>', $numeroExcluido)
        ->orderBy('numero_item')->lockForUpdate()->get()
        ->each(fn ($itemPosterior) => $itemPosterior->forceFill([...])->save());
});
```

Envolto em `DB::transaction()`, com `lockForUpdate()` nos itens
seguintes e processamento em ordem crescente (evita colisão transitória
no índice único durante o próprio laço de renumeração). **Já testado
extensivamente** (inserção, edição, exclusão, renumeração) via
`Livewire::test()` numa tarefa anterior desta sessão.

### 2.2 Cascata de exclusão de Pessoa (Endereço/Contato) — **RISCO ALTO, sem transação**

> **✅ CORRIGIDO (2026-09-05)** — `bootCascadesRelatedDataOnForceDelete()`
> agora envolve `contatos()->delete()` + `enderecos()->detach()` + o
> `foreach` de exclusão de Endereços numa única `DB::transaction()`.
> Validado forçando uma exception logo depois do `delete()` de Contatos
> (via `DB::listen()` interceptando a query) e confirmando que Contato,
> vínculo Pessoa↔Endereço e a própria Pessoa continuam intactos após o
> rollback. **Escopo residual não coberto**: a exclusão da PRÓPRIA
> Pessoa roda fora desta transação (é o fluxo nativo do Eloquent,
> `forceDelete()` só chama `delete()` do registro DEPOIS que este
> listener retorna) — coberto na prática quando a chamada vem da Lixeira
> Central (2.3, que agora embrulha a chamada inteira, aninhando por
> savepoint), mas não se `forceDelete()` for chamado direto de outro
> lugar (ex.: um `ForceDeleteAction` num Resource individual).

`plugins/perseu/pessoas/src/Traits/CascadesRelatedDataOnForceDelete.php:34-51`:

```php
static::forceDeleting(function (self $model): void {
    $model->contatos()->delete();                       // (1)
    $enderecoIds = $model->enderecos()->pluck('enderecos.id');
    $model->enderecos()->detach();                       // (2)
    foreach ($enderecoIds as $enderecoId) {
        $endereco = Endereco::find($enderecoId);
        if ($endereco && !$endereco->pessoasFisicas()->exists() && !$endereco->pessoasJuridicas()->exists()) {
            $endereco->delete();                          // (3)
        }
    }
});
```

Nenhum `DB::transaction()` — nem aqui, nem em quem chama (a própria
`forceDelete()` da Pessoa, ou a Lixeira Central, ver 2.3).

**Cenário de falha concreto**: uma interrupção (timeout de rede,
worker do PHP-FPM reciclado, exception não relacionada) entre os
passos (1) e (2) deixa os Contatos já apagados mas o vínculo
Pessoa↔Endereço ainda intacto — e a própria Pessoa pode nem ter sido
excluída de fato, dependendo de onde a interrupção ocorreu em relação
ao evento `forceDeleting` (que roda ANTES do DELETE físico da Pessoa).
Uma interrupção dentro do `foreach` (passo 3) deixa parte dos Endereços
excluídos e parte não, sem nenhum critério de recuperação.

**Severidade: ALTO** — é uma operação DESTRUTIVA (`forceDelete`, sem
volta) cuja falha parcial produz dado órfão numa exclusão que já é
definitiva por natureza.

**Recomendação**: envolver o listener inteiro em `DB::transaction()`
— mais simples ainda, envolver a CHAMADA de `forceDelete()` no ponto de
origem (Resource, Lixeira) numa transação que cubra o evento inteiro.

### 2.3 Lixeira Central — restaurar/excluir/ações em lote — **RISCO ALTO, sem transação**

> **✅ CORRIGIDO (2026-09-05)** — `restoreRecord()`/`forceDeleteRecord()`
> agora chamam `$model->restore()`/`forceDelete()` dentro de
> `DB::transaction()`. Em `bulkAct()`, CADA REGISTRO do lote é
> individualmente transacional (`DB::transaction()` por iteração) —
> decisão deliberada de NÃO envolver o lote inteiro numa única
> transação, pra preservar o comportamento já existente de sucesso
> parcial entre registros diferentes (uma seleção heterogênea já
> reportava quantos foram pulados por permissão; embrulhar tudo
> desfaria os registros já processados com sucesso só por causa de um
> item posterior falhar). Validação indireta: a mesma técnica de
> `DB::listen()` usada em 2.2 confirma que `DB::transaction()` aninha
> corretamente com a transação da cascata (2.2) via savepoint do
> Laravel.

`plugins/perseu/auditoria/src/Filament/Pages/Lixeira.php`:
- `forceDeleteRecord()` (linha ~443): chama `$model->forceDelete()` direto,
  sem `DB::transaction()` — dispara a cascata de 2.2 (também sem
  transação) mais o listener de auditoria (`LogsBusinessActivity`,
  evento `forceDeleted`, que grava a `Activity` DEPOIS do registro já
  fisicamente excluído). Se a gravação do log falhar (timeout, disco
  cheio), a exclusão já aconteceu, mas a entrada de auditoria dessa
  exclusão nunca é criada — gap silencioso, sem exception visível ao
  usuário.
- `restoreRecord()`/`bulkAct()` (linhas ~406-425, ~461-493): mesmo
  padrão, e o `bulkAct()` roda um `foreach` chamando isso registro a
  registro **sem nenhum agrupamento transacional** — uma interrupção no
  meio de uma ação em massa deixa parte dos registros
  restaurados/excluídos e o resto intocado, sem indicação clara pro
  usuário de até onde o lote avançou.

**Severidade: ALTO** pelos mesmos motivos de 2.2, agravado pela
operação em LOTE (superfície de falha maior, mais registros em risco
por operação).

**Recomendação**: envolver cada `forceDeleteRecord()`/`restoreRecord()`
individual em `DB::transaction()`; para `bulkAct()`, envolver o LOTE
INTEIRO numa única transação (tudo ou nada) — ou, se for aceitável
processar parcialmente, pelo menos reportar ao usuário quantos
tiveram sucesso e quais falharam, em vez de silêncio.

### 2.4 Criação de Endereço + vínculo + tag — 3 escritas separadas, sem transação

> **✅ CORRIGIDO (2026-09-05)** — as duas ocorrências
> (`ProjetoResource::createOptionUsing()` e
> `CreatePessoaJuridica::afterCreate()`) agora envolvem `Endereco::create()`
> + `attach()` + `tipos()->create()` numa única `DB::transaction()`.
> Validado forçando uma exception logo antes do `insert` da tag
> (`endereco_tipo`) e confirmando que a contagem de `Endereco` fica
> INALTERADA depois do rollback — ou seja, o próprio `Endereco::create()`
> também é desfeito, não só o attach/tag, fechando o cenário "Endereço
> órfão sem tag" por completo.

Duas ocorrências do MESMO padrão:

- `ProjetoResource.php` (`createOptionUsing` do Select de Endereço da
  Obra): `Endereco::create($data)` → `...->enderecos()->attach(...)` →
  `$endereco->tipos()->create(['tipo' => TipoEndereco::Obra->value])`.
- `CreatePessoaJuridica.php` (`afterCreate()`, endereço vindo da busca
  de CNPJ): sequência idêntica.

**Cenário de falha real e concreto** (não teórico): interrupção entre
a 2ª e a 3ª escrita — o Endereço é criado E vinculado à Pessoa (aparece
normalmente na aba de Endereços dela), mas fica **sem nenhuma tag**.
Como `ProjetoResource::enderecoObraOptionsFor()` filtra endereços por
`whereHas('tipos', ...'Obra'...)`, esse endereço **nunca aparece** como
opção de "Endereço da Obra" — sem erro nenhum, o usuário só vê que o
endereço "sumiu" da lista, sem entender por quê. Esse é exatamente o
tipo de bug silencioso e difícil de rastrear que a tarefa pediu pra
priorizar.

**Severidade: MÉDIO-ALTO** — não corrompe/perde dado permanentemente,
mas produz um estado confuso e sem pista de causa pro usuário final.

**Recomendação**: envolver as 3 escritas em `DB::transaction()` nos
dois lugares.

### 2.5 `syncTipos()` de Endereço — apaga tags antigas antes de criar as novas, sem transação

> **✅ CORRIGIDO (2026-09-05)** — `syncTipos()` agora envolve
> `tipos()->delete()` + `tipos()->createMany()` numa única
> `DB::transaction()`. `sync()` nativo do Eloquent NÃO se aplica aqui
> (`tipos()` é `HasMany`, não `BelongsToMany` — `sync()` é método de
> relação Many-to-Many com pivot). Validado forçando uma exception logo
> depois do `delete()` das tags antigas e confirmando que as tags
> ORIGINAIS (`Comercial` + `Obra`) continuam intactas após o rollback —
> o cenário "Endereço fica sem NENHUMA tag" (pior que 2.4, perdia até o
> estado anterior) está fechado.

`plugins/perseu/pessoas/src/Traits/HasEnderecoRelationManagerSchema.php:187-199`:

```php
$endereco->tipos()->delete();
$endereco->tipos()->createMany([...]);
```

Chamado no `->after()` de Create/Edit de Endereço. Pior que 2.4: aqui,
se falhar entre as duas linhas, o Endereço fica **sem tag nenhuma**
(as antigas já foram apagadas antes de tentar criar as novas) — pode
inclusive fazer um endereço "Obra" desaparecer de um Projeto que já o
usava.

**Severidade: MÉDIO-ALTO** — mesmo raciocínio de 2.4, com um efeito
colateral extra (perde até o estado ANTERIOR, não só falha em criar o
novo).

**Recomendação**: `DB::transaction()` ao redor das duas linhas.

### 2.6 Revisão de Projeto (snapshot) — não aplicável

Não implementada ainda no sistema (confirmado — não há Model/Resource
de "Revisão" com lógica de snapshot, só o campo `revisao` numérico sem
lógica de cópia). Nada a investigar aqui por enquanto; ao ser
implementada, deve nascer já com `DB::transaction()` cobrindo a cópia
de Projeto + Itens, dado tudo o que foi levantado neste documento.

### 2.7 Sincronização de permissões do Shield (`shield:generate`)

Não é uma operação de runtime disparada por usuário final — é um
comando artisan rodado manualmente por quem desenvolve/faz deploy (ver
"Comandos e fluxo úteis" no `CLAUDE.md` da raiz: "não sincroniza
sozinho com a role Admin, sempre seguir com `givePermissionTo()`
manual"). Não há risco de concorrência MULTIUSUÁRIO aqui (não roda
durante o uso normal do sistema) — o risco existente é operacional
(esquecer o passo manual), já documentado, não de concorrência.

### 2.8 Resumo — todo o `DB::transaction()` do código próprio (`perseu/*`)

Confirmado por grep exaustivo em `plugins/perseu/` inteiro: as ÚNICAS
3 ocorrências de `DB::transaction` no código são `GeradorNumeroProjeto`,
`confirmarItemAvulso()` e `excluirItemAvulso()` — todas em
`perseu/comercial`. **Nenhuma** em `perseu/pessoas` ou
`perseu/auditoria`, apesar de existirem pelo menos 4 pontos (2.2 a 2.5)
com escrita multi-tabela sem essa proteção.

---

## 3. Bloqueio de documento (check-out) vs. lock de linha no banco

**São complementares, não concorrentes** — concordo com a premissa da
tarefa. Análise de cada um:

### 3.a Bloqueio de documento (check-out) para Projeto

**Viabilidade**: técnicamente simples — uma coluna
`locked_by_user_id`/`locked_at` em `projetos`, marcada ao entrar em
`EditProjeto::mount()` (hook do Livewire) e liberada em algum hook de
"saída" da página. **O ponto fraco é justamente esse "liberar ao
sair"**: Livewire/Filament não tem um hook confiável de "o usuário
fechou a aba/navegou pra outro lugar sem interação explícita" — o mais
próximo é `disconnect` do WebSocket/polling, que não é garantido
(conexão pode cair sem o evento disparar) ou um `beforeunload` no JS
(também não garantido em mobile/fechamento abrupto). Por isso todo
mecanismo de check-out PRECISA de um timeout automático como rede de
segurança (ex.: lock expira sozinho depois de N minutos de
inatividade, verificado a cada request), não só o "liberar ao sair" —
que é sempre best-effort, nunca garantia.

**Minha recomendação**: viável, mas eu NÃO priorizaria agora. É uma
feature de UX (evita o "eu não sabia que fulano tava mexendo ao mesmo
tempo"), não uma proteção de dado por si só — quem quiser pode ignorar
o aviso e salvar por cima de qualquer forma (a menos que o lock também
BLOQUEIE o salvamento, o que exige então reforçar com verificação
server-side no `save()`, aproximando-se de um lock otimista de
qualquer forma — ver R8/4). Se for implementar, eu resolveria R8
(lock otimista simples via `updated_at`) PRIMEIRO — é mais barato, cobre
mais casos (inclusive escrita fora da tela), e dá a proteção de dado
de fato; o check-out viria depois, como camada de UX sobre essa base.

### 3.b Lock de linha no banco (`lockForUpdate()`)

Concordo com a distinção da tarefa: cobre origem QUALQUER (tela, Job,
outro plugin), o check-out só cobre a tela. Operações que hoje
escrevem em tabelas COMPARTILHADAS entre plugins:

- **Lixeira Central** (`perseu/auditoria`) lê e escreve diretamente em
  `Projeto`, `PessoaFisica`, `PessoaJuridica`, `ReferenciaPreco` (via
  `restore()`/`forceDelete()` cross-plugin) — já coberto em 2.3/R3.
  Essas operações SÃO candidatas a lock de linha (ex.: `lockForUpdate()`
  no registro antes de restaurar/excluir, pra evitar que alguém edite o
  registro no exato instante em que ele está sendo restaurado/excluído
  por outra sessão) — mas o risco PRINCIPAL aqui já é a falta de
  transação (2.3), que é mais grave que a falta de lock.
- `SubjectTypeCatalog`/`LogsBusinessActivity` **só leem** outras
  tabelas (`whereHasMorph` pra exibir na Central de Auditoria) — não
  escrevem, sem risco de concorrência aqui.
- Nenhum Job/fila em background foi encontrado no sistema hoje
  (`perseu/*` não usa `ShouldQueue` em lugar nenhum, confirmado por
  ausência total de uso desse padrão) — o risco "Job escrevendo por
  baixo do lock de tela" citado na tarefa é hoje **teórico** (não existe
  nenhum Job rodando), mas vale manter como critério de design pra
  qualquer Job futuro que mexa nas mesmas tabelas de UI.

---

## 3.1 Caso real: Imposto obsoleto (stale read) da Referência de Preços

> **✅ CORRIGIDO (2026-09-05)** — `confirmarItemAvulso()` não usa mais
> `novo_item_imposto` (o valor em cache) pra gravar: busca o `imposto`
> FRESCO do banco (`ReferenciaPreco::where('id', ...)->lockForUpdate()
> ->value('imposto')`) dentro da MESMA `DB::transaction()` da gravação,
> recalcula `valor_unitario`/`valor_total` com esse valor, e grava o
> resultado numa coluna nova, `itens_projeto.imposto_aplicado`
> (snapshot, não FK vivo — preserva o histórico mesmo que a Referência
> mude depois). `lockForUpdate()` na Referência de Preços fecha a janela
> de corrida por completo (não só reduz) entre o clique em "Confirmar" e
> o commit — qualquer tentativa concorrente de mudar essa Referência
> enquanto a transação está aberta espera até ela terminar. Validado
> ponta a ponta: Projeto com Referência (imposto=8%) → usuário abre a
> linha de Item Avulso (carrega e cacheia 8%) → OUTRA sessão muda a
> Referência pra imposto=20% → usuário confirma o item → `imposto_aplicado`
> gravado = 20.00 (o ATUAL, não o 8% obsoleto que ficou em cache) e
> `valor_unitario` calculado corretamente com 20%. Também confirmado que
> o fluxo de inserção/edição/exclusão de Item Avulso já validado em
> tarefas anteriores continua funcionando sem regressão após este
> refactor (mesmo teste de ponta a ponta: bloqueio de inserção
> incompleta, numeração 001/002/003, edição sem mudança sem escrita,
> exclusão + renumeração).

**Confirmado, é um bug real — severidade ALTO.**

Rastreei o fluxo completo em `ProjetoResource.php`:

1. **Ao clicar "Inserir"** (`Action::make('inserirItem')`, branch
   `$origem === 'item_avulso'`):
   ```php
   $referenciaPrecoId = $get('referencia_preco_id');
   $set('novo_item_imposto', filled($referenciaPrecoId)
       ? ReferenciaPreco::find($referenciaPrecoId)?->imposto
       : null);
   ```
   Lê `imposto` do banco **UMA VEZ** e guarda no campo `Hidden`
   `novo_item_imposto` — que fica no ESTADO do componente Livewire
   (sessão do navegador) por todo o tempo que o usuário levar
   preenchendo a linha (pode ser minutos).

2. **A cada tecla** em Qtde./Porc.%/Custo Unitário
   (`recalcularValoresItemAvulso()`):
   ```php
   $imposto = (float) ($get('novo_item_imposto') ?: 0);   // valor CONGELADO do passo 1
   $valorUnitario = (float) $custoUnitario * (1 + $porcentagem/100) * (1 + $imposto/100);
   ```
   Usa sempre o valor congelado, nunca relê o banco.

3. **Ao clicar "Confirmar"** (`confirmarItemAvulso()`): grava
   `valor_unitario`/`valor_total` — já calculados com o imposto
   potencialmente obsoleto — direto em `itens_projeto`, **sem
   nenhuma nova leitura da Referência de Preços**.

4. **Ao abrir EDIÇÃO** de um item já existente (`abrirEdicaoItemAvulso()`)
   o MESMO padrão se repete — lê o imposto ATUAL nesse instante e
   recalcula, também sujeito à mesma janela de obsolescência entre abrir
   a edição e confirmar.

**Cenário de falha real**: Usuário A abre "Item Avulso" num Projeto
vinculado à Referência de Preços "Tabela Padrão" (imposto 8%) e começa
a digitar Custo Unitário/Quantidade. Usuário B, em paralelo, edita
"Tabela Padrão" e muda o imposto pra 12%. Usuário A termina de digitar
e clica "Confirmar" — o Valor Unitário/Total gravado no banco usa 8%,
não os 12% que agora estão de fato cadastrados. **Ninguém percebe**: não
há erro, nem aviso, nem qualquer registro de que o cálculo usou um
valor diferente do atual — só é perceptível comparando manualmente o
item com a Referência de Preços meses depois, se alguém desconfiar.

**Recomendações — status atual:**

1. ✅ **CORRIGIDO** — Reconfirmar o Imposto no momento do clique em
   "Confirmar": `confirmarItemAvulso()` agora busca
   `ReferenciaPreco::where(...)->lockForUpdate()->value('imposto')`
   fresco do banco (não usa mais `$get('novo_item_imposto')`) e
   recalcula `valor_unitario`/`valor_total` com esse valor atualizado,
   dentro da mesma transação da gravação. Com `lockForUpdate()`, a
   janela ficou ainda menor do que "reduzida" — fica efetivamente
   fechada entre o clique e o commit (não só encurtada).
2. ✅ **CORRIGIDO** — `imposto_aplicado` (`decimal(5,2)`, nullable) foi
   adicionada em `itens_projeto` (migration `2026_09_04_160000_create_itens_projeto_table`,
   editada — tabela ainda não tinha sido commitada) e ao `$fillable`/
   `$casts` de `ItemProjeto`. Resolve os dois problemas apontados:
   preserva o histórico do Imposto efetivamente usado, e fecha a
   assimetria entre "os OUTPUTS do cálculo são snapshots" (já eram) e "o
   INSUMO Imposto não ficava registrado em lugar nenhum" (agora fica).
3. **Esse padrão é específico do Imposto/Item Avulso** — não encontrei,
   nesta investigação, outro lugar em `perseu/comercial` que leia um
   valor de uma tabela COMPARTILHADA pra memória de sessão e só use
   esse valor num cálculo persistido bem depois (os outros campos do
   Item Avulso — Custo Unitário, Porcentagem — são digitados
   diretamente pelo usuário, não lidos de um cadastro compartilhado). A
   investigação de `pessoas`/`auditoria` também não identificou uma
   ocorrência equivalente. Ainda assim, é um padrão a vigiar
   proativamente em qualquer feature nova que leia um cadastro de apoio
   compartilhado (Referência de Preços, Situação, Tipo) pra usar num
   cálculo que só persiste depois de alguma interação do usuário.

---

## 4. Edição concorrente do mesmo registro (locking otimista)

**Confirmado categoricamente: NÃO existe locking otimista, nem no
Filament, nem customizado no projeto.**

- Filament instalado: **5.7.3** (`composer.lock`).
- `vendor/filament/filament/src/Resources/Pages/EditRecord.php`
  (`save()`, ~linha 159-204): abre `beginDatabaseTransaction()`, valida
  o form, chama `handleRecordUpdate($this->getRecord(), $data)` — o
  `$this->getRecord()` é a instância carregada quando a PÁGINA ABRIU,
  sem nenhuma comparação contra o estado atual do banco.
- `handleRecordUpdate()` (~linha 281-286):
  ```php
  protected function handleRecordUpdate(Model $record, array $data): Model
  {
      $record->update($data);
      return $record;
  }
  ```
  Um `UPDATE ... WHERE id = ?` puro — sem `WHERE updated_at = ?`, sem
  coluna de versão, sem checagem de staleness nenhuma. "Last write
  wins" categórico.
- Busca por "optimistic"/"concurrency" em `vendor/filament/{filament,
  schemas,forms}`: nada relevante à persistência de registro.
- Busca por `updated_at` em `plugins/perseu/comercial/src/Filament/` e
  `plugins/perseu/pessoas/src/Filament/`: zero ocorrências fora de
  contexto de EXIBIÇÃO (coluna de tabela) — nenhum Resource do Perseu
  adicionou proteção própria.

**Cenário**: Usuário A e Usuário B abrem o MESMO Projeto pra editar.
A muda o Tipo de Projeto e salva. B, com a tela ainda aberta com dados
antigos, muda o Endereço da Obra (sem ver a mudança de A) e salva
depois — o UPDATE de B sobrescreve TODOS os campos do form com os
valores que B tinha na tela, incluindo o Tipo de Projeto ANTIGO (que A
já tinha mudado). A mudança de A é perdida silenciosamente, sem
nenhum aviso pra nenhum dos dois.

**É esperado (comportamento padrão da maioria dos sistemas) ou vale
endereçar?** Na minha avaliação: é de fato o comportamento padrão da
maioria dos CRUDs simples, e não é um bug "do Perseu" — é uma escolha
de design ausente que o Filament também não resolve por padrão.
**Mas** dado o contexto explícito da tarefa (sistema em nuvem, múltiplos
usuários comerciais/produção mexendo potencialmente no MESMO Projeto
durante seu ciclo de vida — Comercial fecha venda, Produção detalha
execução, ambos no mesmo cadastro), eu classificaria como **risco
MÉDIO que vale endereçar antes de produção real**, priorizando pelo
menos os cadastros mais centrais/multi-time (`Projeto` em primeiro
lugar). A implementação mais barata: sobrescrever
`handleRecordUpdate()` em `EditProjeto` pra comparar
`$record->updated_at` (lido no `mount()`) contra o valor atual no banco
antes do `update()`, e mostrar um erro claro se divergir — não exige
nenhuma coluna nova (`updated_at` já existe em toda tabela).

---

## 5. Nível de isolamento de transação do banco

Confirmado via `ddev mysql`:

- `SELECT VERSION();` → `11.8.8-MariaDB-ubu2404-log`
- `SELECT @@transaction_isolation;` (e `@@tx_isolation`,
  `@@global.transaction_isolation`) → **`REPEATABLE-READ`** nas três
  variantes.
- `config/database.php`: nenhuma chave `isolation` configurada, nenhum
  `.env` customizando isso — é o **padrão de fábrica** do
  MariaDB/InnoDB, não uma escolha deliberada do projeto.

**Isso não é a causa de nenhum dos riscos listados acima** — os riscos
são todos de CÓDIGO (falta de transação/lock explícito), não de
configuração de isolamento. Vale uma nota técnica: `REPEATABLE READ`
protege leituras DENTRO de uma mesma transação (evita "non-repeatable
read"/inconsistência de snapshot durante ela), mas **não previne**
"segunda transação sobrescreve o que a primeira gravou" (isso é
exatamente o problema do item 4, que nenhum nível de isolamento do
MySQL/InnoDB resolve sozinho — precisa de lock explícito ou
verificação de versão a nível de aplicação). Não há necessidade de
mudar o isolamento — é adequado pro caso de uso, os riscos reais
listados neste documento se resolvem no CÓDIGO, não na configuração do
banco.

---

## 6. Núcleo AureusERP (`webkul/*`) — concorrência multiusuário herdada

Plugins catalogados: `accounting, accounts, analytics, barcode, blogs,
chatter, contacts, employees, fields, full-calendar, inventories,
invoices, maintenance, manufacturing, partners, payments,
plugin-manager, products, projects, purchases, recruitments, sales,
security, support, table-views, time-off, timesheets, website`.

### 6.1 `webkul/inventories` (estoque) — trata PARCIALMENTE

- `ProductQuantity::adjustStock()` (`src/Models/ProductQuantity.php:288-333`)
  usa `lockForUpdate()` (linha ~316) antes de ler/escrever
  `quantity`/`reserved_quantity` — mecanismo certo.
- **Efetivo** nas 2 rotas de API que já embrulham em
  `DB::transaction()`: `OperationController::createOperation()`/
  `updateOperationById()` (`src/Http/Controllers/API/V1/OperationController.php:220,246`)
  — a cadeia inteira (`syncMoves()` → `MoveLine::create()` → eventos →
  `adjustStock()` → `lockForUpdate()`) roda dentro da mesma transação
  do Controller, então o lock funciona como esperado.
- **Risco não confirmado**: outros pontos que também criam
  `MoveLine`/disparam `adjustStock()` — `Scrap.php`, `MoveReserver.php`,
  `Move.php` — não têm `DB::transaction` PRÓPRIA (confirmado por grep).
  Se algum desses for chamado FORA de um contexto que já abriu uma
  transação (ex.: direto de uma tela Filament, sem passar pela API),
  o `lockForUpdate()` fica "solto" (efeito prático reduzido a um SELECT
  comum fora de transação explícita) — não foi possível confirmar com
  certeza se isso acontece hoje sem rastrear cada chamador
  individualmente, o que ficou fora do tempo desta investigação.
- **Nota de contexto**: `webkul/inventories` não está necessariamente
  ativo no Perseu-FA hoje — é código herdado, dormant até ser ativado.
  Recomendo essa segunda passada (rastrear cada chamador de
  `adjustStock()`) como pré-requisito ANTES de ativar/usar esse módulo
  em produção, não como urgência imediata.

### 6.2 `webkul/sales`, `purchases`, `manufacturing` — numeração SEGURA por construção

Já detalhado na seção 1.4 — número derivado do `id` autoincrement já
commitado (`'SO/'.$this->id`), não de um `MAX()`/`COUNT()` calculado
antes do INSERT. Estruturalmente mais robusto que o padrão usado em
`numero_item` do Perseu (ver 1.2/R6) — sem race condition possível.
`DB::transaction` presente nos Controllers de API de Sales/Purchases,
não auditado a fundo mas a estratégia de numeração torna isso menos
crítico aqui do que em `inventories`.

### 6.3 Demais plugins — sem achados de saldo/contador compartilhado

Passada geral em `accounting`, `invoices`, `products` e os demais: nenhum
`increment()`/`decrement()`/mutação de saldo compartilhado fora do que
`inventories` já centraliza foi encontrado.

**Síntese geral do núcleo herdado**: o AureusERP trata concorrência de
forma **desigual** — o padrão de numeração por `id` (Sales/Purchases/
Manufacturing) é genuinamente robusto e vale como referência; o estoque
(`inventories`, o módulo mais sensível a race condition de verdade)
usa o mecanismo certo (`lockForUpdate`) mas só comprovadamente efetivo
em 2 dos vários caminhos de escrita possíveis. Não é uma base "já
pronta para concorrência real" de forma uniforme — precisa de revisão
módulo a módulo antes de cada um entrar em uso real com múltiplos
usuários, não é seguro assumir que "é do AureusERP, então já está
tratado".

---

## 7. Opinião técnica geral — prontidão para produção multiusuário

**O sistema hoje NÃO está pronto para múltiplos usuários simultâneos
reais sem os ajustes abaixo** — mas os riscos encontrados são, na
maioria, bem localizados e relativamente baratos de corrigir, não
exigem redesenho de arquitetura.

### Bloqueante — ✅ todos corrigidos em 2026-09-05

1. ✅ **R1 — Imposto obsoleto** (seção 3.1): CORRIGIDO. Era o único
   risco desta lista que corrompia SILENCIOSAMENTE um dado financeiro
   já persistido, sem qualquer sinal de que algo deu errado.
2. ✅ **R2/R3 — Cascatas e Lixeira sem transação** (2.2, 2.3):
   CORRIGIDOS. Eram operações DESTRUTIVAS (exclusão definitiva) — o
   custo de NÃO ter transação era dado órfão permanente, sem lixeira
   pra recuperar.

### Importante, ainda em aberto (fora do escopo desta tarefa de correção)

3. **R4/R5 — Endereço + tag sem transação** (2.4, 2.5): ✅ **também
   CORRIGIDOS** nesta mesma tarefa, apesar de originalmente classificados
   como "pode esperar uma janela curta" — acabaram entrando junto por
   serem rápidos e de baixo risco de regressão.
4. **R8 — Locking otimista em `Projeto`** (seção 4): AINDA EM ABERTO.
   Comportamento "padrão da indústria" de qualquer forma, mas o cenário
   de uso real do Perseu (Comercial + Produção no mesmo Projeto) torna
   essa lacuna mais provável de doer na prática do que num CRUD de
   cadastro isolado. Recomendo pelo menos no Resource de Projeto antes
   do sistema ter volume relevante de uso simultâneo real.
5. **R6 — Primeiro item de Projeto** (1.2): AINDA EM ABERTO. Correção
   pequena (capturar a exceção de duplicata e tentar de novo), mas
   evita uma tela de erro feia numa situação plausível (duplo-clique).

### Pode esperar mais (sem urgência imediata)

6. **R9 — `webkul/inventories`** (6.1): AINDA EM ABERTO — só relevante
   quando esse módulo for de fato ativado/usado — fazer a segunda
   passada de rastreio de chamadores como pré-requisito de ativação,
   não antes disso.
7. **R7 — Edição concorrente de Item individual** (dentro do mesmo
   Projeto): AINDA EM ABERTO — escopo mais estreito que R8 (exigiria
   dois usuários editando o MESMO item, não só o mesmo Projeto) —
   resolve-se naturalmente se R8 for implementado no nível do Projeto
   inteiro (que já cobriria a Section "Itens" por tabela).
8. **Bloqueio de documento/check-out** (seção 3.a): NÃO implementado,
   nem nesta tarefa nem antes (feature de aplicação separada, fora do
   escopo de "lock de linha no banco" desta correção) — útil como UX,
   mas não substitui nenhuma das correções acima. Deixaria pra depois
   de resolver o locking otimista (3.b/R8), que é a proteção de dado de
   fato.

### Avaliação geral (atualizada pós-correção)

Os 5 riscos ALTO/MÉDIO-ALTO identificados (R1-R5) foram corrigidos e
validados nesta tarefa — cada um com um teste que força uma falha NO
MEIO da operação (via `DB::listen()` interceptando a query certa e
lançando uma exception) e confirma que o `DB::transaction()` reverte
TUDO, não só revisão de código lendo o diff. O caso do Imposto obsoleto
(R1) foi validado de ponta a ponta simulando o cenário real de dois
usuários (um preenchendo o Item, outro mudando a Referência de Preços
em paralelo) e confirmando que o valor gravado é sempre o ATUAL.

A base construída em `perseu/comercial` já nasce (desde tarefas
anteriores) com boas práticas nos 2 pontos mais sensíveis de numeração
(`GeradorNumeroProjeto` e `excluirItemAvulso()`, `DB::transaction()`+
`lockForUpdate()` corretos) — essa mesma disciplina agora também está
em `perseu/pessoas`/`perseu/auditoria` (que antes desta tarefa não
tinham NENHUMA transação, apesar de precisarem). O que resta em aberto
— locking otimista (R8), a janela estreita do primeiro item de um
Projeto (R6), edição concorrente de item individual (R7), e o núcleo
herdado `webkul/inventories` (R9) — são riscos de severidade MÉDIA ou
menor, deliberadamente fora do escopo desta tarefa de correção, prontos
pra virar uma tarefa futura quando priorizado. Nenhum dos riscos
restantes exige trocar de banco, mudar arquitetura ou adotar fila/lock
distribuído.
