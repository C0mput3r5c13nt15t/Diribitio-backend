<?php

use Illuminate\Database\Seeder;

class LeaderStudentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\Student::class, 2)->create()->each(function($student) {

            $project = factory(App\Project::class)->create([
                'leader_name' => $student->first_name . ' ' . $student->last_name,
                'leader_id' => $student->id,
                'leader_type' => 'App\Student',
            ]);

            if ($student->first_wish == $project->id && $project->id > 1) {
                $student->first_wish -= 1;
            } else {
                $student->first_wish += 1;
            }

            if ($student->second_wish == $project->id && $project->id > 1) {
                $student->second_wish -= 1;
            } else {
                $student->second_wish += 1;
            }

            if ($student->third_wish == $project->id && $project->id > 1) {
                $student->third_wish -= 1;
            } else {
                $student->third_wish += 1;
            }

            $student->project_id = $project->id;
            $student->role = 2;
            $student->save();
        });
    }
}
