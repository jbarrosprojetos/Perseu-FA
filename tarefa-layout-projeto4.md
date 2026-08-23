Ajustes finos de largura no formulário de ProjetoResource:

## 1. Nome da Obra (descricao) está pequeno demais

Na Linha 1, o campo descricao ("Nome da Obra") está com max-width: 30ch
(calibrado numa tarefa anterior), ficando visualmente pequeno. Aumente
para que ele se estenda mais, chegando próximo/alinhado com a coluna
onde fica "Tipo de Projeto" ao lado (avalie o espaço disponível e
calibre um valor maior, ex: 45-50ch, ou remova o teto e use apenas
grow() sem max-width, testando visualmente qual fica melhor).

## 2. Labels "Física"/"Jurídica" do Radio aparecem espremidos

O Radio de tipo_contratante (max-width: 22ch, calculado numa tarefa
anterior) está deixando os labels "Física" e "Jurídica" com pouco
espaço, aparentando aperto visual. Aumente esse max-width moderadamente
(ex: para 28-30ch) para dar mais respiro entre as duas opções, sem
comprometer o alinhamento do restante da linha.

## 3. Alinhar o campo Cliente com a largura do Nome da Obra

O Select de Cliente (pessoa_fisica_id/pessoa_juridica_id) na Linha 2
deve ter a MESMA largura final que o campo descricao ("Nome da Obra")
da Linha 1, para que as duas colunas fiquem visualmente alinhadas uma
embaixo da outra entre as duas linhas.

Ao final, rode ddev artisan optimize:clear e ddev artisan filament:assets.
Confirme via HTML renderizado os valores finais aplicados aos 3
campos (descricao, Radio, Cliente).
