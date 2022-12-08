<?php
namespace App\Facades;

use App\Enums\Messages;
use App\Enums\Roles;
use App\Models\Competition;
use App\Models\CompetitionRound;
use App\Models\CompetitionRoundBracket;
use App\Models\CompetitionTeam;
use App\Models\CompetitionUser;
use App\Models\Team;
use App\Models\User;
use Google\Service\AndroidEnterprise\Resource\Permissions;

class CompetitionFacade {


    //Make a number of rounds based on the number of competitors and 
    public static function makeRounds(Competition $competition, int $number_of_competitors, $competitors_per_backet = 2) {

        $rounds = 0;


        for($i = $number_of_competitors; $i>=0; $i++) {

            $rounds++;
            $remainder = $i%$competitors_per_backet;
            $i = round(($i /$competitors_per_backet), 0, PHP_ROUND_HALF_DOWN);
            $i += $remainder;

            if($i <= 2){
                $rounds++;
                break;
            }

        }

        for($i=0; $i< $rounds; $i++) {

            $round = CompetitionRound::where('competition_id', $competition->id)
            ->where('round', ($i+1))
            ->first();

            if(!$round){
                CompetitionRound::create(['competition_id' => $competition->id, 'round' => ($i+1)]);
            }
        }


        return $rounds;

    }

    public static function autoAssignCompetitorsUsers(Competition $competition, int $round, int $users_per_bracket) {

        // Try to get previous round
        $current_round = CompetitionRound::where('competition_id', $competition->id)
        ->where('round', $round -1)
        ->first();

        $competitors = null;

        if(!$current_round) {
            //If no previous round, get current round
            $current_round = CompetitionRound::where('competition_id', $competition->id)
            ->where('round', $round)
            ->first();

            if(!$current_round) {
                $current_round = CompetitionRound::create(['competition_id' => $competition->id, 'round' => $round]);
            }

            $competitors = User::where('competition_id', $competition->id)
            ->where('user_role', Roles::Participant)
            ->join('competition_users', 'users.id', '=', 'competition_users.user_id')
            ->get();


        } else {

            $competitors = User::where('competition_id', $competition->id)
            ->where('round', $round -1)
            ->where('is_winner', true)
            ->join('competition_round_brackets', 'users.id', '=', 'competition_round_brackets.user_id')
            ->get();

        }


        foreach($competitors as $competitor) {

            $bracket = CompetitionRoundBracket::where('competition_id', $competition->id)
            ->where('user_id', $competitor->id)
            ->where('round', $round)
            ->first();

            if(!$bracket) {

                $bracket_placement = self::getOpenUserBracket($competition, $round, $users_per_bracket);

                CompetitionRoundBracket::create([
                    'competition_id' => $competition->id,
                    'user_id' => $competitor->user_id,
                    'round' => $round,
                    'bracket' => $bracket_placement
                ]);

            }

        }

        return CompetitionRoundBracket::where('competition_id', $competition->id)
        ->where('round', $round)
        ->get();
    }

    public static function getOpenUserBracket(Competition $competition, int $round, int $users_per_bracket) {

        $brackets =  CompetitionRoundBracket::where('competition_id', $competition->id)
        ->where('round', $round)
        ->get();

        //If no brackets then first bracket!
        if(!$brackets) {
            return 1;
        }

        $open_brackets = [];

        $highest_bracket = 0;

        //Aggregate all the brackets and get their count
        foreach($brackets as $bracket) {

            if(!isset($open_brackets[$bracket->bracket]) ||  (isset($open_brackets[$bracket->bracket]) && !$open_brackets[$bracket->bracket])) {
                $open_brackets[$bracket->bracket] = 0;
            }

            $open_brackets[$bracket->bracket] += 1;

            if($bracket->bracket > $highest_bracket) {
                $highest_bracket = $bracket->bracket;
            }
        }

        //If a bracket is open, assign user
        foreach($open_brackets as $key => $open_check) {

            if($open_brackets[$key]<$users_per_bracket) {
                return $key;
            }
        }

        //If no brackets are found, create a new one
        return  ($highest_bracket + 1);

    }

