Investigação: nos campos compactos de PessoaFisicaResource (usando
HasCompactFieldWidth), o valor calculado de max-width está sendo
aplicado corretamente ao <input>/<select> em si (via
extraInputAttributes), mas visualmente a CAIXA com a borda cinza
(o elemento com fundo branco e contorno visível) parece maior do que
o campo, deixando espaço vazio dentro da borda, além do texto.

Investigue a estrutura HTML renderizada real (via
Livewire::test(...)->html() já usado nas validações anteriores) do
campo Telefone (ou qualquer um dos compactos) e identifique:

1. Existe um elemento WRAPPER (div externo) ao redor do <input> que
   tem largura diferente (maior) do que o max-width aplicado no input
   interno? Se sim, esse wrapper também precisa receber a mesma
   restrição de largura, ou o max-width deveria ser aplicado nesse
   wrapper em vez do input interno.
2. O box-sizing do elemento está configurado como "border-box" ou
   "content-box"? Se for content-box, o max-width pode não estar
   contabilizando padding/border, fazendo o elemento renderizar maior
   do que o esperado visualmente.
3. Existe algum estilo do próprio Filament/Tailwind (ex: w-full,
   flex-1, ou similar) competindo/sobrescrevendo o max-width aplicado,
   fazendo o elemento tentar esticar mesmo com a restrição?

Corrija a causa raiz encontrada no trait HasCompactFieldWidth, para que
a CAIXA VISÍVEL do campo (borda + fundo) tenha exatamente a largura
calculada, sem espaço vazio sobrando dentro dela.

Ao final, rode ddev artisan optimize:clear e ddev artisan filament:assets,
e teste renderizando o HTML novamente para confirmar visualmente
(descrevendo as classes/estilos do elemento) que o problema foi
resolvido.
