<?php

namespace App\Providers;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Component;
use Livewire\Livewire;
use Webkul\Security\Models\User;

use function Livewire\on;
use function Livewire\store;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(Authenticatable::class, User::class);
    }

    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        DatePicker::configureUsing(function (DatePicker $component): void {
            if (app()->getLocale() === 'pt_BR') {
                $component->displayFormat('d/m/Y');
            }
        });

        DateTimePicker::configureUsing(function (DateTimePicker $component): void {
            if (app()->getLocale() === 'pt_BR') {
                $component->displayFormat('d/m/Y H:i:s');
            }
        });

        Table::configureUsing(function (Table $table): void {
            if (app()->getLocale() === 'pt_BR') {
                $table
                    ->defaultDateDisplayFormat('d/m/Y')
                    ->defaultDateTimeDisplayFormat('d/m/Y H:i:s');
            }
        });

        Schema::configureUsing(function (Schema $schema): void {
            if (app()->getLocale() === 'pt_BR') {
                $schema
                    ->defaultDateDisplayFormat('d/m/Y')
                    ->defaultDateTimeDisplayFormat('d/m/Y H:i:s');
            }
        });

        on('dehydrate', function (Component $component): void {
            if (! Livewire::isLivewireRequest()) {
                return;
            }

            if (! store($component)->has('redirect')) {
                return;
            }

            $notifications = session()->pull('filament.notifications');

            if (empty($notifications)) {
                return;
            }

            session()->put('filament.claimed_notifications', $notifications);
        });
    }
}
