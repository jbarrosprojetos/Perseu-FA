Nas linhas com Flex (HasCompactFieldWidth::flexRow), o espaçamento
atual entre campos está pequeno demais — os LABELS de campos vizinhos
estão colando um no outro visualmente (ex: "É WhatsApp?" colado em
"E-mail", "Data de Nascimento" colado em "CPF"), mesmo os campos em si
tendo espaço.

Ajuste o gap do Flex para adicionar um espaçamento equivalente a
aproximadamente 2 caracteres (2ch) entre cada campo da linha, o
suficiente para os labels não colarem, sem exagerar a ponto de tirar o
efeito compacto pretendido.

Investigue se ->dense() do componente Flex tem um valor de gap fixo
não customizável, ou se existe uma forma de definir um gap específico
(ex: método ->gap() se existir, ou via extraAttributes com uma classe/
estilo CSS customizado aplicado ao wrapper do Flex).

Aplique esse ajuste de forma centralizada no método flexRow() do trait
HasCompactFieldWidth (não em cada Resource individualmente), para que
valha automaticamente em qualquer formulário futuro que reaproveitar
esse helper.

Ao final, rode ddev artisan optimize:clear e ddev artisan filament:assets,
e confirme via HTML renderizado qual o valor de gap aplicado.
