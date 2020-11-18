<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Student;
use App\Leader;
use App\Project;
use App\Http\Resources\Student as StudentResource;
use App\Http\Resources\LimitedStudent as LimitedStudentResource;
use App\Http\Resources\Leader as LeaderResource;
use App\Http\Resources\LimitedLeader as LimitedLeaderResource;
use App\Http\Resources\Project as ProjectResource;
use App\Notifications\ProjectHasNotEnoughParticipants;

class SortStudentsController extends Controller
{
    private $all_projects = array();
    private $all_students = array();
    private $deleted_projects = array();
    private $impossible_students = array();

    private function get_all_projects()
    {
        $projects = Project::get(['id', 'authorized','title', 'leader_id', 'leader_name', 'leader_type', 'min_grade', 'max_grade', 'min_participants', 'max_participants']);

        $projects->each(function ($project) {
            $project->leader_assistants = [];

            $project->assistant_student_leaders->each(function ($assistant_student_leader) use ($project) {
                if ($assistant_student_leader->id != $project->leader_id) {
                    $project->leader_assistants = array_merge($project->leader_assistants, [$assistant_student_leader->id]);
                }
            });

            unset($project->assistant_student_leaders);

            $project->participants = [];
        });

        return $projects;
    }

    private function get_all_students()
    {
        $students = Student::get(['id', 'role', 'first_name', 'last_name', 'grade', 'letter', 'first_wish', 'second_wish', 'third_wish', 'first_friend', 'second_friend', 'third_friend']);

        $students->each(function ($student, $key) {
            $student->friends = [];

            if ($student->role === 1) {
                if ($student->first_friend !== 0 && $student->first_friend !== 0) {
                    $student->friends = array_merge($student->friends, [$student->first_friend]);
                }
                if ($student->second_friend !== 0 && $student->second_friend !== 0) {
                    $student->friends = array_merge($student->friends, [$student->second_friend]);
                }
                if ($student->third_friend !== 0 && $student->third_friend !== 0) {
                    $student->friends = array_merge($student->friends, [$student->third_friend]);
                }
            }

            unset($student->first_friend);
            unset($student->second_friend);
            unset($student->third_friend);
        });

        return $students;
    }

    private function check_projects() {
        #echo "Überprüft, ob alle Projekte zugelassen sind\n";
        $this->all_projects->each(function ($project, $key) {
            if ($project->authorized == 0) {
                $this->all_projects->forget($key);

                if ($project->leader_type === 'App\Student') {
                    if ($project->leader_id) {
                        $this->get_student($project->leader_id)->role = 1;
                        if ($this->get_project($this->get_student($project->leader_id)->first_wish)) {
                            $this->append_student_to_project($this->get_student($project->leader_id)->first_wish, $project->leader_id);
                        } else {
                            $this->get_student($project->leader_id)->first_wish = $this->get_student($project->leader_id)->second_wish;

                            if ($this->get_project($this->get_student($project->leader_id)->second_wish)) {
                                $this->append_student_to_project($this->get_student($project->leader_id)->second_wish, $project->leader_id);
                            } else {
                                $this->get_student($project->leader_id)->first_wish = $this->get_student($project->leader_id)->third_wish;
                                $this->get_student($project->leader_id)->second_wish = $this->get_student($project->leader_id)->third_wish;

                                if ($this->get_project($this->get_student($project->leader_id)->third_wish)) {
                                    $this->append_student_to_project($this->get_student($project->leader_id)->third_wish, $project->leader_id);
                                } else {
                                    $this->impossible_students = array_merge($this->impossible_students, [$this->get_student($project->leader_id)]);
                                    $this->all_students->forget($this->get_student_key($project->leader_id));
                                }
                            }
                        }
                    }

                    foreach ($project->leader_assistants as $leader_assistant_id) {
                        $this->get_student($leader_assistant_id)->role = 1;
                        $this->append_student_to_project($this->get_student($leader_assistant_id)->first_wish, $leader_assistant_id);
                    }
                }
            }
        });
    }

    private function check_students() {
        #echo "Überprüft, ob alle Schüler Projektwünsche haben\n";
        $this->all_students->each(function ($student, $key) {
            #echo "-> " . $student->first_name . " wird überprüft\n";
            if ($student->role == 1) {
                if ($student->first_wish == 0 || $student->second_wish == 0 || $student->third_wish == 0) {
                    #echo "-> " . $student->first_name . " hat mindestends einen üngültigen Wunsch\n";
                    $this->impossible_students = array_merge($this->impossible_students, [$student]);
                    $this->all_students->forget($key);
                    #echo "-> " . $student->first_name . " kann nich zugeordnet werden\n";
                } else if (!$this->get_project($student->first_wish) || !$this->get_project($student->second_wish) || !$this->get_project($student->third_wish)) {
                    #echo "-> " . $student->first_name . " hat mindestends einen üngültigen Wunsch\n";
                    $this->impossible_students = array_merge($this->impossible_students, [$student]);
                    $this->all_students->forget($key);
                    #echo "-> " . $student->first_name . " kann nich zugeordnet werden\n";
                }
            }
        });
    }

