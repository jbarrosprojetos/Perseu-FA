<?php

namespace Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaJuridicaResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Perseu\Pessoas\Enums\TipoEndereco;
use Perseu\Pessoas\Filament\Clusters\Pessoas\Resources\PessoaJuridicaResource;
use Perseu\Pessoas\Models\Endereco;
use Perseu\Pessoas\Support\BrasilApiCnpjLookup;

class CreatePessoaJuridica extends CreateRecord
{
    protected static string $resource = PessoaJuridicaResource::class;

    protected function getCreatedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('pessoas::filament/resources/pessoa-juridica/pages/create-pessoa-juridica.notification.title'))
            ->body(__('pessoas::filament/resources/pessoa-juridica/pages/create-pessoa-juridica.notification.body'));
    }

    /**
     * Materializa um Endereço (Comercial/Principal) na relação
     * `enderecos` (EnderecosRelationManager já existente, ver
     * PessoaJuridicaResource::getRelations() — o formulário de Pessoa
     * Jurídica NÃO tem campos de endereço próprios) a partir do resultado
     * da busca de CNPJ. `BrasilApiCnpjLookup::buscar()` é cacheado por 10
     * min (ver a própria classe), então isso normalmente reaproveita a
     * mesma resposta já obtida quando o usuário digitou o CNPJ no
     * formulário, sem nova chamada de rede.
     *
     * Se o CNPJ não tiver passado por uma busca bem-sucedida (não
     * encontrado, erro de rede, ou o campo nunca perdeu o foco), `buscar()`
     * retorna `null` e nenhum Endereço é criado — o usuário cria
     * manualmente pela aba de Endereços, como já funcionava antes.
     *
     * Só roda no Create — numa edição, este hook não existe (é específico
     * de CreateRecord), então não há risco de duplicar o Endereço a cada
     * save.
     */
    protected function afterCreate(): void
    {
        $digits = preg_replace('/\D/', '', (string) $this->record->cnpj);

        if (strlen($digits) !== 14) {
            return;
        }

        $data = BrasilApiCnpjLookup::buscar($digits);

        if ($data === null) {
            return;
        }

        $campos = BrasilApiCnpjLookup::enderecoFrom($data);

        if (collect($campos)->every(fn ($valor) => blank($valor))) {
            return;
        }

        $endereco = Endereco::create($campos);

        $this->record->enderecos()->attach($endereco->id, [
            'tipo'      => TipoEndereco::Comercial->value,
            'principal' => true,
        ]);
    }
}
