<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Model;
use Faker\Generator as Faker;

$factory->define(App\Project::class, function (Faker $faker) {
    return [
        'authorized' => random_int(0, 1),
        'title' => $faker->unique()->text(10),
        'image' => 'project.jpg',
        'descr' => $faker->text(75),
        'leader_name' => $faker->title . ' ' . $faker->lastName,
        'leader_id' => 0,
        'leader_type' => 'App\Leader',
        'cost' => random_int(0, 5),
        'first_day_begin' => $faker->time,
        'first_day_end' => $faker->time,
        'second_day_begin' => $faker->time,
        'second_day_end' => $faker->time,
        'min_grade' => random_int(5, 7),
        'max_grade' => random_int(7, 11),
        'min_participants' => random_int(1, 5),
        'max_participants' => random_int(5, 15)
    ];
});
