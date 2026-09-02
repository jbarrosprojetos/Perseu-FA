<?php

namespace Webkul\Project\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Partner\Models\Partner;
use Webkul\Project\Models\Processo;
use Webkul\Project\Models\ProcessoStage;
use Webkul\Security\Models\User;
use Webkul\Support\Database\Factories\Concerns\HasCompanyDefault;

/**
 * @extends Factory<Processo>
 */
class ProcessoFactory extends Factory
{
    use HasCompanyDefault;

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Processo::class;

    /**
     * Define the model's default state.
     *
     * @return array<string => , mixed>
     */
    public function definition(): array
    {
        return [
            'name'                    => fake()->name(),
            'description'             => fake()->sentence(),
            'tasks_label'             => 'Tasks',
            'visibility'              => 'public',
            'color'                   => fake()->hexColor(),
            'sort'                    => fake()->randomNumber(),
            'start_date'              => fake()->date(),
            'end_date'                => fake()->date(),
            'allocated_hours'         => fake()->numberBetween(1, 999999),
            'allow_timesheets'        => true,
            'allow_milestones'        => false,
            'allow_task_dependencies' => false,
            'is_active'               => true,
            'stage_id'                => ProcessoStage::factory(),
            'partner_id'              => Partner::query()->value('id') ?? Partner::factory(),
            'user_id'                 => User::query()->value('id') ?? User::factory(),
            'creator_id'              => User::query()->value('id') ?? User::factory(),
        ];
    }
}
