<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Model;
use Faker\Generator as Faker;

$factory->define(App\Student::class, function (Faker $faker) {
    return [
        'user_name' => $faker->unique()->userName,
        'email' => $faker->unique()->safeEmail,
        'email_verified_at' => time(),
        'password' => bcrypt('Passwort'),
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
        'grade' => $faker->numberBetween(5, 11),
        'letter' => strtoupper($faker->lexify('?')),
        'exchange_id' => 0,
        'first_friend' => 0,
        'second_friend' => 0,
        'third_friend' => 0,
        'first_wish' => $faker->numberBetween(1, 20),
        'second_wish' => $faker->numberBetween(1, 20),
        'third_wish' => $faker->numberBetween(1, 20),
        'project_id' => 0,
        'role' => 1,
    ];
});
