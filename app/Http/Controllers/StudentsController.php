<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Student;
use App\Project;
use App\Message;
use App\Exchange;
use App\Http\Resources\Student as StudentResource;
use App\Http\Resources\LimitedStudent as LimitedStudentResource;
use App\Http\Resources\Project as ProjectResource;
use App\Http\Resources\Exchange as ExchangeResource;
use App\Http\Resources\Message as MessageResource;

class StudentsController extends Controller
{
    const $genitive_project_noun = config('diribitio.genitive_project_noun');
    const $definite_article_project_noun = config('diribitio.definite_article_project_noun');
    const $indefinite_article_project_noun = config('diribitio.indefinite_article_project_noun');
    const $no_project_noun = config('diribitio.no_project_noun');

    /**
     * Display the student associated to the provided first_name, last_name, grade and letter.
     *
     * @return \Illuminate\Http\Response
     */
    public function id(Request $request)
    {
        $student = Student::where([
            ['first_name', $request->input('first_name')],
            ['last_name', $request->input('last_name')],
            ['grade', $request->input('grade')],
            ['letter', $request->input('letter')],
            ['role', 1]
        ])->first();

        if ($student) {
            return response()->json(['id' => $student->id], 200);
        } else {
            return response()->json(['id' => 0], 200);
        }
    }

    /**
     * Display the student associated to the provided token.
     *
     * @return \Illuminate\Http\Response
     */
    public function self()
    {
        $student = $this->authUser();

        return new StudentResource($student);
    }

    /**
     * Display the friends associated to the student associated to the provided token.
     *
     * @return \Illuminate\Http\Response
     */
    public function friends()
    {
        $student = $this->authUser();

        $friends = Student::findMany([$student->first_friend, $student->second_friend, $student->third_friend]);

        return LimitedStudentResource::collection($friends);
    }


    /**
     * Display the exchange associated to the student associated to the provided token.
     *
     * @return \Illuminate\Http\Response
     */
    public function exchange()
    {
        $student = $this->authUser();

        if ($student->exchange_id) {
            $exchange = $student->exchange;
        } else {
            return;
        }

        return new ExchangeResource($exchange);
    }

    /**
     * Display the sender of the exchange associated to the student associated to the provided token.
     *
     * @return \Illuminate\Http\Response
     */
    public function exchange_sender()
    {
        $student = $this->authUser();

        if ($student->exchange_id) {
            $sender = $student->exchange->sender;
        } else {
            return;
        }

        return new LimitedStudentResource($sender);
    }

    /**
     * Display the receiver of the exchange associated to the student associated to the provided token.
     *
     * @return \Illuminate\Http\Response
     */
    public function exchange_receiver()
    {
        $student = $this->authUser();

        if ($student->exchange_id) {
            $receiver = $student->exchange->receiver;
        } else {
            return;
        }

        return new LimitedStudentResource($receiver);
    }

    /**
     * Display the exchange requests associated to the student associated to the provided token.
     *
     * @return \Illuminate\Http\Response
     */
    public function exchange_requests()
    {
        $student = $this->authUser();

        $exchanges = Exchange::all()->where('receiver_id', $student->id);

        $exchanges->each(function($exchange) {
            $sender = $exchange->sender()->first();
            $receiver = $exchange->receiver()->first();
            $exchange->sender = new LimitedStudentResource($sender);
            $exchange->receiver = new LimitedStudentResource($receiver);
        });

        return ExchangeResource::collection($exchanges);
    }

