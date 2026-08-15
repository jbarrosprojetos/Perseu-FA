Preciso traduzir manualmente, na tabela "currencies", apenas os
registros mais relevantes para uso prático (não a lista completa de
169 moedas):
- BRL → "Real brasileiro"
- USD → "Dólar americano"
- EUR → "Euro"

E na tabela "countries" (250 registros), traduzir apenas:
- Brazil → "Brasil"
- United States → "Estados Unidos"
- Argentina, Paraguay, Uruguay → nomes já em português caso estejam
  diferentes (Argentina, Paraguai, Uruguai)
- Portugal (caso o nome esteja diferente do português)

Faça essa correção diretamente no banco (via seeder/comando artisan,
ou update direto), e também no arquivo de seed correspondente (json ou
equivalente), para que a tradução sobreviva a uma reinstalação futura.

Atualize a seção correspondente em PENDENCIAS-TRADUCAO.md, marcando
como "parcialmente resolvido - apenas os itens de uso prático foram
traduzidos, o restante da lista permanece em inglês por decisão do
cliente".

Ao final, rode ddev artisan optimize:clear e confirme os valores
atualizados.
