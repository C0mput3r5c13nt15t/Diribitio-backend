<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Model;
use Faker\Generator as Faker;

$factory->define(App\Leader::class, function (Faker $faker) {
    return [
        'user_name' => $faker->unique()->userName,
        'email' => $faker->unique()->safeEmail,
        'email_verified_at' => time(),
        'password' => bcrypt('Passwort'),
        'project_id' => 0,
    ];
});
