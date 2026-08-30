<?php

namespace Webkul\Support\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Perseu\Pessoas\Enums\IndicadorContribuinteIcms;
use Perseu\Pessoas\Enums\RegimeTributario;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Chatter\Traits\HasChatter;
use Webkul\Field\Traits\HasCustomFields;
use Webkul\Partner\Models\Partner;
use Webkul\Security\Models\User;
use Webkul\Security\Traits\HasOwnershipScope;
use Webkul\Support\Database\Factories\CompanyFactory;
use Webkul\Support\Models\Scopes\CompanyScope;
use Webkul\Support\Traits\RestrictToAllowedCompanies;

class Company extends Model implements Sortable
{
    use HasChatter, HasCustomFields, HasFactory, HasOwnershipScope, RestrictToAllowedCompanies, SoftDeletes, SortableTrait;

    // Reaproveita os mesmos enums de Perseu\Pessoas (ver CLAUDE.md,
    // "Localização do cadastro de Empresa") — o cast é o que faz
    // Filament TextColumn/TextEntry renderizarem o rótulo traduzido
    // automaticamente (via HasLabel) em vez do inteiro cru, mesmo
    // mecanismo já usado em PessoaJuridica.
    protected $casts = [
        'regime_tributario'           => RegimeTributario::class,
        'indicador_contribuinte_icms' => IndicadorContribuinteIcms::class,
    ];

    protected static function ownershipScopeIsGlobal(): bool
    {
        return false;
    }

    protected $fillable = [
        'sort',
        'name',
        'company_id',
        'parent_id',
        'tax_id',
        'registration_number',
        'email',
        'phone',
        'mobile',
        'street1',
        'street2',
        'city',
        'bairro',
        'numero',
        'zip',
        'state_id',
        'country_id',
        'logo',
        'color',
        'is_active',
        'founded_date',
        'creator_id',
        'currency_id',
        'partner_id',
        'website',
        // Localização pro padrão brasileiro de Pessoa Jurídica (ver
        // migration 2026_08_30_100000_add_brazilian_fields_to_companies_table
        // e CLAUDE.md) — `tax_id` (acima) já reaproveitado como CNPJ e
        // `founded_date` (acima) como Data de Abertura, sem coluna nova.
        'nome_fantasia',
        'cnae',
        'cnae_descricao',
        'regime_tributario',
        'porte',
        'descricao_porte',
        'situacao_cadastral',
        'descricao_situacao_cadastral',
        'indicador_contribuinte_icms',
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'parent_id');
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Company::class, 'parent_id');
    }

    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    public function isBranch(): bool
    {
        return ! is_null($this->parent_id);
    }

    public function isParent(): bool
    {
        return is_null($this->parent_id);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id')
            ->withoutGlobalScope(CompanyScope::class);
    }

    public function parents()
    {
        $parents = collect();

        $current = $this->parent;

        while ($current) {
            $parents->push($current);

            $current = $current->parent;
        }

        return $parents;
    }

    public function getParentsAttribute()
    {
        return $this->parents();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    protected static function newFactory(): CompanyFactory
    {
        return CompanyFactory::new();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($company) {
            $company->creator_id ??= Auth::id();

            if (! $company->partner_id) {
                $partner = Partner::create([
                    'creator_id'       => $company->creator_id ?? Auth::id(),
                    'sub_type'         => 'company',
                    'company_registry' => $company->registration_number,
                    'name'             => $company->name,
                    'email'            => $company->email,
                    'website'          => $company->website,
                    'tax_id'           => $company->tax_id,
                    'phone'            => $company->phone,
                    'mobile'           => $company->mobile,
                    'color'            => $company->color,
                    'street1'          => $company->street1,
                    'street2'          => $company->street2,
                    'city'             => $company->city,
                    'zip'              => $company->zip,
                    'state_id'         => $company->state_id,
                    'country_id'       => $company->country_id,
                    'parent_id'        => $company->parent_id,
                    'company_id'       => $company->id,
                ]);

                $company->partner_id = $partner->id;
            }
        });

        static::saving(function ($company) {
            $company->currency->update([
                'active' => true,
            ]);
        });

        static::created(function ($company) {
            if (! $company->creator_id) {
                return;
            }

            User::find($company->creator_id)
                ?->allowedCompanies()
                ->syncWithoutDetaching([$company->id]);
        });

        static::saved(function ($company) {
            Partner::withoutGlobalScopes()->updateOrCreate(
                [
                    'id' => $company->partner_id,
                ],
                [
                    'sub_type'         => 'company',
                    'company_registry' => $company->registration_number,
                    'name'             => $company->name,
                    'email'            => $company->email,
                    'website'          => $company->website,
                    'tax_id'           => $company->tax_id,
                    'phone'            => $company->phone,
                    'mobile'           => $company->mobile,
                    'color'            => $company->color,
                    'street1'          => $company->street1,
                    'street2'          => $company->street2,
                    'city'             => $company->city,
                    'zip'              => $company->zip,
                    'state_id'         => $company->state_id,
                    'country_id'       => $company->country_id,
                    'parent_id'        => $company->parent_id,
                    'company_id'       => $company->id,
                ]
            );
        });
    }
}
