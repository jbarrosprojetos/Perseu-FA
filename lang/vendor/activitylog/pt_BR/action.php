<?php

/**
 * Override de UMA chave que faltava no pacote rmsramos/activitylog
 * (`vendor/rmsramos/activitylog/resources/lang/pt_BR/action.php`), sem
 * copiar o arquivo inteiro: o `Illuminate\Translation\FileLoader`
 * mescla (`array_replace_recursive`) o que estiver aqui em
 * `lang/vendor/{namespace}/{locale}/{grupo}.php` por cima da tradução
 * original do pacote — as chaves existentes (`created`/`deleted`/
 * `updated`/`restored`, `modal`, `view`, etc.) continuam vindo do
 * pacote normalmente, só `forceDeleted` é adicionado.
 *
 * Necessário porque o Spatie Activitylog NUNCA logou o evento
 * `forceDeleted` por padrão (ver
 * `Perseu\Auditoria\Traits\LogsBusinessActivity::bootLogsBusinessActivity()`,
 * que fechou essa lacuna) — o pacote de UI (rmsramos/activitylog)
 * também nunca precisou de um rótulo pra esse evento, por isso não
 * vinha com um.
 */
return [
    'event' => [
        'forceDeleted' => 'excluído definitivamente',
    ],
];
