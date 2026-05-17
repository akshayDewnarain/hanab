<?php

namespace Domain\Employees\Database\Factories;

use Domain\Employees\Models\Certificate;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Certificate> */
class CertificateFactory extends Factory
{
    /** {@inheritdoc} */
    protected $model = Certificate::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(3, true),
            'code' => strtoupper($this->faker->unique()->lexify('???')),
            'label_background_color' => $this->faker->hexColor(),
            'label_text_color' => $this->faker->hexColor(),
            'label_border_color' => $this->faker->hexColor(),
            'category' => $this->faker->randomElement(['safety', 'technical', 'compliance']),
            'description' => $this->faker->optional()->sentence(),
            'requires_expiry_date' => $this->faker->boolean(40),
            'is_active' => $this->faker->boolean(90),
        ];
    }
}
