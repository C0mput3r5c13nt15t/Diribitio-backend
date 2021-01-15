<?php

use Illuminate\Database\Seeder;

class LeadersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\Leader::class, 250)->create()->each(function($leader) {

            $project = factory(App\Project::class)->create([
                'leader_id' => $leader->id,
            ]);

            $leader->project_id = $project->id;
            $leader->save();
        });
    }
}
