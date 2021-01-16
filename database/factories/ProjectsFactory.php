<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Model;
use Faker\Generator as Faker;

$factory->define(App\Project::class, function (Faker $faker) {
    return [
        'authorized' => 1,
        'title' => $faker->unique()->text(20),
        'image' => 'project.jpg',
        'descr' => $faker->text(75),
        'leader_name' => $faker->title . ' ' . $faker->lastName,
        'leader_id' => 0,
        'leader_type' => 'App\Leader',
        'cost' => 0,
        'first_day_begin' => $faker->time,
        'first_day_end' => $faker->time,
        'second_day_begin' => $faker->time,
        'second_day_end' => $faker->time,
        'min_grade' => 5,
        'max_grade' => 11,
        'min_participants' => 1,
        'max_participants' => 45
    ];
});