    private function get_project($project_id) {
        $project = $this->all_projects->filter(function($project) use ($project_id) {
            return $project->id == $project_id;
        })->first();
        return $project;
    }

    private function get_student_key($student_id) {
        $search_student = $this->all_students->filter(function($student) use ($student_id) {
            return $student->id == $student_id;
        })->first();
        $this->all_students->each(function ($student, $key) use($search_student){
            if ($student == $search_student) {
                return $key;
            }
        });
    }

    private function get_student($student_id) {
        $student = $this->all_students->filter(function($student) use ($student_id) {
            return $student->id == $student_id;
        })->first();
        return $student;
    }

    private function remove_wish($student_id, $friend_id) {
        $this->all_students->transform(function ($student, $key) use ($friend_id, $student_id) {
            if ($student->id == $student_id) {
                $friends = $student->friends;
                array_splice($friends, array_search($friend_id, $friends), 1);
                $student->friends = $friends;
            }
            return $student;
        });
    }

    private function remove_student_from_project($project_id, $student_id) {
        $this->all_projects->transform(function ($project, $key) use ($project_id, $student_id) {
            if ($project->id == $project_id) {
                $participants = $project->participants;
                array_splice($participants, array_search($student_id, $participants), 1);
                $project->participants = $participants;
            }
            return $project;
        });
    }

    private function append_student_to_project($project_id, $student_id) {
        $this->all_projects->transform(function ($project, $key) use ($project_id, $student_id) {
            if ($project->id == $project_id) {
                $project->participants = array_merge($project->participants, [$student_id]);
            }
            return $project;
        });
    }

