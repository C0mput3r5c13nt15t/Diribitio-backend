<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Allows the masteradmin to craete an account

        DB::table('sign_up_emails')->insert([
            'email' => 'masteradmin@yourschool.onmicrosoft.com',
            'created_by' => '0',
        ]);


        // Creates a basic schedule with one day interwalls starting the day after creation

        DB::table('schedule')->insert([
            'begin' => Carbon::now(),
            'control' => Carbon::now()->add(1, 'day'),
            'registration' => Carbon::now()->add(2, 'day'),
            'sort_students' => Carbon::now()->add(3, 'day'),
            'exchange' => Carbon::now()->add(4, 'day'),
            'projects' => Carbon::now()->add(5, 'day'),
            'end' => Carbon::now()->add(6, 'day'),
        ]);


        // If in development this generates test students, leaders and projects

        if (App::environment('local')) {
            $this->call(StudentsTableSeeder::class);
            $this->call(LeaderStudentsTableSeeder::class);
            $this->call(LeadersTableSeeder::class);
        }

    }
}
