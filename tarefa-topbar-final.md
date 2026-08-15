Preciso de três ajustes no arquivo
resources/css/filament/admin-topbar.css:

1. COR DINÂMICA (correção): atualmente o CSS usa a cor #FFC000 fixa
   (hardcoded) para o fundo da topbar. Troque para usar a variável CSS
   dinâmica que o Filament já gera a partir da cor primária configurada
   em Settings > Branding (via FilamentColor::register, chamado no
   middleware App\Http\Middleware\ApplyBrandSettings). O objetivo é que,
   se a cor primária for trocada pela tela no futuro, a topbar mude
   automaticamente, sem precisar editar este CSS.

   Também investigue e me explique: existe alguma cor azul "primary"
   hardcoded em algum lugar conflitando com a cor primária configurada
   (que deveria ser amarela)? Se encontrar, corrija para usar a mesma
   variável dinâmica.

2. ALINHAMENTO DO LOGO: atualmente o logo está centralizado na sua
   linha própria (acima do menu). Preciso que fique alinhado à
   ESQUERDA, mantendo a linha própria.

3. DESTAQUE DO ITEM DE MENU SELECIONADO: o item ativo (ex: "Configurações"
   na navegação) precisa de destaque mais visível/escuro. Use um tom
   mais escuro da cor "gray_color" configurada em Settings > Branding,
   através de variável CSS dinâmica (mesmo princípio do item 1, não usar
   valor fixo).

Ao final, rode ddev artisan filament:assets e ddev artisan view:clear.
