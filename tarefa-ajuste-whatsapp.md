No formulário de PessoaFisicaResource, o Toggle "É WhatsApp?" está
desalinhado verticalmente em relação ao campo Telefone (fica mais alto/
deslocado, não alinhado com a linha do campo).

Investigue:
1. Se o componente Toggle do Filament tem alguma opção de tamanho
   reduzido (ex: algum método ->extraAttributes() ou classe CSS que
   diminua a altura do switch visual)
2. A causa exata do desalinhamento (provavelmente o Toggle tem um
   "label" próprio ocupando espaço vertical diferente do TextInput)

Ajuste para que o Toggle "É WhatsApp?" fique posicionado imediatamente
à direita do label "Telefone" (ou seja, na mesma linha do texto do
label, acima do campo de input, não ao lado do campo em si), reduzido
de tamanho se possível, para não competir em altura com o campo de
texto.

Se não for viável posicionar exatamente ao lado do label por
limitação do Filament, proponha a alternativa mais próxima
visualmente (ex: alinhar verticalmente ao centro da altura combinada
label+input, ou reduzir o Toggle e alinhá-lo ao topo do grid), e
explique a limitação encontrada.

Ao final, rode ddev artisan optimize:clear e ddev artisan filament:assets.