    private function move_participants_and_leaders($project, $key) {
        #echo "Verschiebung der Schüler in andere Projekte\n";
        $this->all_projects->forget($key);

        foreach ($project->participants as $former_participant_id) {
            $former_participant = $this->get_student($former_participant_id);
            if ($former_participant->first_wish == $project->id) {
                #echo "----> " . $former_participant->first_name . " " . $former_participant->last_name . " wäre ein Teilnehmer von " . $project->id . " gewesen (1.Wunsch)\n";
                if (!$this->get_project($former_participant->second_wish)) {
                    #echo "-----> " . $former_participant->first_name . "s 2. Wunsch (" . $former_participant->second_wish. ") findet nicht statt\n";
                    if (!$this->get_project($former_participant->third_wish)) {
                        #echo "------> " . $former_participant->first_name . "s 3. Wunsch (" . $former_participant->third_wish. ") findet nicht statt\n";
                        $this->impossible_students = array_merge($this->impossible_students, [$former_participant]);
                        $this->all_students->forget($this->get_student_key($former_participant->id));
                        #echo "-------> " . $former_participant->first_name . "kann nicht zugeordnet werden\n";
                    } else {
                        $former_participant->first_wish = $former_participant->third_wish;
                        $former_participant->second_wish = $former_participant->third_wish;
                        #echo "------> " . $former_participant->first_name . "s 1. und 2. Wunsch ist jetzt " . $former_participant->second_wish . "\n";
                        $this->append_student_to_project($former_participant->first_wish, $former_participant->id);
                    }
                } else {
                    $former_participant->first_wish = $former_participant->second_wish;
                    #echo "-----> " . $former_participant->first_name . "s 1. Wunsch ist jetzt " . $former_participant->second_wish . "\n";
                    $this->append_student_to_project($former_participant->first_wish, $former_participant->id);
                }
            } elseif ($former_participant->second_wish == $project->id) {
                #echo "----> " . $former_participant->first_name . " " . $former_participant->last_name . " wäre ein Teilnehmer von " . $project->id . " gewesen (2.Wunsch)\n";
                if (!$this->get_project($former_participant->third_wish)) {
                    #echo "-----> " . $former_participant->first_name . "s 3. Wunsch (" . $former_participant->third_wish. ") findet nicht statt\n";
                    if (!$this->get_project($former_participant->first_wish)) {
                        #echo "------> " . $former_participant->first_name . "s 1. Wunsch (" . $former_participant->first_wish. ") findet nicht statt\n";
                        $this->impossible_students = array_merge($this->impossible_students, [$former_participant]);
                        $this->all_students->forget($this->get_student_key($former_participant->id));
                        #echo "-------> " . $former_participant->first_name . "kann nicht zugeordnet werden\n";
                    } else {
                        $former_participant->second_wish = $former_participant->first_wish;
                        $former_participant->third_wish = $former_participant->first_wish;
                        #echo "------> " . $former_participant->first_name . "s 2. und 3. Wunsch ist jetzt " . $former_participant->first_wish . "\n";
                        $this->append_student_to_project($former_participant->first_wish, $former_participant->id);
                    }
                } else {
                    $former_participant->second_wish = $former_participant->third_wish;
                    #echo "-----> " . $former_participant->first_name . "s 2. Wunsch ist jetzt " . $student->third_wish . "\n";
                    $this->append_student_to_project($former_participant->first_wish, $former_participant->id);
                }
            } elseif ($former_participant->third_wish == $project->id) {
                #echo "----> " . $former_participant->first_name . " " . $former_participant->last_name . " wäre ein Teilnehmer von " . $project->id . " gewesen (3.Wunsch)\n";
                if (!$this->get_project($former_participant->first_wish)) {
                    #echo "-----> " . $former_participant->first_name . "s 1. Wunsch (" . $former_participant->first_wish. ") findet nicht statt\n";
                    if (!$this->get_project($former_participant->second_wish)) {
                        #echo "------> " . $former_participant->first_name . "s 2. Wunsch (" . $former_participant->second_wish. ") findet nicht statt\n";
                        $this->impossible_students = array_merge($this->impossible_students, [$former_participant]);
                        $this->all_students->forget($this->get_student_key($former_participant->id));
                        #echo "-------> " . $former_participant->first_name . "kann nicht zugeordnet werden\n";
                    } else {
                        $former_participant->third_wish = $former_participant->second_wish;
                        $former_participant->first_wish = $former_participant->second_wish;
                        #echo "------> " . $former_participant->first_name . "s 3. und 1. Wunsch ist jetzt " . $former_participant->second_wish . "\n";
                        $this->append_student_to_project($former_participant->first_wish, $former_participant->id);
                    }
                } else {
                    $former_participant->third_wish = $former_participant->first_wish;
                    #echo "-----> " . $former_participant->first_name . "s 3. Wunsch ist jetzt " . $former_participant->first_wish . "\n";
                    $this->append_student_to_project($former_participant->first_wish, $former_participant->id);
                }
            }
        }

        if ($project->leader_type === 'App\Student') {
            if ($project->leader_id) {
                $this->get_student($project->leader_id)->role = 1;
                if ($this->get_project($this->get_student($project->leader_id)->first_wish)) {
                    $this->append_student_to_project($this->get_student($project->leader_id)->first_wish, $project->leader_id);
                } else {
                    $this->get_student($project->leader_id)->first_wish = $this->get_student($project->leader_id)->second_wish;

                    if ($this->get_project($this->get_student($project->leader_id)->second_wish)) {
                        $this->append_student_to_project($this->get_student($project->leader_id)->second_wish, $project->leader_id);
                    } else {
                        $this->get_student($project->leader_id)->first_wish = $this->get_student($project->leader_id)->third_wish;
                        $this->get_student($project->leader_id)->second_wish = $this->get_student($project->leader_id)->third_wish;

                        if ($this->get_project($this->get_student($project->leader_id)->third_wish)) {
                            $this->append_student_to_project($this->get_student($project->leader_id)->third_wish, $project->leader_id);
                        } else {
                            $this->impossible_students = array_merge($this->impossible_students, [$this->get_student($project->leader_id)]);
                            $this->all_students->forget($this->get_student_key($project->leader_id));
                        }
                    }
                }
            }

            foreach ($project->leader_assistants as $leader_assistant_id) {
                $this->get_student($leader_assistant_id)->role = 1;
                $this->append_student_to_project($this->get_student($leader_assistant_id)->first_wish, $leader_assistant_id);
            }
        }
    }

