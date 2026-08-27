<?php

namespace Perseu\Pessoas\Support;

use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Perseu\Pessoas\Enums\RegimeTributario;
use Perseu\Pessoas\Rules\CnpjValido;
use Throwable;

/**
 * Consulta a API pública BrasilAPI (https://brasilapi.com.br, sem
 * autenticação, fonte minhaReceita/Receita Federal) e preenche os campos
 * do formulário de Pessoa Jurídica a partir do CNPJ digitado. Mesmo
 * padrão de Perseu\Pessoas\Support\ViaCepLookup (classe estática, sem
 * estado, operando via Set/Get do Filament em vez de mutar um Model
 * diretamente), reaproveitado tanto em CreatePessoaJuridica quanto
 * EditPessoaJuridica porque as duas páginas usam o mesmo
 * PessoaJuridicaResource::form().
 */
class BrasilApiCnpjLookup
{
    private const ERROR_STATE_KEY = 'cnpj_lookup_erro';

    /**
     * Só preenche campos que estiverem vazios — nunca sobrescreve um valor
     * já digitado pelo usuário (ver CLAUDE.md / UX definida com o
     * usuário). Não há, no Filament, uma forma simples de "perguntar antes
     * de sobrescrever" sem interromper o fluxo automático (abrir um modal
     * de confirmação a cada edição de CNPJ seria mais intrusivo que o
     * ganho), então essa é a opção mais segura.
     */
    public static function fill(Set $set, Get $get, ?string $cnpj): void
    {
        $digits = preg_replace('/\D/', '', (string) $cnpj);

        if (strlen($digits) !== 14 || ! CnpjValido::isValid($digits)) {
            $set(self::ERROR_STATE_KEY, null);
            static::limparSituacaoCadastral($set);

            return;
        }

        $data = static::buscar($digits);

        if ($data === null) {
            $set(self::ERROR_STATE_KEY, __('pessoas::filament/resources/pessoa-juridica.form.cnpj-nao-encontrado'));
            static::limparSituacaoCadastral($set);

            return;
        }

        $set(self::ERROR_STATE_KEY, null);

        if (blank($get('razao_social')) && filled($data['razao_social'] ?? null)) {
            $set('razao_social', $data['razao_social']);
        }

        if (blank($get('nome_fantasia')) && filled($data['nome_fantasia'] ?? null)) {
            $set('nome_fantasia', $data['nome_fantasia']);
        }

        // ddd_telefone_1 é o telefone principal; ddd_telefone_2 só entra
        // como fallback quando o principal vier vazio (nem toda empresa
        // tem os dois preenchidos na base pública). ddd_fax nunca é usado
        // — não há campo de fax no cadastro.
        if (blank($get('telefone'))) {
            $telefone = static::formatarTelefone($data['ddd_telefone_1'] ?? null)
                ?? static::formatarTelefone($data['ddd_telefone_2'] ?? null);

            if ($telefone !== null) {
                $set('telefone', $telefone);
            }
        }

        if (blank($get('email')) && filled($data['email'] ?? null)) {
            $set('email', $data['email']);
        }

        if (blank($get('cnae')) && filled($data['cnae_fiscal'] ?? null)) {
            $set('cnae', static::formatarCnae((string) $data['cnae_fiscal']));
            $set('cnae_descricao', $data['cnae_fiscal_descricao'] ?? null);
        }

        if (blank($get('data_abertura')) && filled($data['data_inicio_atividade'] ?? null)) {
            $set('data_abertura', $data['data_inicio_atividade']);
        }

        // A resposta da BrasilAPI usa `porte` para o TEXTO ("DEMAIS",
        // "MICRO EMPRESA"...) e `codigo_porte` para o código numérico —
        // invertido em relação ao padrão cnae_fiscal/cnae_fiscal_descricao
        // que o nome sugeriria. Confirmado com uma chamada real (ver
        // CLAUDE.md) antes de mapear, para não assumir o nome errado.
        if (blank($get('porte')) && filled($data['codigo_porte'] ?? null)) {
            $set('porte', (string) $data['codigo_porte']);
            $set('descricao_porte', $data['porte'] ?? null);
        }

        // opcao_pelo_simples/opcao_pelo_mei só permitem inferir os casos
        // positivos com segurança — quando ambos são false/null, a empresa
        // pode estar em Lucro Presumido OU Lucro Real, e a API não
        // distingue os dois, então o campo fica em "Não Informado" nesse
        // caso, para o usuário escolher manualmente. Só sobrescreve quando
        // o valor atual é null ou "Não Informado" — se o usuário já
        // escolheu um regime manualmente, não mexe (blank() não serve aqui
        // porque o valor padrão do Select é 0, e blank(0) é false).
        // Select::options(RegimeTributario::class) ativa o cast automático
        // de enum do Filament (ver HasOptions::options() -> ->enum()) — o
        // valor lido via $get() aqui vem como instância de RegimeTributario,
        // não como int cru, então precisa desembrulhar antes de comparar.
        $regimeAtual = $get('regime_tributario');
        $regimeAtual = $regimeAtual instanceof RegimeTributario ? $regimeAtual->value : $regimeAtual;

        if ($regimeAtual === null || (int) $regimeAtual === RegimeTributario::NaoInformado->value) {
            if (($data['opcao_pelo_mei'] ?? null) === true) {
                $set('regime_tributario', RegimeTributario::Mei->value);
            } elseif (($data['opcao_pelo_simples'] ?? null) === true) {
                $set('regime_tributario', RegimeTributario::SimplesNacional->value);
            }
        }

        // Situação Cadastral é somente leitura (reflete a Receita Federal,
        // nunca digitada pelo usuário) — diferente dos demais campos,
        // sempre é sobrescrita com o resultado da busca mais recente, sem
        // checar blank() antes.
        $set('situacao_cadastral', filled($data['situacao_cadastral'] ?? null) ? (string) $data['situacao_cadastral'] : null);
        $set('descricao_situacao_cadastral', $data['descricao_situacao_cadastral'] ?? null);
    }

