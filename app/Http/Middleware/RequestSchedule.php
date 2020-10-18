<?php

namespace App\Http\Middleware;

use Closure;
use App\Schedule;

class RequestSchedule
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, ...$times)
    {
        $schedule = Schedule::findOrFail(1);

        $begin = $schedule->{$times[0]};
        $end = $schedule->{$times[1]};
        if (count($times) == 4) {
            $second_begin = $schedule->{$times[2]};
            $second_end = $schedule->{$times[3]};
        }
        $today = date("Y-m-d");

        if ($today > $begin && $today <= $end) {
        } else {
            if (count($times) == 4) {
                if ($today > $second_begin && $today <= $second_end) {
                } else {
                    abort(response()->json('Diese Anfrage ist zur Zeit nicht zulässig.', 423));
                }
            } else {
                abort(response()->json('Diese Anfrage ist zur Zeit nicht zulässig.', 423));
            }
        }

        return $next($request);
    }
}