    private function check_wishes($student_id) {
        if (!$student_id) {
            return;
        }
        $student = $this->get_student($student_id);
        if (!$this->get_project($student->first_wish)) {
            #echo "--> " . $student->first_name . "s 1. Wunsch (" . $student->first_wish. ") findet nicht statt\n";
            if (!$this->get_project($student->second_wish)) {
                #echo "---> " . $student->first_name . "s 2. Wunsch (" . $student->second_wish. ") findet nicht statt\n";
                if (!$this->get_project($student->third_wish)) {
                    #echo "----> " . $student->first_name . "s 3. Wunsch (" . $student->third_wish. ") findet nicht statt\n";
                    $this->impossible_students = array_merge($this->impossible_students, [$student]);
                    $this->all_students->forget($this->get_student_key($student_id));
                    #echo "-----> " . $student->first_name . " kann nicht zugeordnet werden\n";
                } else {
                    $student->first_wish = $student->third_wish;
                    $student->second_wish = $student->third_wish;
                    #echo "----> " . $student->first_name . "s 1. und 2. Wunsch ist jetzt " . $student->second_wish . "\n";
                }
            } else {
                $student->first_wish = $student->second_wish;
                #echo "---> " . $student->first_name . "s 1. Wunsch ist jetzt " . $student->second_wish . "\n";
            }
        } elseif (!$this->get_project($student->second_wish)) {
            #echo "--> " . $student->first_name . "s 2. Wunsch (" . $student->second_wish. ") findet nicht statt\n";
            if (!$this->get_project($student->third_wish)) {
                #echo "---> " . $student->first_name . "s 3. Wunsch (" . $student->third_wish. ") findet nicht statt\n";
                if (!$this->get_project($student->first_wish)) {
                    #echo "----> " . $student->first_name . "s 1. Wunsch (" . $student->first_wish. ") findet nicht statt\n";
                    $this->impossible_students = array_merge($this->impossible_students, [$student]);
                    $this->all_students->forget($key);
                    #echo "-----> " . $student->first_name . " kann nicht zugeordnet werden\n";
                } else {
                    $student->second_wish = $student->first_wish;
                    $student->third_wish = $student->first_wish;
                    #echo "----> " . $student->first_name . "s 2. und 3. Wunsch ist jetzt " . $student->first_wish . "\n";
                }
            } else {
                $student->second_wish = $student->third_wish;
                #echo "---> " . $student->first_name . "s 2. Wunsch ist jetzt " . $student->third_wish . "\n";
            }
        } elseif (!$this->get_project($student->third_wish)) {
            #echo "--> " . $student->first_name . "s 3. Wunsch (" . $student->third_wish. ") findet nicht statt\n";
            if (!$this->get_project($student->first_wish)) {
                #echo "---> " . $student->first_name . "s 1. Wunsch (" . $student->first_wish. ") findet nicht statt\n";
                if (!$this->get_project($student->second_wish)) {
                    #echo "----> " . $student->first_name . "s 2. Wunsch (" . $student->second_wish. ") findet nicht statt\n";
                    $this->impossible_students = array_merge($this->impossible_students, [$student]);
                    $this->all_students->forget($key);
                    #echo "-----> " . $student->first_name . " kann nicht zugeordnet werden\n";
                } else {
                    $student->third_wish = $student->second_wish;
                    $student->first_wish = $student->second_wish;
                    #echo "----> " . $student->first_name . "s 3. und 1. Wunsch ist jetzt " . $student->second_wish . "\n";
                }
            } else {
                $student->third_wish = $student->first_wish;
                #echo "---> " . $student->first_name . "s 3. Wunsch ist jetzt " . $student->first_wish . "\n";
            }
        }
    }

    private function delete_project($project, $key) {
        #echo $project->title . " (" . $project->id . ") wurde gelöscht\n";
        $project->participants = [];
        $project->leader = null;
        $project->assistant_student_leaders = [];
        $this->all_projects->forget($key);

        $this->all_students->each(function ($student, $key) {
            $this->check_wishes($student->id);
        });

        if ($project->leader_type != "App\Student") {
            $this->deleted_projects = $this->deleted_projects->push($project);
        }
    }

    private function check_solved() {
        #echo "\nÜberprüfung ob der Vorschlag ausreichend ist\n";
        $solved = true;
        $this->all_projects->each(function ($project, $key) use(&$solved){
            if (count($project->participants) > $project->max_participants) {           // Hat das Projekt zu viele Teilnehmer?
                $solved = false;
                #echo "-> Es gibt noch Projekte mit zu vielen Teilnehmern\n";
                return false;
            } else if (count($project->participants) < $project->min_participants && count($project->participants) > 0) {       // Hat das Projekt zu wenig Teilnehmer? (mehr als 0)
                $solved = false;
                #echo "-> Es gibt noch Projekte mit zu wenigen Teilnehmern\n";
                return false;
            }
        });

        if ($solved) {
        #echo "-> Der Sortiervorschlag ist ausreichend\n";
        }

        return $solved;
    }

    private function format_data($all_projects, $deleted_projects) {
        #echo "Formatierung des Sortiervorschlags\n";

        $this->all_projects->each(function ($project, $key) {
            if ($project->leader_type === 'App\Student') {
                $project->leader = $this->get_student($project->leader_id);

                $new_leader_assistants = [];

                foreach ($project->leader_assistants as $leader_assistant_id) {
                    array_push($new_leader_assistants, $this->get_student($leader_assistant_id));
                }

                $project->assistant_student_leaders = $new_leader_assistants;
            } else {
                $project->assistant_student_leaders = [];
            }

            unset($project->leader_id);
            unset($project->leader_assistants);

            $participant_objects = [];

            foreach ($project->participants as $participant_id) {
                array_push($participant_objects, $this->get_student($participant_id));
            }

            unset($project->participants);

            $project->participants = $participant_objects;
        });

        $this->deleted_projects->each(function ($project, $key) {
            $project->assistant_student_leaders = [];

            unset($project->leader_id);
            unset($project->leader_assistants);

            $participant_objects = [];

            unset($project->participants);

            $project->participants = $participant_objects;
        });

        $return_data = $all_projects->merge($deleted_projects);

        $impossible_students_project = array(
            "id" => $return_data->max('id')+1,
            "title" => "Nicht zuordnbare Schüler",
            "leader_id" => 0,
            "leader_name" => "Bitte alle Schüler entfernen",
            "leader_type" => "App\\Leader",
            "min_grade" => 0,
            "max_grade" => 13,
            "min_participants" => 0,
            "max_participants" => 0,
            "leader" => null,
            "assistant_student_leaders" => [],
            "participants" => $this->impossible_students
        );

        $return_data->push($impossible_students_project);

        return $return_data;
    }

