## 1. Reorganizar o layout do formulário de PessoaFisicaResource

Reestruturar usando componentes Grid/Group do Filament Schemas, na
seguinte disposição (2 colunas onde indicado):

- Linha 1: Nome (coluna única, largura total - columnSpanFull)
- Linha 2: Grid 2 colunas:
  - Coluna 1: Telefone + Toggle "É WhatsApp?" agrupados (lado a lado
    ou empilhados dentro do mesmo Group, como fizer mais sentido
    visualmente)
  - Coluna 2: E-mail
- Linha 3: Grid 2 colunas:
  - Coluna 1: RG + Data de Nascimento agrupados
  - Coluna 2: CPF (confirme que a máscara 999.999.999-99 já está
    aplicada, deve estar desde a criação original)
- Linha 4: Grid 2 colunas:
  - Coluna 1: Estado Civil + Sexo agrupados
  - Coluna 2: Profissão
- Linha 5: Observações (columnSpanFull)

Use Schemas\Components\Grid ou Section com columns(2), como for mais
consistente com o padrão já usado em outros formulários do sistema
(verifique um exemplo existente antes de decidir a abordagem exata).

## 2. Verificar se o tema compacto (Bonsai) está ativo neste projeto

Investigue se o pacote qalainau/bonsai-theme está instalado
(composer show qalainau/bonsai-theme) e se o BonsaiThemePlugin está
registrado no AdminPanelProvider (deve ter sido copiado junto quando
o projeto foi clonado de ~/testes/aureuserp, mas pode não estar
registrado no provider atual).

Se estiver instalado mas não registrado, registre-o. Se não estiver
instalado, instale e registre, seguindo o mesmo processo já documentado
anteriormente. Se já estiver tudo ativo, apenas confirme e não faça
nada.

Ao final, rode ddev artisan optimize:clear e ddev artisan filament:assets.
