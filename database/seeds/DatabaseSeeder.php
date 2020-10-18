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
        // Admin erstellen

        DB::table('admins')->insert([
            'user_name' => 'Masteradmin',
            'email' => 'pauljustus27@gmail.com',
            'email_verified_at' => Carbon::now(),
            'password' => bcrypt('Passwort'),
        ]);


        // Zeitplan erstellen

        DB::table('schedule')->insert([
            'begin' => Carbon::now(),
            'control' => Carbon::now()->add(1, 'day'),
            'registration' => Carbon::now()->add(2, 'day'),
            'sort_students' => Carbon::now()->add(3, 'day'),
            'exchange' => Carbon::now()->add(4, 'day'),
            'projects' => Carbon::now()->add(5, 'day'),
            'end' => Carbon::now()->add(6, 'day'),
        ]);


        // Projekte, Schüler und Projektleiter erstellen, falls die App noch in Entwicklung ist

        if (App::environment('local')) {
            $this->call(StudentsTableSeeder::class);
            $this->call(LeaderStudentsTableSeeder::class);
            $this->call(LeadersTableSeeder::class);
        }

    }
}