    /**
     * Display the project associated to the student associated to the provided token.
     *
     * @return \Illuminate\Http\Response
     */
    public function project()
    {
        $student = $this->authUser();

        if ($student->project_id !== 0 && $student->project()->exists()) {
            $project = $student->project;

            if ($project->leader_type === 'App\Student') {
                $leader = $project->leader()->first();
                $project['leader'] = new LimitedStudentResource($leader);
            }

            $project->first_day_begin = date_format(date_create($project->first_day_begin), 'H:i');
            $project->first_day_end = date_format(date_create($project->first_day_end), 'H:i');
            $project->second_day_begin = date_format(date_create($project->second_day_begin), 'H:i');
            $project->second_day_end = date_format(date_create($project->second_day_end), 'H:i');

            $project->messages = $project->messages;

            $project->participants = LimitedStudentResource::collection($project->participants);

            if ($project->assistant_student_leaders()->exists()) {
                $project->assistant_student_leaders = LimitedStudentResource::collection($project->assistant_student_leaders()->where('id', '!=', $project->leader_id)->get());
            } else {
                $project->assistant_student_leaders = [];
            }

        } else {
            return response()->json('Du hast noch ' . $no_project_noun . '.', 401);
        }

        return new ProjectResource($project);
    }

    /**
     * Display the project associated to the leader of type student associated to the provided token.
     *
     * @return \Illuminate\Http\Response
     */
    public function leaded_project()
    {
        $student = $this->authUser();

        if ($student->role === 2 && $student->project_id !== 0 && $student->leaded_project()->exists()) {
            $leaded_project = $student->leaded_project;

            $leaded_project->first_day_begin = date_format(date_create($leaded_project->first_day_begin), 'H:i');
            $leaded_project->first_day_end = date_format(date_create($leaded_project->first_day_end), 'H:i');
            $leaded_project->second_day_begin = date_format(date_create($leaded_project->second_day_begin), 'H:i');
            $leaded_project->second_day_end = date_format(date_create($leaded_project->second_day_end), 'H:i');

            $leaded_project->messages = $leaded_project->messages;

            $leaded_project['participants'] = LimitedStudentResource::collection($leaded_project->participants()->get());

            if ($leaded_project->assistant_student_leaders()->exists()) {
                $leaded_project->assistant_student_leaders = LimitedStudentResource::collection($leaded_project->assistant_student_leaders()->where('id', '!=', $leaded_project->leader_id)->get());
            } else {
                $leaded_project->assistant_student_leaders = [];
            }
        } else if ($student->role === 2 && $student->project_id !== 0 && $student->project()->exists()) {
            $leaded_project = $student->project;

            if ($leaded_project->leader_type === 'App\Student') {
                $leader = $leaded_project->leader()->first();
                $leaded_project['leader'] = new LimitedStudentResource($leader);
            }

            $leaded_project->first_day_begin = date_format(date_create($leaded_project->first_day_begin), 'H:i');
            $leaded_project->first_day_end = date_format(date_create($leaded_project->first_day_end), 'H:i');
            $leaded_project->second_day_begin = date_format(date_create($leaded_project->second_day_begin), 'H:i');
            $leaded_project->second_day_end = date_format(date_create($leaded_project->second_day_end), 'H:i');

            $leaded_project->messages = $leaded_project->messages;

            $leaded_project['participants'] = LimitedStudentResource::collection($leaded_project->participants()->get());

            if ($leaded_project->assistant_student_leaders()->exists()) {
                $leaded_project->assistant_student_leaders = LimitedStudentResource::collection($leaded_project->assistant_student_leaders()->where('id', '!=', $leaded_project->leader_id)->get());
            } else {
                $leaded_project->assistant_student_leaders = [];
            }
        } else {
            return response()->json('Du leitest noch ' . $no_project_noun . '.', 401);
        }

        return new ProjectResource($leaded_project);
    }

