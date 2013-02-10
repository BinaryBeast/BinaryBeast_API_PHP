<?php

/**
 * A very basic class that provides some convenience methods, for helping
 * developers determine tournament-specific values.. such 
 * as calculating the number of rounds in a bracket, or 
 * determining the next power of 2 (like 11 would become 16)
 * 
 * This class is used statically by the way
 * 
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 * 
 * @version 1.0.0
 * @date 2013-02-03
 * @author Brandon Simmons
 */
class BBHelper {

    /**
     * Reverse of list of result code value constants in BinaryBeast,
     * allowing us to quickly translate a result code into a 
     * human-readable string
     * @var array
     */
    public static $results_codes = array(
        '200'               => 'Success',
        '401'               => 'Login Failed',
        '403'               => 'Authentication error - you are not allowed to delete / update that object',
        '404'               => 'Generic not found error - Either an invalid *_id or invalid service name (service name being Tourney.TourneyCreate.Create for example)',
        '405'               => 'Your account is not permitted to call that particular service',
        '406'               => 'Incorrect / Invalid E-Mail address (likely while trying to log in)',
        '415'               => 'E-Mail address is already in use',
        '416'               => 'Malformed E-Mail address',
        '418'               => 'E-Mail address is currently pending activation',
        '425'               => 'Your account is banned! Try viewing the result directly for a explaination',
        '450'               => 'Incorrect Password',
        '461'               => 'Invalid game_code',
        '465'               => 'Invalid bracket number, use the BinaryBeast::BRACKET_* constants for available options',
        '470'               => 'Duplicate entry',
        '500'               => 'Generic error, likely something wrong on BinaryBeast\'s end',
        '601'               => 'The "$filter" value provided is too short',
        '604'               => 'Invalid user_id',
        '705'               => 'Proivded tourney_team_id and tourney_id do not match!',        
        '704'               => 'Tournament not found / invalid tourney_id',
        '706'               => 'Team not found / invalid tourney_team_id',
        '708'               => 'Match not found / invalid tourney_match_id',
        '709'               => 'Match Game not found / invalid tourney_match_game_id',
        '711'               => 'Tournament does not have enough teams to fill the number of groups ($tournament->group_count) you have defined, either add more teams or lower your group_count setting',
        '715'               => 'The tournament\'s current status does not allow this action (For example trying to add players to an active tournament, or trying to start a touranment that is already complete)',
    );
    /**
     * Allows translating a touranment's type_id into a string
     * @var array
     */
    private static $tournament_types = array(0 => 'Elimination Brackets', 1 => 'Cup');

    /**
     * Given a bracket integer, return the string description, 
     * for example 1 would return "Winners"
     * 
     * See the constants defined in this class
     * 
     * @param int   $bracket
     * @param bool  $short       For shortened version, convenient for array keys (ie bronze instead of 'Bronze Bracket (3rd place)'
     * @return string
     */
    public static function get_bracket_label($bracket, $short = false) {
        //Array of labels that directly relate to the Bracket integer (like 0 = Groups), 
        //Values depend on wether or not $short was requested
        $labels = $short
            ? array('groups', 'winners', 'losers', 'finals', 'bronze')
            : array(
            'Groups',
            'Winners Bracket',
            'Losers Bracket',
            'Finals',
            'Bronze Bracket (3rd place)',
        );

        return $labels[$bracket];
    }

    /**
     * Returns the next power of two given the number of players
     * 
     * IE 31 => 32
     * 
     * @param int $players
     * @return int
     */
    public static function get_bracket_size($players) {
        //Reasonable limits
        if($players < 2) return 2;
        if($players > 1024) return 1024;

        //Increment until we hit a power of two (using binary math (AND Operator)
        while($players & ($players - 1)) ++$players;

        return $players;
    }

    /**
     * Given the number of players in a tournament,
     * calculate the number of rounds in the Winners' bracket
     * 
     * @param int $players
     * @return int
     */
    public static function get_winner_rounds($players) {
        //Yummy logarithm sexiness!
        return log($players, 2);
    }

    /**
     * Given the number of players in a tournament,
     * calculate the number of rounds in the Losers' bracket
     * 
     * Note that BinaryBeast adds an extra "fake" round for redrawing
     * the winner of a bracket, so the result of this method may not match 
     * remote BinaryBeast values
     * 
     * @param int $players
     * @return int
     */
    public static function get_loser_rounds($players) {
        //Basically the number of rounds in the winners bracket, plus another bracket half the size - 1
        return $this->get_winner_rounds($players)
                + $this->get_winner_rounds($players / 2) - 1;
    }
    
    /**
     * Attempts to return a human-friendly readable explanation 
     * for the given result code
     * 
     * Returns null if the provided code does not have a description defined for it
     * (You can email contact@binarybeast.com if you ever run into this) 
     * 
     * @example 403 becomes "Authentication error - you are not allowed to delete / update that object"
     * 
     * @param string $result
     */
    public static function translate_result($result) {
        //Only bother if it's a string/number
        if(is_string($result) || is_numeric($result)) {
            if(key_exists($result, self::$results_codes)) {
                return self::$results_codes[$result];
            }
        }
        
        //If unable to find af riendly version, just pass back the original input
        return $result;
    }

    /**
     * If the provided best_of value is invalid, we'll 
     * parse it as an integer and replace the next highest acceptable value,
     * because bear in mind: best_of MUST be an odd number
     * 
     * @param int $best_of
     * @return int
     */
    public static function get_best_of($best_of) {
        $best_of = abs(intval($best_of));
        if($best_of < 1) return 1;
        return $best_of + ($best_of %2 == 0 ? 1 : 0);
    }

    /**
     * Attempts to return the string value of a tournament's type_id
     * 
     * @param int $type_id
     */
    public static function tournament_type_id_to_string($type_id) {
        if(isset(self::$tournament_types[$type_id])) {
            return self::$tournament_types[$type_id];
        }
        return null;
    }
    
    /**
     * Simply reduces several status options down to a simple: active | not active
     * 
     * It will return true for any of the following values for $status:
     * Active, Active-Groups, Active-Brackets, Complete
     *
     * @param BBTournament $tournament
     * @return boolean
     */
    public static function tournament_is_active(&$tournament) {
        return in_array($tournament->status, array('Active', 'Active-Groups', 'Active-Brackets', 'Complete'));
    }
    
    /**
     * Evaluates the given tournament to see if it's currently in the group rounds stage
     * returns true if in group rounds, false otherwise
     * 
     * The biggest reason for moving this to BBHelper is that in the future, BinaryBeast may
     *  change the way it handles different tournament stages / phases, hopefully for the better
     * 
     * @param BBTournament $tournament
     * @return boolean
     */
    public static function tournament_in_group_rounds(&$tournament) {
        return $tournament->status == 'Active-Groups';
    }

    /**
     * Evaluates the given tournament to see if it has active brackets
     * 
     * The biggest reason for moving this to BBHelper is that in the future, BinaryBeast may
     *  change the way it handles different tournament stages / phases, hopefully for the better
     * 
     * Warning: if will return false even if the tournament is complete, it STRICTLY returns
     *      true for tournaments with ACTIVE brackets
     * 
     * @param BBTournament $tournament
     * @return boolean
     */
    public static function tournament_in_brackets(&$tournament) {
        return $tournament->status == 'Active' || $tournament->status == 'Active-Brackets';
    }

}

?>