    /**
     * Create a sorting proposal
     *
     * @return \Illuminate\Http\Response
     */
    public function create_sorting_proposal()
    {
        #echo "Alle Schüler und Projekte werden aus der Datenbank geladen\n";

        $this->all_projects = $this->get_all_projects();
        $this->all_students = $this->get_all_students();
        $this->impossible_students = [];
        $this->deleted_projects = collect();

        $sort = true;
        $solved = false;

        $max_tries = 10;
        $tries = 10;

        // Gibt alle Schüler und Projekte aus (Es wird nicht sortiert!)

        if ($sort === false) {
            return response()->json(['students' => $this->all_students, 'projects' => $this->all_projects]);
        }

        // Algorythmus zum Einsortieren der Schüler in die Projekte

        #echo "Freundschaften werden überprüft und Wünsche gleich gesetzt\n";

        if ($sort === true) {
            $this->check_projects();
            $this->check_students();

            $this->all_students->each(function ($student, $key) {
                foreach ($student->friends as $friend_id) {
                    $friend = $this->get_student($friend_id);

                    if (in_array($student->id, $friend->friends)) {         // Freundschaften werden auf gegenseitigkeit geprüft => Wünsche werden gleich gesetzt
                        $friend->first_wish = $student->first_wish;
                        $friend->second_wish = $student->second_wish;
                        $friend->third_wish = $student->third_wish;
                    } else {
                        $this->remove_wish($student->id, $friend->id);
                    }
                }
            });

            #echo "Projekte die nicht stattfinden können werden gelöscht\n";

            $this->all_projects->each(function ($project, $key) {           // Projekte die nicht genügend Teilnehmer haben KÖNNEN werden gelöscht
                $max_possible_participants = 0;
                $this->all_students->each(function ($student, $key) use(&$max_possible_participants, $project) {
                    if ($student->first_wish == $project->id || $student->second_wish == $project->id || $student->third_wish == $project->id) {
                        $max_possible_participants += 1;
                    }
                });

                if ($project->min_participants > $max_possible_participants || $max_possible_participants == 0) {
                    #echo "\n" . $project->title . " (" . $project->id . ") kann nicht sattfinden (" . $max_possible_participants . " von " . $project->min_participants . " Teilnehmern)\n";

                    $this->delete_project($project, $key);

                    if ($max_possible_participants > 0) {
                        $this->all_students->each(function ($student, $key) use($project) {
                            if ($student->first_wish == $project->id) {
                                #echo "-> " . $student->first_name . " " . $student->last_name . " wäre ein Teilnehmer von " . $project->id . " gewesen (1.Wunsch)\n";
                                if (!$this->get_project($student->second_wish)) {
                                    #echo "--> " . $student->first_name . "s 2. Wunsch (" . $student->second_wish. ") findet nicht statt\n";
                                    if (!$this->get_project($student->third_wish)) {
                                        #echo "---> " . $student->first_name . "s 3. Wunsch (" . $student->third_wish. ") findet nicht statt\n";
                                        $this->impossible_students = array_merge($this->impossible_students, [$student]);
                                        $this->all_students->forget($key);
                                        #echo "----> " . $student->first_name . " kann nicht zugeordnet werden\n";
                                    } else {
                                        $student->first_wish = $student->third_wish;
                                        $student->second_wish = $student->third_wish;
                                        #echo "---> " . $student->first_name . "s 1. und 2. Wunsch ist jetzt " . $student->second_wish . "\n";
                                    }
                                } else {
                                    $student->first_wish = $student->second_wish;
                                    #echo "--> " . $student->first_name . "s 1. Wunsch ist jetzt " . $student->second_wish . "\n";
                                }
                            } elseif ($student->second_wish == $project->id) {
                                #echo "-> " . $student->first_name . " " . $student->last_name . " wäre ein Teilnehmer von " . $project->id . " gewesen (2.Wunsch)\n";
                                if (!$this->get_project($student->third_wish)) {
                                    #echo "--> " . $student->first_name . "s 3. Wunsch (" . $student->third_wish. ") findet nicht statt\n";
                                    if (!$this->get_project($student->first_wish)) {
                                        #echo "---> " . $student->first_name . "s 1. Wunsch (" . $student->first_wish. ") findet nicht statt\n";
                                        $this->impossible_students = array_merge($this->impossible_students, [$student]);
                                        $this->all_students->forget($this->get_student_key($student->id));
                                        #echo "----> " . $student->first_name . " kann nicht zugeordnet werden\n";
                                    } else {
                                        $student->second_wish = $student->first_wish;
                                        $student->third_wish = $student->first_wish;
                                        #echo "---> " . $student->first_name . "s 2. und 3. Wunsch ist jetzt " . $student->first_wish . "\n";
                                    }
                                } else {
                                    $student->second_wish = $student->third_wish;
                                    #echo "--> " . $student->first_name . "s 2. Wunsch ist jetzt " . $student->third_wish . "\n";
                                }
                            } elseif ($student->third_wish == $project->id) {
                                #echo "-> " . $student->first_name . " " . $student->last_name . " wäre ein Teilnehmer von " . $project->id . " gewesen (3.Wunsch)\n";
                                if (!$this->get_project($student->first_wish)) {
                                    #echo "--> " . $student->first_name . "s 1. Wunsch (" . $student->first_wish. ") findet nicht statt\n";
                                    if (!$this->get_project($student->second_wish)) {
                                        #echo "---> " . $student->first_name . "s 2. Wunsch (" . $student->second_wish. ") findet nicht statt\n";
                                        $this->impossible_students = array_merge($this->impossible_students, [$student]);
                                        $this->all_students->forget($this->get_student_key($student->id));
                                        #echo "----> " . $student->first_name . " kann nicht zugeordnet werden\n";
                                    } else {
                                        $student->third_wish = $student->second_wish;
                                        $student->first_wish = $student->second_wish;
                                        #echo "---> " . $student->first_name . "s 3. und 1. Wunsch ist jetzt " . $student->second_wish . "\n";
                                    }
                                } else {
                                    $student->third_wish = $student->first_wish;
                                    #echo "--> " . $student->first_name . "s 3. Wunsch ist jetzt " . $student->first_wish . "\n";
                                }
                            }
                        });
                    }

                    if ($project->leader_type === 'App\Student') {
                        if ($project->leader_id != 0) {
                            $leader = $this->get_student($project->leader_id);
                            #echo "-> " . $leader->first_name . " " . $leader->last_name . " wäre der Leiter von " . $project->id . " gewesen\n";
                            $leader->role = 1;
                            #echo "--> " . $leader->first_name . " " . $leader->last_name . " ist jetzt ein normaler Teilnehmer\n";

                            $this->check_wishes($project->leader_id);
                        }

                        foreach ($project->leader_assistants as $leader_assistant_id) {
                            $assistant = get_student($leader_assistant_id);
                            #echo "-> " . $assistant->first_name . " " . $assistant->last_name . " wäre ein Assistent von " . $project->id . " gewesen\n";
                            $this->$assistant->role = 1;
                            #echo "--> " . $assistant->first_name . " " . $assistant->last_name . "ist jetzt ei normaler Teilnehmer\n";

                            $this->check_wishes($leader_assistant_id);
                        }
                    }
                }
            });

            #echo "\n Die Schüler werden ihren 1.Wünschen zugeordnet\n";

            $this->all_students->each(function ($student, $key) {
                if ($student->role === 1) {
                    $this->append_student_to_project($student->first_wish, $student->id);
                    #echo "-> " . $student->first_name . " " . $student->last_name . " wird seinem 1.Wunsch (" . $student->first_wish . ") zugeordnet\n";
                }
            });

            while ($solved == false) {

                $tries -= 1;

                if ($tries <= 0) {
                    break;
                }

                #echo "\n----------" . ($max_tries - $tries) . " Durchlauf----------\n";

                $this->all_projects->each(function ($project, $key) {
                    if (count($project->participants) < $project->min_participants) {       // Hat das Projekt zu wenig Teilnehmer?
                        #echo "\n" . $project->title . " (" . $project->id . ") nur " . count($project->participants) . " von " . $project->min_participants . " Teilnehmern\n";
                        $donor_participants = [];
                        $donor_projects = [];
                        #echo "-> Check ob Projekt aufgefüllt werden kann\n";
                        $this->all_projects->each(function ($donor_project, $key) use(&$donor_participants, &$donor_projects, $project) {
                            if ($donor_project != $project) {
                                foreach ($donor_project->participants as $donor_participant_id) {
                                    if ($this->get_student($donor_participant_id)->first_wish == $project->id && $donor_project->min_participants <= count($donor_project->participants) - (1 + count($this->get_student($donor_participant_id)->friends))) {
                                        array_push($donor_participants, $donor_participant_id);
                                        array_push($donor_projects, $donor_project);
                                    } elseif ($this->get_student($donor_participant_id)->second_wish == $project->id && $donor_project->min_participants <= count($donor_project->participants) - (1 + count($this->get_student($donor_participant_id)->friends))) {
                                        array_push($donor_participants, $donor_participant_id);
                                        array_push($donor_projects, $donor_project);
                                    } elseif ($this->get_student($donor_participant_id)->third_wish == $project->id && $donor_project->min_participants <= count($donor_project->participants) - (1 + count($this->get_student($donor_participant_id)->friends))) {
                                        array_push($donor_participants, $donor_participant_id);
                                        array_push($donor_projects, $donor_project);
                                    }
                                }
                            }
                        });

                        if ((count($donor_participants) + count($project->participants)) >= $project->min_participants) {                           // Projekt wird aufgefüllt
                            #echo "--> Das Projekt kann aufgefüllt werden\n";
                            foreach ($donor_participants as $donor_participant_id) {
                                if (count($project->participants) < $project->min_participants) {
                                    #echo "---> " . $this->get_student($donor_participant_id)->first_name . " " . $this->get_student($donor_participant_id)->last_name . " wird nach " . $project->id . " verschoben\n";
                                    $this->remove_student_from_project($donor_projects[array_search($donor_participant_id, $donor_participants)]->id, $donor_participant_id);
                                    $this->append_student_to_project($project->id, $donor_participant_id);
                                    foreach ($this->get_student($donor_participant_id)->friends as $friend_id) {
                                        #echo "----> " . $this->get_student($donor_participant_id)->first_name . " " . $this->get_student($donor_participant_id)->last_name . " wird als Freund nach " . $project->id . " verschoben\n";
                                        $this->remove_student_from_project($donor_projects[array_search($donor_participant_id, $donor_participants)]->id, $friend_id);
                                        $this->append_student_to_project($project->id, $friend_id);
                                        array_splice($donor_project, array_search($friend_id, $donor_participants), 1);
                                        array_splice($donor_participants, array_search($friend_id, $donor_participants), 1);
                                    }
                                }
                            }
                        } else {
                            #echo "--> Das Projekt kann nicht aufgefüllt werden\n";
                            #echo "---> ";
                            $this->move_participants_and_leaders($project, $key);                                                                                       // Projekt wird aufgelöst
                            #echo "---> ";
                            $this->delete_project($project, $key);
                        }
                    } else if (count($project->participants) > $project->max_participants) {           // Hat das Projekt zu viele Teilnehmer?
                        #echo "\n" . $project->title . " (" . $project->id . ") hat " . count($project->participants) . " von " . $project->max_participants . " Teilnehmern\n";
                        foreach ($project->participants as $participant_id) {
                            # #echo $this->get_student($participant_id) . "\n";
                            if (count($project->participants) > $project->max_participants) {
                                $participant = $this->get_student($participant_id);
                                if (count($this->get_project($participant->first_wish)->participants) - count($participant->friends) < $this->get_project($participant->first_wish)->max_participants) {
                                    #echo "--> " . $participant->first_name . " " . $participant->last_name . " wird nach " . $participant->first_wish . " (1.Wunsch) verschoben\n";
                                    $this->remove_student_from_project($project->id, $participant_id);
                                    $this->append_student_to_project($$participant->first_wish, $participant_id);
                                    foreach ($participant->friends as $friend) {
                                        #echo "---> " . $this->get_student($friend)->first_name . " " . $this->get_student($friend)->last_name . " wird als Freund nach " . $this->get_student($friend)->first_wish . " (1.Wunsch) verschoben\n";
                                        $this->remove_student_from_project($project->id, $friend);
                                        $this->append_student_to_project($participant->first_wish, $friend);
                                    }
                                } elseif (count($this->get_project($participant->second_wish)->participants) - count($participant->friends) < $this->get_project($participant->second_wish)->max_participants) {
                                    #echo "--> " . $participant->first_name . " " . $participant->last_name . " wird nach " . $participant->second_wish . " (2.Wunsch) verschoben\n";
                                    $this->remove_student_from_project($project->id, $participant_id);
                                    $this->append_student_to_project($participant->second_wish, $participant_id);
                                    foreach ($participant->friends as $friend) {
                                        #echo "---> " . $this->get_student($friend)->first_name . " " . $this->get_student($friend)->last_name . " wird als Freund nach " . $this->get_student($friend)->second_wish . " (1.Wunsch) verschoben\n";
                                        $this->remove_student_from_project($project->id, $friend);
                                        $this->append_student_to_project($participant->second_wish, $friend);
                                    }
                                } elseif (count($this->get_project($participant->third_wish)->participants) - count($participant->friends) < $this->get_project($participant->third_wish)->max_participants) {
                                    #echo "--> " . $participant->first_name . " " . $participant->last_name . " wird nach " . $participant->second_wish . " (3.Wunsch) verschoben\n";
                                    $this->remove_student_from_project($project->id, $participant_id);
                                    $this->append_student_to_project($participant->third_wish, $participant_id);
                                    foreach ($participant->friends as $friend) {
                                        #echo "---> " . $this->get_student($friend)->first_name . " " . $this->get_student($friend)->last_name . " wird als Freund nach " . $this->get_student($friend)->third_wish . " (1.Wunsch) verschoben\n";
                                        $this->remove_student_from_project($project->id, $friend);
                                        $this->append_student_to_project($participant->third_wish, $friend);
                                    }
                                }
                            }
                        }
                    } /* else if (count($project->participants) <= 0) {            // Hat das Projekt überhaupt Teilnehmer? (hindert optimale Lösung)
                        #echo "\n" . $project->title . " hat keine Teilnehmer\n";
                        #echo "-> ";
                        $this->move_participants_and_leaders($project, $key);
                        #echo "-> ";
                        $this->delete_project($project, $key);
                    } */
                });

                $solved = $this->check_solved();
            }
        }

        #echo "\n----------Ende der Sortierung----------\n\n";

        $this->all_projects->each(function ($project, $key) {         // Nicht benutze Projekte löschen
            if (count($project->participants) == 0) {
                #echo "\n" . $project->title . " hat keine Teilnehmer\n";
                #echo "-> ";
                $this->move_participants_and_leaders($project, $key);
                #echo "-> ";
                $this->delete_project($project, $key);
            }
        });

        $data = ProjectResource::collection($this->format_data($this->all_projects, $this->deleted_projects));

        Storage::disk('local')->put('private/temp-data-storage/data.json', json_encode(['data' => $data]));

        return response()->json(['data' => $data, 'message' => 'Die Schüler konnten erfolgreich sortiert werden.'], 200);
    }

