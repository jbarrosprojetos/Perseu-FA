Ajustar o layout do formulário de PessoaFisicaResource (já reorganizado
anteriormente), com 3 correções finas de posicionamento:

1. TELEFONE + WHATSAPP na mesma linha: o campo Telefone deve dividir
   a linha com o Toggle "É WhatsApp?" (reduzir a largura do Telefone
   proporcionalmente para caber os dois lado a lado nessa linha,
   ex: Telefone ocupando um espaço maior tipo 2/3 e o Toggle 1/3, ou
   proporção que fique visualmente equilibrada).

2. RG + DATA DE NASCIMENTO na mesma linha: RG deve dividir a linha com
   Data de Nascimento (reduzir a largura do RG proporcionalmente para
   caber os dois lado a lado).

3. ESTADO CIVIL + SEXO + PROFISSÃO na mesma linha, em 3 colunas: esses
   três campos devem ficar juntos numa única linha, divididos em 3
   colunas de largura igual (usar Grid com columns(3) para essa linha
   específica).

O restante do layout (Nome em linha única, E-mail e CPF onde estão,
Observações em linha única) permanece como está.

Ao final, rode ddev artisan optimize:clear e ddev artisan filament:assets.