    /**
     * Extrai os campos de endereço de uma resposta crua da API, no mesmo
     * formato aceito por `Endereco::create()` — usado por
     * `CreatePessoaJuridica::afterCreate()` para materializar um Endereço
     * (Comercial/Principal) a partir do resultado da busca de CNPJ. O
     * formulário de Pessoa Jurídica NÃO tem campos de endereço próprios
     * (endereço é a relação `enderecos`, gerenciada pelo
     * EnderecosRelationManager já existente — ver CLAUDE.md), então esses
     * dados nunca passam por `Set`/`Get` de formulário, só por aqui.
     *
     * Sem prefixar `logradouro` com `descricao_tipo_de_logradouro` — o
     * campo já vem da API no mesmo formato texto-livre que ViaCepLookup
     * usa, então concatenar criaria um formato diferente do que a busca
     * de CEP produz para o mesmo campo.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function enderecoFrom(array $data): array
    {
        return [
            'cep'         => $data['cep'] ?? null,
            'logradouro'  => $data['logradouro'] ?? null,
            'numero'      => $data['numero'] ?? null,
            'complemento' => $data['complemento'] ?? null,
            'bairro'      => $data['bairro'] ?? null,
            'municipio'   => $data['municipio'] ?? null,
            'uf'          => $data['uf'] ?? null,
        ];
    }

    private static function limparSituacaoCadastral(Set $set): void
    {
        $set('situacao_cadastral', null);
        $set('descricao_situacao_cadastral', null);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function buscar(string $cnpjDigits): ?array
    {
        // Cache::remember não guarda `null` de fato (Cache::get não
        // distingue "chave ausente" de "valor null"), então uma consulta
        // que falhou/CNPJ não encontrado simplesmente não fica em cache e
        // é tentada de novo na próxima chamada — comportamento aceitável
        // aqui (evita mascarar uma falha temporária da API), não é bug.
        return Cache::remember(
            "brasilapi.cnpj.{$cnpjDigits}",
            now()->addMinutes(10),
            function () use ($cnpjDigits) {
                try {
                    $response = Http::timeout(8)->get("https://brasilapi.com.br/api/cnpj/v1/{$cnpjDigits}");
                } catch (Throwable) {
                    return null;
                }

                if (! $response->successful()) {
                    return null;
                }

                $data = $response->json();

                return is_array($data) ? $data : null;
            },
        );
    }

    private static function formatarTelefone(?string $dddTelefone): ?string
    {
        if (blank($dddTelefone)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $dddTelefone);

        if (strlen($digits) === 11) {
            // (11) 99970-7134
            return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 5), substr($digits, 7, 4));
        }

        if (strlen($digits) === 10) {
            // (11) 2385-1939
            return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 4), substr($digits, 6, 4));
        }

        return null;
    }

    private static function formatarCnae(string $cnaeFiscal): ?string
    {
        $digits = str_pad(preg_replace('/\D/', '', $cnaeFiscal), 7, '0', STR_PAD_LEFT);

        if (strlen($digits) !== 7) {
            return null;
        }

        // 9999-9/99
        return substr($digits, 0, 4).'-'.substr($digits, 4, 1).'/'.substr($digits, 5, 2);
    }
}
