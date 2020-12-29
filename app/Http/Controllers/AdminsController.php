<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Admin;
use App\Student;
use App\Leader;
use App\Project;
use App\Message;
use App\Exchange;
use App\Schedule;
use App\Http\Resources\Admin as AdminResource;
use App\Http\Resources\Student as StudentResource;
use App\Http\Resources\LimitedStudent as LimitedStudentResource;
use App\Http\Resources\Leader as LeaderResource;
use App\Http\Resources\LimitedLeader as LimitedLeaderResource;
use App\Http\Resources\Project as ProjectResource;
use App\Http\Resources\Exchange as ExchangeResource;
use App\Http\Resources\Message as MessageResource;
use App\Http\Resources\Schedule as ScheduleResource;
use App\Notifications\LeaderProjectDeleted;
use App\Notifications\StudentProjectDeleted;
use App\Notifications\AssistantProjectDeleted;

class AdminsController extends Controller
{
    /**
     * Display a listing of the students.
     *
     * @return \Illuminate\Http\Response
     */
    public function index_students()
    {
        $students = Student::paginate(10);

        return StudentResource::collection($students);
    }

    /**
     * Display a listing of the searched students.
     *
     * @return \Illuminate\Http\Response
     */
    public function search_index_students(Request $request)
    {
        $students = Student::all();

        $searchEmail = $request->input('search_email');
        $searchFirstName = $request->input('search_first_name');
        $searchLastName = $request->input('search_last_name');
        $searchClass = $request->input('search_class');

        if (!$searchValue) {
            return;
        }

        $students = $students->filter(function ($student) use ($searchValue) {
            $class = strval($student->grade) . strval($student->letter);
            if (strpos($searchEmail, $student->email) !== false && strpos($searchFirstName, $student->first_name) !== false && strpos($searchLastName, $student->last_name) !== false && strpos($searchClass, $class) !== false) {
                return true;
            } else {
                return false;
            }
        });

        return StudentResource::collection($students);
    }

    /**
     * Display a listing of the students names.
     *
     * @return \Illuminate\Http\Response
     */
    public function little_index_students()
    {
        $students = Student::all(['id', 'first_name', 'last_name']);

        return StudentResource::collection($students);
    }

    /**
     * Display a listing of the leaders.
     *
     * @return \Illuminate\Http\Response
     */
    public function index_leaders()
    {
        $leaders = Leader::all();

        return LeaderResource::collection($leaders);
    }

    /**
     * Display a listing of the exchanges.
     *
     * @return \Illuminate\Http\Response
     */
    public function index_exchanges()
    {
        $exchanges = Exchange::all();

        $exchanges->each(function($exchange) {
            $sender = $exchange->sender()->first();
            $receiver = $exchange->receiver()->first();
            $exchange->sender = new LimitedStudentResource($sender);
            $exchange->receiver = new LimitedStudentResource($receiver);
        });

        return ExchangeResource::collection($exchanges);
    }

    /**
     * Display a listing of the projects.
     *
     * @return \Illuminate\Http\Response
     */
    public function index_projects()
    {
        $projects = Project::all();

        $projects->each(function($project) {
            $project->messages = $project->messages;

            if ($project->leader_type === 'App\Leader') {
                $leader = $project->leader()->first();
                $project['leader'] = new LimitedLeaderResource($leader);
            } else if ($project->leader_type === 'App\Student') {
                $leader = $project->leader()->first();
                $project['leader'] = new LimitedStudentResource($leader);

                if ($project->assistant_student_leaders()->exists()) {
                    $project->assistant_student_leaders = LimitedStudentResource::collection($project->assistant_student_leaders()->where('id', '!=', $project->leader_id)->get());
                }
            }

            $project['participants'] = LimitedStudentResource::collection($project->participants()->get());
        });

        return ProjectResource::collection($projects);
    }

    /**
     * Display the specified exchange.
     *
     * @return \Illuminate\Http\Response
     */
    public function exchange($id) {
        $exchange = Exchange::findOrFail($id);

        $sender = $exchange->sender()->first();
        $receiver = $exchange->receiver()->first();
        $exchange->sender = new LimitedStudentResource($sender);
        $exchange->receiver = new LimitedStudentResource($receiver);

        return new ExchangeResource($exchange);
    }

