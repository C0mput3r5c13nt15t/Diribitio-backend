<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Leader;
use App\Project;
use App\Message;
use App\Http\Resources\Leader as LeaderResource;
use App\Http\Resources\LimitedStudent as LimitedStudentResource;
use App\Http\Resources\Project as ProjectResource;
use App\Http\Resources\Message as MessageResource;

class LeadersController extends Controller
{
    /**
     * Display the leader associated with the provided token.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function self()
    {
        $leader = $this->authUser();

        return new LeaderResource($leader);
    }

    /**
     * Display the leader associated with the provided token.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function leaded_project()
    {
        $leader = $this->authUser();

        if ($leader->project_id !== 0 && $leader->leaded_project()->exists()) {
            $leaded_project = $leader->leaded_project;

            $leaded_project->first_day_begin = date_format(date_create($leaded_project->first_day_begin), 'H:i');
            $leaded_project->first_day_end = date_format(date_create($leaded_project->first_day_end), 'H:i');
            $leaded_project->second_day_begin = date_format(date_create($leaded_project->second_day_begin), 'H:i');
            $leaded_project->second_day_end = date_format(date_create($leaded_project->second_day_end), 'H:i');

            $leaded_project->messages = $leaded_project->messages;

            $leaded_project['participants'] = LimitedStudentResource::collection($leaded_project->participants()->get());
        } else {
            return response()->json('Sie leiten ' . config('diribitio.no_project_noun') . '.', 401);
        }

        return new ProjectResource($leaded_project);
    }

    /**
     * Store a newly created message and assign it to the project associated with the leader associated with the provided token.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function store_leaded_project_message(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json('Die mitgesendeten Daten der Anfrage sind ungültig.', 406);
        }

        $leader = $this->authUser();

        if ($leader->project_id != 0 && $leader->leaded_project()->exists()) {
            $leaded_project = $leader->leaded_project;
            $message = new Message;

            $message->message = $request->input('message');
            $message->sender_name = $leader->user_name;

            if ($leaded_project->messages()->save($message)) {
                return new MessageResource($message);
            } else {
                return response()->json('Es gab einen unbekannten Fehler.', 500);
            }
        } else {
            return response()->json('Sie leiten noch ' . config('diribitio.no_project_noun') . '.', 403);
        }
    }

    /**
     * Store a newly created project and assign the leader associated with the provided tokenb to it.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store_project(Request $request) {
        if ($request->hasFile('image')) {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string',
                'image' => 'image|max:1999',
                'descr' => 'required|string',
                'leader_name' => 'required|string',
                'cost' => 'required|numeric|min:0',
                'first_day_begin' => 'required|date_format:"H:i"',
                'first_day_end' => 'required|date_format:"H:i"|after:first_day_begin',
                'second_day_begin' => 'required|date_format:"H:i"',
                'second_day_end' => 'required|date_format:"H:i"|after:second_day_begin',
                'min_grade' => 'required|numeric',
                'max_grade' => 'required|numeric|gte:min_grade',
                'min_participants' => 'required|numeric|min:0',
                'max_participants' => 'required|numeric|gte:min_participants',
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string',
                'descr' => 'required|string',
                'leader_name' => 'required|string',
                'cost' => 'required|numeric|min:0',
                'first_day_begin' => 'required|date_format:"H:i"',
                'first_day_end' => 'required|date_format:"H:i"|after:first_day_begin',
                'second_day_begin' => 'required|date_format:"H:i"',
                'second_day_end' => 'required|date_format:"H:i"|after:second_day_begin',
                'min_grade' => 'required|numeric',
                'max_grade' => 'required|numeric|gte:min_grade',
                'min_participants' => 'required|numeric|min:0',
                'max_participants' => 'required|numeric|gte:min_participants',
            ]);
        }

        if ($validator->fails()) {
            return response()->json('Die mitgesendeten Daten der Anfrage sind ungültig.', 406);
        }

        $leader = $this->authUser();

        if ($leader->project_id === 0) {
            $project = new Project;

            $project->authorized = 0;
            $project->title = $request->input('title');
            $project->descr = $request->input('descr');
            $project->leader_name = $request->input('leader_name');
            $project->leader_id = $leader->id;
            $project->leader_type = 'App\Leader';
            $project->cost = $request->input('cost');
            $project->first_day_begin = $request->input('first_day_begin');
            $project->first_day_end = $request->input('first_day_end');
            $project->second_day_begin = $request->input('second_day_begin');
            $project->second_day_end = $request->input('second_day_end');
            $project->min_grade = $request->input('min_grade');
            $project->max_grade = $request->input('max_grade');
            $project->min_participants = $request->input('min_participants');
            $project->max_participants = $request->input('max_participants');

            if ($request->hasFile('image')) {
                $filenameWithExt = $request->file('image')->getClientOriginalName();
                $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                $extension = $request->file('image')->getClientOriginalExtension();
                $fileNameToStore= $filename.'_'.time().'.'.$extension;
                $path = $request->file('image')->storeAs('public/images', $fileNameToStore);
                $project->image = $fileNameToStore;
            }

            try {
                if ($leader->leaded_project()->save($project)) {

                    $leader->project_id = $project->id;

                    if ($leader->save()) {
                        return response()->json(['message' => config('diribitio.definite_article_project_noun') . ' wurde erfolgreich erstellt.'], 200);
                    } else {
                        return response()->json('Es gab beim Aktualisieren ihres Accounts.', 500);
                    }
                } else {
                    return response()->json('Es gab einen unbekannten Fehler.', 500);
                }
            } catch (\Illuminate\Database\QueryException $e) {
                return response()->json('Scheinbar ist der Titel bereits vergeben.', 500);
            }
        } else {
            return response()->json('Sie leiten bereits' . config('diribitio.indefinite_article_project_noun') . '.', 403);
        }
    }

    /**
     * Update the project associated with the leader associated with the provided token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update_project(Request $request) {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'descr' => 'required|string',
            'leader_name' => 'required|string',
            'cost' => 'required|numeric|min:0',
            'first_day_begin' => 'required|date_format:"H:i"',
            'first_day_end' => 'required|date_format:"H:i"|after:first_day_begin',
            'second_day_begin' => 'required|date_format:"H:i"',
            'second_day_end' => 'required|date_format:"H:i"|after:second_day_begin',
            'min_grade' => 'required|numeric',
            'max_grade' => 'required|numeric|gte:min_grade',
            'min_participants' => 'required|numeric|min:0',
            'max_participants' => 'required|numeric|gte:min_participants',
        ]);

        if ($validator->fails()) {
            return response()->json('Die mitgesendeten Daten der Anfrage sind ungültig.', 406);
        }

        $leader = $this->authUser();

        if ($leader->project_id != 0 && $leader->leaded_project()->exists()) {
            $project = Project::findOrFail($leader->project_id);

            $project->title = $request->input('title');
            $project->descr = $request->input('descr');
            $project->leader_name = $request->input('leader_name');
            $project->cost = $request->input('cost');
            $project->first_day_begin = $request->input('first_day_begin');
            $project->first_day_end = $request->input('first_day_end');
            $project->second_day_begin = $request->input('second_day_begin');
            $project->second_day_end = $request->input('second_day_end');
            $project->min_grade = $request->input('min_grade');
            $project->max_grade = $request->input('max_grade');
            $project->min_participants = $request->input('min_participants');
            $project->max_participants = $request->input('max_participants');

            try {
                if ($leader->leaded_project()->save($project)) {
                    return response()->json(['message' => config('diribitio.definite_article_project_noun') . ' wurde erfolgreich aktualisiert.'], 200);
                } else {
                    return response()->json('Es gab einen unbekannten Fehler.', 500);
                }
            } catch (\Illuminate\Database\QueryException $e) {
                return response()->json('Scheinbar ist der neue Titel bereits vergeben.', 500);
            }
        } else {
            return response()->json('Sie leiten noch ' . config('diribitio.no_project_noun') . '.', 403);
        }
    }

    /**
     * Update the project associated with the leader associated with the provided token outside the normal time frame with admin authorization.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function touch_up_project(Request $request) {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'descr' => 'required|string',
            'leader_name' => 'required|string',
            'cost' => 'required|numeric',
            'first_day_begin' => 'required|date_format:"H:i"',
            'first_day_end' => 'required|date_format:"H:i"|after:first_day_begin',
            'second_day_begin' => 'required|date_format:"H:i"',
            'second_day_end' => 'required|date_format:"H:i"|after:second_day_begin',
            'min_grade' => 'required|numeric',
            'max_grade' => 'required|numeric|gte:min_grade',
            'min_participants' => 'required|numeric|min:0',
            'max_participants' => 'required|numeric|gte:min_participants',
        ]);

        if ($validator->fails()) {
            return response()->json('Die mitgesendeten Daten der Anfrage sind ungültig.', 406);
        }

        $leader = $this->authUser();

        if ($leader->project_id != 0 && $leader->leaded_project()->exists()) {
            $project = Project::findOrFail($leader->project_id);

            if ($project->editable != 1) {
                return response()->json(config('diribitio.definite_article_project_noun') . ' kann zurzeit nur mit der Erlaubnis eines Admins bearbeitet werden.', 403);
            }

            $project->editable = false;
            $project->title = $request->input('title');
            $project->descr = $request->input('descr');
            $project->leader_name = $request->input('leader_name');
            $project->cost = $request->input('cost');
            $project->first_day_begin = $request->input('first_day_begin');
            $project->first_day_end = $request->input('first_day_end');
            $project->second_day_begin = $request->input('second_day_begin');
            $project->second_day_end = $request->input('second_day_end');
            $project->min_grade = $request->input('min_grade');
            $project->max_grade = $request->input('max_grade');
            $project->min_participants = $request->input('min_participants');
            $project->max_participants = $request->input('max_participants');

            try {
                if ($leader->leaded_project()->save($project)) {
                    return response()->json(['message' => config('diribitio.definite_article_project_noun') . ' wurde erfolgreich aktualisiert.'], 200);
                } else {
                    return response()->json('Es gab einen unbekannten Fehler.', 500);
                }
            } catch (\Illuminate\Database\QueryException $e) {
                return response()->json('Scheinbar ist der neue Titel bereits vergeben.', 500);
            }
        } else {
            return response()->json('Sie leiten noch ' . config('diribitio.no_project_noun') . '.', 403);
        }
    }

    /**
     * Remove the specified message from the project associated with the leader associated with the provided token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy_leaded_project_message(Request $request, $id)
    {
        $leader = $this->authUser();

        if ($leader->project_id != 0 && $leader->leaded_project()->exists()) {
            $leaded_project = $leader->leaded_project;

            $message = Message::findOrFail($id);

            if ($leaded_project->messages()->where('id', $id)->delete()) {
                return;
            } else {
                return response()->json('Es gab einen unbekannten Fehler.', 500);
            }
        } else {
            return response()->json('Sie leiten noch ' . config('diribitio.no_project_noun') . '.', 403);
        }
    }

    /**
     * Remove the leader associated with the provided token from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function self_destroy()
    {
        $leader = $this->authUser();

        if ($leader->project_id == 0 && !$leader->leaded_project()->exists()) {
            if ($leader->delete()) {
                return response()->json(['message' => 'Ihr Account wurde erfolgreich gelöscht.'], 200);
            } else {
                return response()->json('Es gab einen unbekannten Fehler.', 500);
            }
        } else {
            return response()->json('Ihr konnte nicht gelöscht werden, da sie ' . config('diribitio.indefinite_article_project_noun') . ' leiten.', 403);
        }
    }
}
