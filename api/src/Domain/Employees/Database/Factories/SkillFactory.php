<?php

namespace Domain\Employees\Database\Factories;

use Domain\Employees\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Skill> */
class SkillFactory extends Factory
{
    /** {@inheritdoc} */
    protected $model = Skill::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(3, true),
            'code' => strtoupper($this->faker->unique()->lexify('???')),
            'label_background_color' => $this->faker->hexColor(),
            'label_text_color' => $this->faker->hexColor(),
            'label_border_color' => $this->faker->hexColor(),
            'category' => $this->faker->randomElement(['technical', 'soft_skill', 'domain', 'safety']),
            'description' => $this->faker->optional()->sentence(),
            'is_active' => $this->faker->boolean(90),
        ];
    }
}