    /**
     * Store a newly created exchange and assign the student associated to the provided token to it.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store_exchange(Request $request)
    {
        $student = $this->authUser();

        if ($student->role === 1 && $student->project_id !== 0 && $student->exchange_id === 0) {
            $exchange = new Exchange;

            $exchange->sender_id = $student->id;
            $exchange->receiver_id = $request->input('receiver_id');

            $receiver = Student::findOrFail($exchange->receiver_id);

            if ($receiver->role === 1 && $receiver->project_id !== 0 && $receiver->exchange_id === 0 && $receiver->project_id !== $student->project_id) {
                if ($exchange->sender_id != $exchange->receiver_id) {
                    if ($student->sended_exchange()->save($exchange)) {

                        $student->exchange_id = $exchange->id;

                        if ($student->save()) {
                            return response()->json(['message' => 'Die Tauschanfrage wurde erfolgreich gestelt.'], 200);
                        } else {
                            return response()->json('Es gab einen Fehler beim Aktualisieren deines Accounts.', 500);
                        }
                    } else {
                        return response()->json('Es gab einen unbekannten Fehler.', 500);
                    }
                } else {
                    return response()->json('Du kannst nicht mit dir selber tauschen.', 403);
                }
            } else if ($receiver->role != 1) {
                return response()->json('Der angegebene Empfänger leitet bereits ' . $indefinite_article_project_noun . ' und kann deswegen nicht tauschen.', 403);
            } else if ($receiver->exchange_id != 0) {
                return response()->json('Der angegebene Empfänger hat bereits eine Tauschanfrage gestellt bzw. bereits getauscht.', 403);
            } else if ($receiver->project_id == 0) {
                return response()->json('Der angegebene Empfänger hat noch ' . $no_project_noun . ' und kann somit auch nicht tauschen.', 403);
            } else if ($receiver->project_id == $student->project_id) {
                return response()->json('Du kannst nicht mit einer Person ' . $genitive_project_noun . ' tauschen, an dem du bereits telnimmst.', 403);
            } else {
                return response()->json('Es gab einen unbekannten Fehler.', 500);
            }

        } else {
            if ($student->role != 1 ) {
                return response()->json('Du leites bereits ' . $indefinite_article_project_noun . ' und kannst deswegen nicht tauschen.', 403);
            } else if ($student->exchange_id != 0 ) {
                return response()->json('Du hast bereits eine Tauschanfrage gestellt bzw. bereits getauscht.', 403);
            } else if ($student->project_id == 0 ) {
                return response()->json('Du hast noch ' . $no_project_noun . ' und kannst somit auch nicht tauschen.', 403);
            } else {
                return response()->json('Es gab einen unbekannten Fehler.', 500);
            }
        }
    }

    /**
     * Store a newly created project in storage and assign the student associated to the provided token to it.
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

        if (!config('diribitio.allow_student_projects')) {
            return response()->json('Du bist als Schüler nicht berechtigt ' . $indefinite_article_project_noun . ' zu erstellen.', 403);
        }

        if ($validator->fails()) {
            return response()->json('Die mitgesendeten Daten der Anfrage sind ungültig.', 406);
        }

        $student = $this->authUser();

        if ($student->role === 1 && $student->project_id === 0) {
            $project = new Project;

            $project->authorized = 0;
            $project->title = $request->input('title');
            $project->descr = $request->input('descr');
            $project->leader_name = $request->input('leader_name');
            $project->leader_id = $student->id;
            $project->leader_type = 'App\Student';
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

            if ($student->leaded_project()->save($project)) {

                $student->role = 2;
                $student->project_id = $project->id;

                try {
                    if ($student->save()) {
                        return response()->json(['message' => $definite_article_project_noun . ' wurde erfolgreich erstellt.'], 200);
                    } else {
                        return response()->json('Es gab einen Fehler beim Aktualisieren deines Accounts.', 500);
                    }
                } catch (\Illuminate\Database\QueryException $e) {
                    return response()->json('Scheinbar ist der Titel bereits vergeben.', 500);
                }
            } else {
                return response()->json('Es gab einen unbekannten Fehler.', 500);
            }
        } else {
            return response()->json('Du leitest bereits ' . $indefinite_article_project_noun . '.', 403);
        }
    }

    /**
     * Store a newly created message and assign it to the project associated to the leader of type student associated to the provided token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store_leaded_project_message(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|min:1',
        ]);

        if (!config('diribitio.allow_student_projects')) {
            return response()->json('Du bist als Schüler nicht berechtigt eine Nachrich zu senden.', 403);
        }

        if ($validator->fails()) {
            return response()->json('Die mitgesendeten Daten der Anfrage sind ungültig.', 406);
        }

        $student = $this->authUser();

        if ($student->role === 2 && $student->project_id != 0  && $student->leaded_project()->exists()) {
            $leaded_project = $student->leaded_project;

            $message = new Message;

            $message->message = $request->input('message');

            if ($leaded_project->messages()->save($message)) {
                return new MessageResource($message);
            } else {
                return response()->json('Es gab einen unbekannten Fehler.', 500);
            }
        } else if ($student->role === 2 && $student->project_id != 0 && $student->project()->exists()) {
            $leaded_project = $student->project;

            $message = new Message;

            $message->message = $request->input('message');

            if ($leaded_project->messages()->save($message)) {
                return new MessageResource($message);
            } else {
                return response()->json('Es gab einen unbekannten Fehler.', 500);
            }
        } else {
            return response()->json('Du leitest ' . $no_project_noun . '.', 403);
        }
    }

    /**
     * Update the student associated to the provided token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function self_update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|min:1',
            'last_name' => 'required|string|min:1',
            'grade' => 'required|numeric|min:0',
            'letter' => 'required|alpha|min:1|max:1',
            'first_friend' => 'required|numeric',
            'second_friend' => 'required|numeric',
            'third_friend' => 'required|numeric',
            'first_wish' => 'required|numeric|min:1',
            'second_wish' => 'required|numeric|min:1|different:first_wish',
            'third_wish' => 'required|numeric|min:1|different:first_wish|different:second_wish',
        ]);

        if ($validator->fails()) {
            return response()->json('Die mitgesendeten Daten der Anfrage sind ungültig.', 406);
        }

        $student = $this->authUser();

        $student->first_name = $request->input('first_name');
        $student->last_name = $request->input('last_name');
        $student->grade = $request->input('grade');
        $student->letter = $request->input('letter');
        $student->first_friend = $request->input('first_friend');
        $student->second_friend = $request->input('second_friend');
        $student->third_friend = $request->input('third_friend');
        $student->first_wish = $request->input('first_wish');
        $student->second_wish = $request->input('second_wish');
        $student->third_wish = $request->input('third_wish');

        if ($student->save()) {
            return response()->json(['message' => 'Dein Account wurde erfolgreich aktualisiert.'], 200);
        } else {
            return response()->json('Es gab einen unbekannten Fehler.', 500);
        }
    }

    /**
     * Promote the specific student to a leader assistant.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function promote_student($id)
    {
        $student = $this->authUser();

        if (!config('diribitio.allow_student_projects')) {
            return response()->json('Du bist als Schüler nicht berechtigt einen Assistenten zu ernennen.', 403);
        }

        if ($student->role === 2 && $student->project_id != 0  && $student->leaded_project()->exists()) {
            $leaded_project = $student->leaded_project;

            $promoted_student = Student::findOrFail($id);

            if ($promoted_student->role === 1 && $promoted_student->project_id === 0) {
                $promoted_student->role = 2;
                $promoted_student->project_id = $leaded_project->id;

                if ($promoted_student->save()) {
                    return response()->json(['message' => 'Der Schüler wurde erfolgreich zu einem Assistenten ernannt.'], 200);
                } else {
                    return response()->json('Es gab einen unbekannten Fehler.', 500);
                }
            } else {
                return response()->json('Der Schüler leitet bereits ' . $indefinite_article_project_noun . 'oder ist ein Assistent davon.', 403);
            }
        } else {
            return response()->json('Du bist nicht der Leiter' . $genitive_project_noun . '.', 403);
        }
    }

    /**
     * Suspend the student associated to the provided token from leader assistant.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function quit_assistant()
    {
        $student = $this->authUser();

        if (!config('diribitio.allow_student_projects')) {
            return response()->json('Du bist als Schüler nicht berechtigt als Assistenten zu kündigen.', 403);
        }

        if ($student->role === 2 && $student->project_id != 0  && !$student->leaded_project()->exists()) {
            $student->role = 1;
            $student->project_id = 0;

            if ($student->save()) {
                return response()->json(['message' => 'Du bist nun kein Assistent mehr.'], 200);
            } else {
                return response()->json('Es gab einen unbekannten Fehler.', 500);
            }
        } else {
            return response()->json('Du bist kein Assistent ' . $genitive_project_noun . '.', 403);
        }
    }

    /**
     * Suspend the specific student from leader assistant.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function suspend_student($id)
    {
        $student = $this->authUser();

        if (!config('diribitio.allow_student_projects')) {
            return response()->json('Du bist als Schüler nicht berechtigt einen Assistenten zu suspendieren.', 403);
        }

        if ($student->role === 2 && $student->project_id != 0  && $student->leaded_project()->exists()) {
            $leaded_project = $student->leaded_project;

            $suspended_student = Student::findOrFail($id);

            if ($suspended_student->role === 2 && $suspended_student->project_id === $leaded_project->id) {
                $suspended_student->role = 1;
                $suspended_student->project_id = 0;

                if ($suspended_student->save()) {
                    return response()->json(['message' => 'Der Schüler wurde erfolgreich von seiner Assistenz suspendiert.'], 200);
                } else {
                    return response()->json('Es gab einen unbekannten Fehler.', 500);
                }
            } else {
                return response()->json('Der Schüler ist kein Assistent' . $genitive_project_noun . '.', 403);
            }
        } else {
            return response()->json('Du bist nicht der Leiter ' . $genitive_project_noun . '.', 403);
        }
    }

    /**
     * Confirm the specific exchange associated to the receiver of type student associated to the provided token.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function confirm_exchange($id)
    {
        $student = $this->authUser();

        $exchange = Exchange::findOrFail($id);

        $rejectedExchanges = Exchange::all()->where('receiver_id', $student->id)->where('id', '!=', $id);

        if ($student->id === $exchange->receiver_id && $student->exchange_id === 0) {
            $exchange->confirmed = 1;

            if ($exchange->save()) {
                $student->exchange_id = $exchange->id;

                if ($student->save()) {
                    foreach ($rejectedExchanges as $rejectedExchange) {
                        $rejectedSender = Student::findOrFail($rejectedExchange->sender_id);
                        $rejectedSender->exchange_id = 0;
                        $rejectedSender->save();
                        $rejectedExchange->delete();
                    }

                    return response()->json(['message' => 'Du hast die Tauschanfrage wurde erfolgreich angenommen.']);
                } else {
                    return response()->json('Es gab einen Fehler beim Aktualisieren deines Accounts.', 500);
                }
            } else {
                return response()->json('Es gab einen unbekannten Fehler.', 500);
            }
        } else {
            if ($student->id != $exchange->receiver_id) {
                return response()->json('Du bist nicht der Empfänger des Tausches und kannst ihn somit nicht bestätigen.', 403);
            } else if ($student->exchange_id != 0) {
                return response()->json('Du hast bereits eine Tauschanfrage gestellt bzw. bereits getauscht.', 403);
            } else {
                return response()->json('Es gab einen unbekannten Fehler.', 500);
            }
        }
    }

    /**
     * Update the project associated to the leader of type student associated to the provided token.
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

        if (!config('diribitio.allow_student_projects')) {
            return response()->json('Du bist als Schüler nicht berechtigt ' . $indefinite_article_project_noun . ' zu aktualisieren.', 403);
        }

        if ($validator->fails()) {
            return response()->json('Die mitgesendeten Daten der Anfrage sind ungültig.', 406);
        }

        $student = $this->authUser();

        if ($student->role === 2 && $student->project_id != 0 && $student->leaded_project()->exists()) {
            $project = Project::findOrFail($student->project_id);

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
                if ($student->leaded_project()->save($project)) {
                    return response()->json(['message' => $definite_article_project_noun . ' wurde erfolgreich aktualisiert.'], 200);
                } else {
                    return response()->json('Es gab einen unbekannten Fehler.', 500);
                }
            } catch (\Illuminate\Database\QueryException $e) {
                return response()->json('Scheinbar ist der neue Titel bereits vergeben.', 500);
            }
        } else {
            if ($student->project_id == 0) {
                return response()->json('Es wurde ' . $no_project_noun . ' zum aktualisieren angegeben.', 400);
            } else if ($student->role != 2 || !$student->leaded_project()->exists()) {
                return response()->json('Du bist nicht der Leiter ' . $genitive_project_noun . '.', 403);
            } else {
                return response()->json('Es gab einen unbekannten Fehler.', 500);
            }
        }
    }

    /**
     * Update the project associated to the leader of type student associated to the provided token outside the normal time frame with admin authorization.
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

        if (!config('diribitio.allow_student_projects')) {
            return response()->json('Du bist als Schüler nicht berechtigt ' . $indefinite_article_project_noun . ' zu aktualisieren.', 403);
        }

        if ($validator->fails()) {
            return response()->json('Die mitgesendeten Daten der Anfrage sind ungültig.', 406);
        }

        $leader = $this->authUser();

        if ($leader->project_id != 0 && $leader->leaded_project()->exists()) {
            $project = Project::findOrFail($leader->project_id);

            if ($project->editable != 1) {
                return response()->json($definite_article_project_noun . ' kann zurzeit nur mit der Erlaubnis eines Admins bearbeitet werden.', 403);
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
                    return response()->json(['message' => $definite_article_project_noun . ' wurde erfolgreich aktualisiert.'], 200);
                } else {
                    return response()->json('Es gab einen unbekannten Fehler.', 500);
                }
            } catch (\Illuminate\Database\QueryException $e) {
                return response()->json('Scheinbar ist der neue Titel bereits vergeben.', 500);
            }
        } else {
            return response()->json('Sie leiten noch ' . $no_project_noun . '.', 403);
        }
    }

    /**
     * Destroy the specific exchange associated to the student associated to the provided token and if needed update the other student associated to that exchange.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy_exchange()
    {
        $student = $this->authUser();

        if ($student->exchange_id != 0 && $student->exchange) {
            $exchange = $student->exchange;

            if ($exchange->delete()) {
                $student->exchange_id = 0;

                if ($student->save()) {
                    if ($exchange->confirmed === 1) {
                        if ($student->id === $exchange->sender_id) {
                            $second_student = $exchange->receiver;
                        } else if ($student->id === $exchange->receiver_id) {
                            $second_student = $exchange->sender;
                        } else {
                            return response()->json('Es gab einen Fehler beim Aktualisieren des Accounts deines Tauschpartners.', 500);
                        }

                        $second_student->exchange_id = 0;

                        if ($second_student->save()) {
                            return response()->json(['message' => 'Die Tauschanfrage wurde erfolgreich gelöscht.'], 200);
                        } else {
                            return response()->json('Es gab einen Fehler beim Aktualisieren des Accounts deines Tauschpartners.', 500);
                        }
                    } else {
                        return new ExchangeResource($exchange);
                    }
                } else {
                    return response()->json('Es gab einen Fehler beim Aktualisieren deines Accounts.', 500);
                }
            } else {
                return response()->json('Es gab einen unbekannten Fehler.', 500);
            }
        } else {
            return response()->json('Du kannst noch keine Tauschanfrage löschen, da du noch keine erstellt hast.', 403);
        }
    }

    /**
     * Remove the specified message from the project associated to the leader of type student associated to the provided token.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy_leaded_project_message($id)
    {
        $student = $this->authUser();

        if (!config('diribitio.allow_student_projects')) {
            return response()->json('Du bist als Schüler nicht berechtigt eine Nachrich zu löschen.', 403);
        }

        if ($student->role === 2 && $student->project_id != 0 && $student->leaded_project()->exists()) {
            $leaded_project = $student->leaded_project;

            $message = Message::findOrFail($id);

            if ($leaded_project->messages()->where('id', $id)->delete()) {
                return;
            } else {
                return response()->json('Es gab einen unbekannten Fehler.', 500);
            }
        } else if ($student->role === 2 && $student->project_id != 0 && $student->project()->exists()) {
            $leaded_project = $student->project;

            $message = Message::findOrFail($id);

            if ($leaded_project->messages()->where('id', $id)->delete()) {
                return;
            } else {
                return response()->json('Es gab einen unbekannten Fehler.', 500);
            }
        } else {
            return response()->json('Du leitest ' . $no_project_noun . '.', 403);
        }
    }
}
