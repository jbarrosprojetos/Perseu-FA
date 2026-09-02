<?php

namespace Webkul\Project\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Project\Models\ProcessoStage;
use Webkul\Security\Models\User;
use Webkul\Support\Database\Factories\Concerns\HasCompanyDefault;

/**
 * @extends Factory<ProcessoStage>
 */
class ProcessoStageFactory extends Factory
{
    use HasCompanyDefault;

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ProcessoStage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string => , mixed>
     */
    public function definition(): array
    {
        return [
            'name'         => fake()->name(),
            'sort'         => fake()->randomNumber(),
            'is_active'    => true,
            'is_collapsed' => false,
            'creator_id'   => User::query()->value('id') ?? User::factory(),
        ];
    }
}
