<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\User;
use Cache;
use Illuminate\Support\Facades\Redis;

class ProcessMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user_id = null;
    public $message = null;

    private $keywords = [
        "*schedule*" => "schedule_meeting",
        "details *" => "details_of_person",
        "*free*" => "who_is_free",
        "*available*" => "who_is_free",
        "members of team *" => "list_team",
        "in team *" => "list_team",
        "*in my team*" => "list_team",
        "who is *" => "details_of_person",
        "poke *" => "poke",
        "h*llo" => "greeting",
        "hi" => "greeting",
        "hey" => "greeting",
        "thanks" => "thanks",
        "*thanks" => "thanks",
        "thanks*" => "thanks",
        "*thanks*" => "thanks",
    ];

    private $responses = [
        "greeting" => [
            "Hello",
            "Hi",
            "Hey",
            "Heyy!",
            "Nice to see you back"
        ],
        "thanks" => [
            "You are welcome!",
            "It was my pleasure",
            "No problem",
            "No problem, let me know if you need anything else"
        ],
        "other" => [
            "Sorry I didn't understood what you mean",
            "I'm afraid I don't know how to do that yet, can you try something else?",
            "Sorry, I'm not functional enough for that",
            "That sounds great, but I'm unable to perform such operations yet",
            "Sorry, I don't know how to do that yet, maybe in the next version :)"
        ]
    ];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user_id, $message)
    {
        $this->user_id = $user_id;
        $this->message = $message;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle() {
        try {

            $messageType = $this->parseMessage();
            switch ($messageType) {
                case 'poke':
                    $response = $this->processPoke();
                    break;
                case 'list_team':
                    $response = $this->processListTeamQuery();
                    break;
                case 'who_is_free':
                    $response = $this->processWhoIsFree();
                    break;
                case 'details_of_person':
                    $response = $this->processDetailsOfPerson();
                    break;
                case 'schedule_meeting':
                    $response = $this->processScheduleMeeting();
                    break;
                default:
                    $response = $this->buildResponse($messageType);
                    break;
            }

            if($response == "") {
                $response = $this->buildResponse("other");
            }

            Redis::lpush('new:message:'.$this->user_id, $response);
        } catch (\Exception $e) {
            dd($e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

    public function parseMessage() {
        foreach ($this->keywords as $keyword => $type) {
            $a = strtolower($keyword);
            $b = strtolower($this->message);

            if(fnmatch("*".$a."*", $b)){
                return $type;
            }
        }

        return "other";
    }

    public function processScheduleMeeting() {
        $id = $this->parsePerson();

        if($id) {
            $person = User::find($id);
            return "Meeting invitation sent to {$person->name}";
        }

        return "I couldn't find the person, sorry";
    }

    public function processDetailsOfPerson() {
        $id = $this->parsePerson();

        if($id) {
            $person = User::find($id);
            return "{$person->name} - {$person->role} - ".($person->available ? "Available" : "Busy")." at the moment";
        }

        return "I can't find this person, sorry";
    }

    public function parsePerson() {

        $users = Cache::remember('users', 1440, function() {
            return User::all();
        });

        foreach ($users as $user) {
            if (strpos(strtolower($this->message), strtolower($user->name)) !== FALSE) {
                return $user->id;
            }            
        }

        return false;
    }

    public function parseRole() {
        $roles = Cache::remember('roles', 1440, function() {
            $users = User::all();
            $roles = [];

            foreach($users as $user) {
                if(!in_array($user->role, $roles)) {
                    $roles[] = $user->role;
                }
            }

            return $roles;
        });

        foreach($roles as $role) {
            if(strpos(strtolower($this->message), strtolower($role)) !== FALSE) {
                return $role;
            }
        }

        return false;
    }

    public function parseTeam() {
        preg_match('/team ([0-9a-bA-B]+)/i', $this->message, $match);
        
        if(isset($match[1])) {
            return $match[1];
        }

        if(strpos($this->message, "my") !== FALSE) {
            return User::find($this->user_id)->team;
        }

        return false;
    }

    public function processPoke() {
        $id = $this->parsePerson();

        if(!$id) {
            return "I can't find that person :(";
        }

        $user = User::find($id);

        return "Poking: {$user->name}";
    }

    public function processWhoIsFree() {
        $team = $this->parseTeam();
        $id = $this->parsePerson();
        $role = $this->parseRole();

        $response = "";

        if($id) {
            $user = User::find($id);
            if($user->available) {
                $response = "Good news, {$user->name} is available at the moment.";
            } else {
                $response = "I'm afraid {$user->name} is not available at the moment.";
            }
        }

        if($team) {
            $response = "Sorry, there are no free members at the moment";
            if($role) {
                $users = User::where('team', $team)->where('available', true)->where('role', $role)->get();

                if($users->count() > 0) {
                    $response = "List of available ".$role."(s) of team <b>".$team."</b>:<br/>";

                    foreach($users as $key => $user) {
                        $response .= ($key+1).") {$user->name} - {$user->role}<br/>"; 
                    }
                }
            } else {
                $users = User::where('team', $team)->where('available', true)->get();

                if($users->count() > 0) {
                    $response = "List of available members of team <b>".$team."</b>:<br/>";

                    foreach($users as $key => $user) {
                        $response .= ($key+1).") {$user->name} - {$user->role}<br/>"; 
                    }
                }

            }
            
        }

        return $response;
    }

    public function processListTeamQuery() {
        $team = $this->parseTeam();
        $users = User::where('team', $team)->get();
        
        $response = "The members of team <b>$team</b> are:<br/>";
        
        foreach ($users as $key => $user) {
            $response .= ($key+1).") {$user->name} - {$user->role}<br/>";
        }

        return $response;
    }

    public function buildResponse($type) {
        return $this->responses[$type][array_rand($this->responses[$type])];
    }
}
