Preciso corrigir 3 pendências específicas listadas em
PENDENCIAS-TRADUCAO.md (item 3 e 4). Releia esse arquivo antes de
começar, para pegar os caminhos exatos de cada arquivo.

## 1. Labels hardcoded na navegação (2 itens)
- PriceListResource: trocar o texto fixo 'Price Lists' por __() com
  uma chave apropriada no namespace do plugin, criando essa chave nos
  arquivos lang/en e lang/pt_BR daquele plugin.
- WebsiteDashboard: mesmo tratamento para 'Website'.

## 2. Chaves ausentes tanto em EN quanto em PT_BR (4 itens)
- IndustryResource (industry.navigation.title)
- TagResource (tag.navigation.title)
- BankResource (bank.navigation.title e bank.navigation.group)

Crie essas chaves nos arquivos de idioma EN (com o texto em inglês
correspondente ao nome do recurso) E em PT_BR (traduzido), já que
atualmente faltam nos dois.

## 3. Chave ausente (company-modal-title)
Mesma lógica: criar em EN e PT_BR.

Depois de todas as correções, rode:
- ddev artisan optimize:clear
- ddev artisan view:clear

Me liste exatamente quais arquivos foram criados/modificados.
