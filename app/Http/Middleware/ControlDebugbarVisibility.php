<?php

namespace App\Http\Middleware;

use Closure;
use Fruitcake\LaravelDebugbar\LaravelDebugbar;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controls the visibility of barryvdh/laravel-debugbar per authenticated
 * user, independent of APP_DEBUG — see "Controle de visibilidade da
 * Debugbar via Role com Guard Sanctum" no CLAUDE.md para o racional
 * completo.
 *
 * `Webkul\Security\Models\User::$guard_name = ['web', 'sanctum']`, e
 * `HasRoles::roles()` (Spatie Permission) retorna as roles do usuário em
 * QUALQUER guard — por isso o filtro por `guard_name` é feito aqui
 * explicitamente, não é algo que o pacote já faz sozinho.
 */
class ControlDebugbarVisibility
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null) {
            $debugbar = app(LaravelDebugbar::class);

            if ($user->roles()->where('guard_name', 'sanctum')->exists()) {
                $debugbar->enable();
            } else {
                $debugbar->disable();
            }
        }

        return $next($request);
    }
}
