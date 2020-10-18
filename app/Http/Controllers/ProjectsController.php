<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Project;
use App\Http\Resources\Project as ProjectResource;
use App\Http\Resources\Student as StudentResource;
use App\Http\Resources\Leader as LeaderResource;
use App\Http\Resources\Message as MessageResource;

class ProjectsController extends Controller
{
    /**
     * Display a listing of the projects.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $projects = Project::all();

        return ProjectResource::collection($projects);
    }

    /**
     * Display the specified project.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show_little($id)
    {
        $project = Project::findOrFail($id)->get(['id', 'title', 'min_grade', 'max_grade'])->where('id', $id)->first();

        return new ProjectResource($project);
    }

    /**
     * Display the specified project.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $project = Project::findOrFail($id);

        $project->first_day_begin = date_format(date_create($project->first_day_begin), 'H:i');
        $project->first_day_end = date_format(date_create($project->first_day_end), 'H:i');
        $project->second_day_begin = date_format(date_create($project->second_day_begin), 'H:i');
        $project->second_day_end = date_format(date_create($project->second_day_end), 'H:i');

        return new ProjectResource($project);
    }

}