    /**
     * Display the specified project.
     *
     * @return \Illuminate\Http\Response
     */
    public function project($id)
    {
        $project = Project::find($id);

        if (!$project) {
            return response()->json(config('diribitio.definite_article_project_noun') . ' konnte nicht gefunden werden.', 200);
        }

        $project->messages = $project->messages()->get();

        if ($project->leader_type === 'App\Leader') {
            $leader = $project->leader()->first();
            $project['leader'] = new LimitedLeaderResource($leader);

            $project->first_day_begin = date_format(date_create($project->first_day_begin), 'H:i');
            $project->first_day_end = date_format(date_create($project->first_day_end), 'H:i');
            $project->second_day_begin = date_format(date_create($project->second_day_begin), 'H:i');
            $project->second_day_end = date_format(date_create($project->second_day_end), 'H:i');

            $project->assistant_student_leaders = [];

        } else if ($project->leader_type === 'App\Student') {
            $leader = $project->leader()->first();
            $project['leader'] = new LimitedStudentResource($leader);

            $project->first_day_begin = date_format(date_create($project->first_day_begin), 'H:i');
            $project->first_day_end = date_format(date_create($project->first_day_end), 'H:i');
            $project->second_day_begin = date_format(date_create($project->second_day_begin), 'H:i');
            $project->second_day_end = date_format(date_create($project->second_day_end), 'H:i');

            if ($project->assistant_student_leaders()->exists()) {
                $project->assistant_student_leaders = LimitedStudentResource::collection($project->assistant_student_leaders()->where('id', '!=', $project->leader_id)->get());
            } else {
                $project->assistant_student_leaders = [];
            }
        }

        $project['participants'] = LimitedStudentResource::collection($project->participants()->get());

        return new ProjectResource($project);
    }

