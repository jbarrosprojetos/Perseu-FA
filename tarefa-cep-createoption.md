No ProjetoResource (plugin Comercial), o createOptionForm do campo
endereco_id (que abre um modal para cadastrar um novo Endereço na
hora) não tem a busca automática de CEP (ViaCEP) que já existe no
plugin Pessoas, em
Perseu\Pessoas\Traits\HasEnderecoRelationManagerSchema.

Investigue se é possível REAPROVEITAR diretamente essa lógica (ex:
chamando um método estático compartilhado do trait do plugin Pessoas
a partir do plugin Comercial, já que ele está disponível via
autoload), em vez de duplicar a implementação da consulta à API
ViaCEP.

Se for tecnicamente limpo reaproveitar (plugins podem referenciar
classes um do outro, já que ambos estão registrados no mesmo projeto
Laravel), use esse caminho. Se gerar acoplamento problemático entre
plugins que deveriam ser independentes (avalie e explique sua
decisão), então duplique a lógica de forma equivalente dentro do
plugin Comercial, documentando a decisão no CLAUDE.md.

Ao final, rode ddev artisan optimize:clear e ddev artisan filament:assets.
Teste via tinker/Livewire simulando o preenchimento do CEP
"06711-250" e confirme que logradouro, bairro, município e UF são
preenchidos automaticamente no formulário do createOptionForm.
