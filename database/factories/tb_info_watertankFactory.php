<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
// InfoWatertankFactory.php

use Faker\Generator as Faker;
use Illuminate\Support\Str;

$factory->define(App\Models\InfoWatertank::class, function (Faker $faker) {
  $nama = $faker->unique()->randomElement(['rumah 1', 'rumah 2']) . ' ' . $faker->unique()->numberBetween(1, 100);
  return [
    'nama' => $faker->word,
    'diameter' => $faker->randomFloat(2, 1, 10),
    'tinggi' => $faker->numberBetween(1, 50),
    'kapasitas' => $faker->randomFloat(2, 100, 1000),
    'created_at' => now(),
    'updated_at' => now(),
  ];
});
