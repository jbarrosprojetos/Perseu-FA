Decisão tomada: remover definitivamente o tema Bonsai
(qalainau/bonsai-theme) do projeto, não apenas desativar.

1. Remova o pacote via composer:
   ddev composer remove qalainau/bonsai-theme

2. Remova a linha ->plugin(BonsaiThemePlugin::make()) e o import
   correspondente do AdminPanelProvider.php (a mesma que havia sido
   comentada na tarefa anterior - agora remova de vez, não deixe
   comentário morto).

3. Remova os arquivos CSS customizados criados especificamente para
   corrigir conflitos causados pelo Bonsai, JÁ QUE ELES NÃO SERÃO MAIS
   NECESSÁRIOS sem o Bonsai ativo:
   - resources/css/filament/admin-select.css
   - resources/css/filament/admin-entry-label.css
   - resources/css/filament/admin-entry-content.css
   - resources/css/filament/admin-radio-gap.css
   Remova também o registro desses arquivos no AdminPanelProvider
   (FilamentAsset::register).

   MANTENHA o resources/css/filament/admin-topbar.css (esse controla
   a cor/logo/layout da topbar, não é uma correção de conflito com o
   Bonsai - é customização nossa de identidade visual).

4. Verifique se algum dos nossos próprios traits (HasCompactFieldWidth
   em plugins/perseu/pessoas) tem alguma menção a "Bonsai" no código
   ou comentários que precise ser ajustada agora que ele não existe
   mais (ex: os !important que foram adicionados especificamente por
   causa do Bonsai) - avalie se ainda fazem sentido manter (não fazem
   mal ficar, mas os comentários explicando "por causa do Bonsai"
   ficariam desatualizados) ou se deve limpar/atualizar os comentários.

5. Atualize CLAUDE.md: adicione uma nota no topo da seção sobre
   Bonsai dizendo que ele foi REMOVIDO definitivamente em [data], com
   o motivo (conflitos recorrentes com qualquer tela/plugin fora dos
   nossos módulos próprios, que já têm sistema de compactação
   independente). Mantenha o histórico da investigação como registro,
   mas deixe claro que não está mais ativo no projeto.

6. Rode ddev artisan optimize:clear e ddev artisan filament:assets.

## Validação

1. Confirme que composer.json/composer.lock não referenciam mais o
   pacote
2. Teste a submissão de formulários em PessoaFisicaResource e
   ProjetoResource para garantir que nada quebrou (nosso sistema de
   compactação não deveria depender do Bonsai de forma nenhuma)
3. Confirme visualmente (ou via HTML) que as telas de Projects,
   Pessoas e Comercial continuam funcionando

Me relate tudo que foi removido/alterado.
