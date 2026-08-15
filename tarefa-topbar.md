Preciso de dois ajustes visuais na topbar (barra superior) do painel
Filament admin:

1. Fundo da topbar na cor #FFC000 (amarelo institucional da marca)
2. Reorganizar o layout: o logotipo deve ficar sozinho em uma linha
   própria, no topo, ocupando a largura total da barra. Abaixo dele,
   em uma segunda linha, ficam os itens de navegação/menu que hoje
   estão ao lado do logo (Configurações, Funções, Empresas, Equipes,
   Usuários, Campos personalizados, busca, notificações, avatar).

Antes de escrever qualquer código, inspecione a estrutura HTML/Blade
real da topbar nesta versão do Filament instalada (procure em
vendor/filament/filament/resources/views/components/topbar* ou
similar) para confirmar a estrutura exata antes de propor a mudança.

Ao aplicar a mudança:
- Garanta contraste legível: se o texto/ícones estiverem em branco ou
  cor muito clara, ajuste para uma cor escura (ex: preto ou cinza
  escuro) sobre o fundo amarelo
- Prefira uma abordagem via CSS customizado registrado no
  AdminPanelProvider (FilamentAsset::register), evitando alterar
  arquivos dentro de vendor/ diretamente (isso se perderia em um
  futuro composer update)
- Se a reorganização do logo em linha própria exigir sobrescrever um
  view/component do Filament (publicar a view), explique isso antes
  de fazer, e faça de forma que sobreviva a atualizações futuras do
  pacote (publicando só o componente necessário, não o pacote inteiro)

Ao final, rode ddev artisan filament:assets e ddev artisan view:clear,
e me diga se algum passo manual adicional é necessário para ver o
resultado.