    public static function autoAssignCompetitorsTeams(Competition $competition, int $round, int $teams_per_bracket) {

        // Try to get previous round
        $current_round = CompetitionRound::where('competition_id', $competition->id)
        ->where('round', $round -1)
        ->first();

        $competitors = null;

        if(!$current_round) {
            //If no previous round, get current round
            $current_round = CompetitionRound::where('competition_id', $competition->id)
            ->where('round', $round)
            ->first();

            if(!$current_round) {
                $current_round = CompetitionRound::create(['competition_id' => $competition->id, 'round' => $round]);
            }

            $competitors = Team::where('competition_id', $competition->id)
            ->join('competition_teams', 'teams.id', '=', 'competition_teams.team_id')
            ->get();


        } else {

            $competitors = Team::where('competition_id', $competition->id)
            ->where('round', $round -1)
            ->where('is_winner', true)
            ->join('competition_round_brackets', 'teams.id', '=', 'competition_round_brackets.team_id')
            ->get();

        }


        foreach($competitors as $competitor) {

            $bracket = CompetitionRoundBracket::where('competition_id', $competition->id)
            ->where('team_id', $competitor->id)
            ->where('round', $round)
            ->first();

            if(!$bracket) {

                $bracket_placement = self::getOpenTeamBracket($competition, $round, $teams_per_bracket);

                CompetitionRoundBracket::create([
                    'competition_id' => $competition->id,
                    'team_id' => $competitor->team_id,
                    'round' => $round,
                    'bracket' => $bracket_placement
                ]);

            }

        }

        return CompetitionRoundBracket::where('competition_id', $competition->id)
        ->where('round', $round)
        ->get();
    }

    public static function getOpenTeamBracket(Competition $competition, int $round, int $teams_per_bracket) {

        $brackets =  CompetitionRoundBracket::where('competition_id', $competition->id)
        ->where('round', $round)
        ->get();

        //If no brackets then first bracket!
        if(!$brackets) {
            return 1;
        }

        $open_brackets = [];

        $highest_bracket = 0;

        //Aggregate all the brackets and get their count
        foreach($brackets as $bracket) {

            if(!isset($open_brackets[$bracket->bracket]) ||  (isset($open_brackets[$bracket->bracket]) && !$open_brackets[$bracket->bracket])) {
                $open_brackets[$bracket->bracket] = 0;
            }

            $open_brackets[$bracket->bracket] += 1;

            if($bracket->bracket > $highest_bracket) {
                $highest_bracket = $bracket->bracket;
            }
        }

        //If a bracket is open, assign user
        foreach($open_brackets as $key => $open_check) {

            if($open_brackets[$key] < $teams_per_bracket) {
                return $key;
            }
        }

        //If no brackets are found, create a new one
        return  ($highest_bracket + 1);

    }

    public static function canRegisterTeam(Competition $competition, Team $team, User $user) {

        if(!PermissionsFacade::competitionCanEngage($competition, $user)){
            return ['status' => false, 'message' => Messages::ERROR_COMPETITION_BANNED];
        }

        $has_registered = CompetitionTeam::where('competition_id', $competition->id)
        ->where('team_id', $team->id)
        ->first();

        if($has_registered) {
            return ['status' => false, 'message' => Messages::ERROR_COMPETITION_TEAM_ALREADY_REGISTERED];
        }

        if($competition->max_registration_for_teams && $competition->max_registration_for_teams>0) {

            $registered = CompetitionTeam::where('competition_id', $competition->id)->count();


            if($registered >= $competition->max_registration_for_teams){
                return ['status' => false, 'message' => Messages::ERROR_COMPETITION_TEAM_MAX_REGISTRATIONS, 'max' => $competition->max_registration_for_teams , 'current' => $registered];
            }
        }

        return['status' => true];
    }

    public static function canRegisterUser(Competition $competition, User $user) {

        if(!PermissionsFacade::competitionCanEngage($competition, $user)){
            return ['status' => false, 'message' => Messages::ERROR_COMPETITION_BANNED];
        }

        $registered = CompetitionUser::where('competition_id', $competition->id)
        ->where('user_id', $user->id)
        ->where('user_role', Roles::Participant)
        ->first();

        if($registered) {
            return ['status' => false, 'message' => Messages::ERROR_COMPETITION_USER_ALREADY_REGISTERED];
        }

        if($competition->max_registration_for_users && $competition->max_registration_for_users > 0) {

            $registered = CompetitionUser::where('competition_id', $competition->id)
            ->where('user_role', Roles::Participant)
            ->count();


            if($registered >= $competition->max_registration_for_users){
                return ['status' => false, 'message' => Messages::ERROR_COMPETITION_USER_MAX_REGISTRATIONS, 'max' => $competition->max_registration_for_users , 'current' => $registered];
            }
        }

        return['status' => true];
    }

    public static function registerTeam(Competition $competition, Team $team){

        $ct = CompetitionTeam::create([
            'team_id' => $team->id,
            'competition_id' => $competition->id
        ]);

        return $ct;
    }

    public static function registerUser(Competition $competition, User $user){

        $cu = CompetitionUser::create([
            'user_id' => $user->id,
            'competition_id' => $competition->id,
            'user_role' => Roles::Participant
        ]);

        return $cu;
    }
}