    /**
     * Get the current sorting proposal
     *
     * @return \Illuminate\Http\Response
     */
    public function request_sorting_proposal()
    {
        if (Storage::disk('local')->exists('private/temp-data-storage/data.json')) {
            return response()->json(json_decode(Storage::disk('local')->get('private/temp-data-storage/data.json')), 200);
        } else {
            return response()->json('Es gibt noch keinen Sortierungsvorschlag.', 404);
        }
    }

    /**
     * Edit the current sorting proposal
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function edit_sorting_proposal(Request $request)
    {
        if (Storage::disk('local')->exists('private/temp-data-storage/data.json')) {
            Storage::disk('local')->put('private/temp-data-storage/data.json', json_encode(['data' => $request->input('data')]));
            return response()->json(['message' => 'Der Sortierungsvorschlag wurde erfolgreich aktualisiert.'], 200);
        } else {
            return response()->json('Es gibt noch keinen Sortierungsvorschlag.', 404);
        }
    }

    /**
     * Apply the sorting proposal on the actual application data
     *
     * @return \Illuminate\Http\Response
     */
    public function apply_sorting_proposal()
    {
        if (Storage::disk('local')->exists('private/temp-data-storage/data.json')) {
            $projects = collect(json_decode(Storage::disk('local')->get('private/temp-data-storage/data.json'))->data);
        } else {
            return response()->json('Es konnte kein Sortierungsvorschlag gefunden werden.', 404);
        }

        $projects->each(function ($item) {
            $project = (object) $item;
            $participants = collect($project->participants);

            if ($participants->count() > 0) {
                $participants->each(function ($item) use ($project) {
                    $participant = (object) $item;
                    $student = Student::findOrFail($participant->id);

                    $student->role = 1;
                    $student->project_id = $project->id;

                    $student->save();
                });
            } else if (Project::find($project->id)) {
                $project_object = Project::findOrFail($project->id);
                $leader = $project_object->leader;

                if ($leader) {
                    $leader->notify(new ProjectHasNotEnoughParticipants());
                    if ($project_object->leader_type === 'App\Leader') {
                        $leader->project_id = 0;
                        $leader->save();
                    }
                }

                $messages = $project_object->messages();

                if ($messages->exists()) {
                    if ($messages->delete()) {
                        if ($project_object->delete()) {
                            if ($project_object->image != null && $project_object->image != '') {
                                Storage::delete('public/images/'. $project_object->image);
                            }
                        }
                    }
                } else {
                    if ($project_object->delete()) {
                        if ($project_object->image != null && $project_object->image != '') {
                            Storage::delete('public/images/'. $project_object->image);
                        }
                    }
                }
            }
        });

        Storage::disk('local')->delete('private/temp-data-storage/data.json');
        return response()->json(['message' => 'Die Sortierung wurde erfolgreich angewandt.'], 200);
    }
}

