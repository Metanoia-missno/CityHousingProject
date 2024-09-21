<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Permission;

class PermissionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Permission::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'permission_name' => $this->faker->regexify('[A-Za-z0-9]{100}'),
        ];
    }
}