    /**
     * Display the admin associated with the provided token.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function self()
    {
        $admin = $this->authUser();

        return new AdminResource($admin);
    }

    /**
     * Accomplish the specified exchange.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function accomplish_exchange($id)
    {
        $exchange = Exchange::findOrFail($id);

        if ($exchange->accomplished === 0) {
            $sender = $exchange->sender;

            $receiver = $exchange->receiver;

            $sender_project_id = $sender->project_id;

            $sender->project_id = $receiver->project_id;

            $receiver->project_id = $sender_project_id;

            if ($sender->save()) {
                if ($receiver->save()) {
                    $exchange->accomplished = 1;
                    if ($exchange->save()) {
                        return response()->json(['message' => 'Der Tausch wurde erfolgreich durchgeführt.'], 200);
                    }
                } else {
                    return response()->json('Es gab einen Fehler beim Aktualisieren des Accounts des Empfängers.', 500);
                }
            } else {
                return response()->json('Es gab einen Fehler beim Aktualisieren des Accounts des Senders.', 500);
            }
        } else {
            return response()->json('Der Tausch wurde bereits durchgeführt.', 403);
        }
    }

    /**
     * Toggle authorized of the specified project.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function toogle_authorized(Request $request, $id)
    {
        $project = Project::findOrFail($id);

        if ($request->input('authorized') == 0 || $project->editable == 0) {
            if ($project->authorized != $request->input('authorized')) {
                $project->authorized = $request->input('authorized');

                if ($project->save()) {
                    return response()->json(['message' => config('diribitio.definite_article_project_noun') . ' wurde erfolgreich aktualisiert.'], 200);
                } else {
                    return response()->json('Es gab einen unbekannten Fehler.', 500);
                }
            }
        } else {
            return response()->json(config('diribitio.definite_article_project_noun') . ' ist zur Bearbeitung freigegeben und kann währenddessen nicht zugelassen werden.', 500);
        }

    }

    /**
     * Toggle editable of the specified project.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function toogle_editable(Request $request, $id)
    {
        $project = Project::findOrFail($id);

        if ($project->editable != $request->input('editable')) {
            $project->editable = $request->input('editable');
            $project->authorized = false;

            if ($project->save()) {
                return response()->json(['message' => config('diribitio.definite_article_project_noun') . ' wurde erfolgreich aktualisiert.'], 200);
            } else {
                return response()->json('Es gab einen unbekannten Fehler.', 500, 500);
            }
        }

    }

    /**
     * Update the shedule.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update_schedule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'begin' => 'required|date',
            'control' => 'required|date|after:begin',
            'registration' => 'required|date|after:control',
            'sort_students' => 'required|date|after:registration',
            'exchange' => 'required|date|after:sort_students',
            'projects' => 'required|date|after:exchange',
            'end' => 'required|date|after:projects',
        ]);

        if ($validator->fails()) {
            return response()->json('Die mitgesendeten Daten der Anfrage sind ungültig.', 406);
        }

        $schedule = Schedule::findOrFail(1);

        $schedule->begin = $request->input('begin');
        $schedule->control = $request->input('control');
        $schedule->registration = $request->input('registration');
        $schedule->sort_students = $request->input('sort_students');
        $schedule->exchange = $request->input('exchange');
        $schedule->projects = $request->input('projects');
        $schedule->end = $request->input('end');

        if ($schedule->save()) {
            return ['message' => 'Der Zeitplan wurde erfolgreich aktualisiert!'];
        } else {
            return response()->json('Es gab einen Fehler beim Aktualisieren des Zeitplanes.', 500);
        }
    }

    /**
     * Remove the specified leader from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy_leader($id)
    {
        $leader = Leader::findOrFail($id);

        if ($leader->project_id == 0 && !$leader->leaded_project()->exists()) {
            if ($leader->delete()) {
                return response()->json(['message' => config('diribitio.definite_article_project_leader') . ' wurde erfolgreich gelöscht.'], 200);
            } else {
                return response()->json('Es gab einen unbekannten Fehler.', 500);
            }
        } else {
            return response()->json(config('diribitio.definite_article_project_leader') . '  konnte nicht gelöscht werden, da er noch ' . config('diribitio.indefinite_article_project_noun') . ' leitet.', 403);
        }
    }

    /**
     * Remove the specified project from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy_project($id)
    {
        $project = Project::findOrfail($id);

        $messages = $project->messages();

        $project->messages = $messages;

        $participants = $project->participants;

        if ($project->leader_type === 'App\Leader') {
            $leader = $project->leader;

            if ($project->leader()->exists()) {
                $leader->project_id = 0;
                if ($leader->save()) {
                    $leader->notify(new LeaderProjectDeleted());
                } else {
                    return response()->json('Es gab einen unbekannten Fehler.', 500);
                }
            } else {
                #return response()->json(config('diribitio.definite_article_project_noun') . ' wird von niemandem geleitet.', 500);
            }

            if ($messages->exists()) {
                if ($messages->delete()) {
                    if ($project->delete()) {
                        if ($project->image != null && $project->image != '') {
                            Storage::delete('public/images/'. $project->image);
                        }
                        return response()->json(['message' => config('diribitio.definite_article_project_noun') . ' wurde erfolgreich gelöscht.'], 200);
                    } else {
                        return response()->json('Es gab einen unbekannten Fehler.', 500);
                    }
                } else {
                    return response()->json('Es gab einen Fehler beim Löschen der Nachrichten ' . config('diribitio.genitive_project_noun') .  '.', 500);
                }
            } else {
                if ($project->delete()) {
                    if ($project->image != null && $project->image != '') {
                        Storage::delete('public/images/'. $project->image);
                    }
                    return response()->json(['message' => config('diribitio.definite_article_project_noun') . ' wurde erfolgreich gelöscht.'], 200);
                } else {
                    return response()->json('Es gab einen unbekannten Fehler.', 500);
                }
            }
        } else if ($project->leader_type === 'App\Student') {
            if ($project->assistant_student_leaders()->exists()) {
                $student_leader_assistants = $project->assistant_student_leaders;

                $errors = 0;

                $student_leader_assistants->each(function ($leader, $key) use ($project) {
                    if ($leader->id == $project->leader_id) {
                        $leader->notify(new StudentProjectDeleted());
                    } else {
                        $leader->notify(new AssistantProjectDeleted());
                    }
                    $leader->role = 1;
                    $leader->project_id = 0;
                    if ($leader->save()) {
                    } else {
                        $errors += 1;
                    }
                });

                if ($errors != 0) {
                    return response()->json('Es gab ' . strval($errors) . ' Fehler beim Aktualisieren der Accounts der Schüler.', 500);
                }

                unset($project->assistant_student_leaders);
            } else {
                #return response()->json(config('diribitio.definite_article_project_noun') . ' wird von niemandem geleitet und kann deswegen nicht gelöscht werden.', 500);
            }

            if ($messages->exists()) {
                if ($messages->delete()) {
                    if ($project->delete()) {
                        if ($project->image != null && $project->image != '') {
                            Storage::delete('public/images/'. $project->image);
                        }
                        return response()->json(['message' => config('diribitio.definite_article_project_noun') . ' wurde erfolgreich gelöscht.'], 200);
                    } else {
                        return response()->json('Es gab einen unbekannten Fehler.', 500);
                    }
                } else {
                    return response()->json('Es gab einen Fehler beim Löschen der Nachrichten ' . config('diribitio.genitive_project_noun') . '.', 500);
                }
            } else {
                if ($project->delete()) {
                    if ($project->image != null && $project->image != '') {
                        Storage::delete('public/images/'. $project->image);
                    }
                    return response()->json(['message' => config('diribitio.definite_article_project_noun') . ' wurde erfolgreich gelöscht.'], 200);
                } else {
                    return response()->json('Es gab einen unbekannten Fehler.', 500);
                }
            }
        }
    }

    /**
     * Remove the specified exchange from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy_exchange($id)
    {
        $exchange = Exchange::findOrFail($id);

        $sender = $exchange->sender;
        $receiver = $exchange->receiver;

        if ($exchange->delete()) {
            $sender->exchange_id = 0;
            $receiver->exchange_id = 0;

            if ($sender->save() && $receiver->save()) {
                return response()->json(['message' => 'Die Tauschanfrage wurde erfolgreich gelöscht.'], 200);
            } else {
                return response()->json('Es gab einen Fehler beim Aktualisieren der Accouts der tauschenden Schüler.', 500);
            }
        } else {
            return response()->json('Es gab einen unbekannten Fehler.', 500);
        }
    }
}
