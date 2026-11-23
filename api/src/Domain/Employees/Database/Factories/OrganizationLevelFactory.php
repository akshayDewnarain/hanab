<?php

namespace Domain\Employees\Database\Factories;

use Domain\Employees\Models\OrganizationLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OrganizationLevel> */
class OrganizationLevelFactory extends Factory
{
    /** {@inheritdoc} */
    protected $model = OrganizationLevel::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'sort_order' => $this->faker->numberBetween(0, 100),
            'color' => '#'.str_pad(dechex($this->faker->numberBetween(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT),
            'is_active' => true,
        ];
    }
}
