<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Schedule;
use App\Http\Resources\Schedule as ScheduleResource;

class ScheduleController extends Controller
{
    /**
     * Display a listing of the shedules.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $schedules = Schedule::all();

        return ScheduleResource::collection($schedules);
    }

    /**
     * Display the specified shedule.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $schedule = Schedule::findOrFail(1);

        return new ScheduleResource($schedule);
    }

}
