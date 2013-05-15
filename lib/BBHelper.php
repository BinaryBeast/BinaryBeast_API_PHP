<?php

/**
 * Simple class that helpers translating and verifying
 * 
 * ## Tournament validation
 * 
 * This class can be used to check the tournament to see if it's in the 
 * right condition for certain tasks
 * 
 * For example, {@link BBHelper::tournament_can_start} checks to make sure the provided
 *  tournament has enough confirmed teams to start, and takes into account
 *  starting group rounds
 * 
 * ## Translation
 * 
 * Most of the funcionality in this class is for translating values, to make them more friendly
 * 
 * For example, {@link BinaryBeast::call()} automatically translates all API result codes, 
 *  and stores them in {@link BinaryBeast::last_result}, so you can always access <b>$bb->last_result_friendly</b> 
 *  after an API call
 * 
 * There are many other translations methods, just look through the documentation to see what's available
 * 
 * 
 * @package BinaryBeast
 * @subpackage Library
 * 
 * @version 3.0.3
 * @date    2013-04-13
 * @author  Brandon Simmons <contact@binarybeast.com>
 * @license http://www.opensource.org/licenses/mit-license.php
 * @license http://www.gnu.org/licenses/gpl.html
 */
class BBHelper {

    /**
     * Reverse of list of result code value constants in BinaryBeast,
     * allowing us to quickly translate a result code into a 
     * human-readable string
     * @var array
     */
    public static $result_codes = array(
        '200'               => 'Success',
        '401'               => 'Login Failed. Please insure that you have an api_key defined in lib/BBConfiguration.php.  Note that any values passed to the BinaryBeast() constructor will be treated as an api_key',
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
        '705'               => 'Provided tourney_team_id and tourney_id do not match!',
        '704'               => 'Tournament not found / invalid tourney_id (or.. invalid map_id)',
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
     * Simple array of group names / labels
     */
    private static $group_labels = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');

    /**
     * There are many "translation" methods in this class
     * To keep the code dry, they all utilize this for the actual grunt work
     * 
     * @ignore
     * @param mixed $value
     * @param array $translations
     * @return string|int|null
     */
    private static function translate($value, $translations) {
        //Invalid value type - just send it back
        if(!is_string($value) && !is_numeric($value)) return $value;

        //Return the translation, the original input if it's not defined
        return isset($translations[$value]) ? $translations[$value] : $value;
    }

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

        return self::translate($bracket, $labels);
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
        return self::get_winner_rounds($players)
                + self::get_winner_rounds($players / 2) - 1;
    }

    /**
     * Calculate the number of teams that will be in each group, based on
     *  the tournament's group_count, and the number of confirmed teams
     * 
     * Warning: This method should only be used before groups have started, because
     *      Afterwards it will no longer necessarily be accurate
     * 
     * Also beware that it's based on the number of teams currently confirmed in the tournament,
     *  so it will change if you add / remove any teams before starting
     * 
     * If you get a value of 0 back, it means that you don't have enough teams to fill
     *  the groups, you should either add more teams or lower the $tournament->group_count value 
     * 
     * @param BBTournament $tournament
     * 
     * @return int
     */
    public static function get_group_size(BBTournament &$tournament) {
        //Just grab a list of confirmed team ids, and count how many we get
        $confirmed_ids = $tournament->confirmed_teams(true);
        $teams = sizeof($confirmed_ids);

        //Derp
        if($tournament->group_count == 0) return 0;

        //Simply divide teams by groups
        $size = $teams / $tournament->group_count;

        //Not enough teams, we need at LEAST 2 teams per group
        if($size < 2) {
            return 0;
        }

        //Success! return the rounded (up) value
        return ceil($size);
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
     * @return string|int
     */
    public static function translate_result($result) {
        return self::translate($result, self::$result_codes);
    }

    /**
     * Attempts to return the string value of a tournament's type_id
     * 
     * @param int $type_id
     * @return string|null
     */
    public static function translate_tournament_type_id($type_id) {
        return self::translate($type_id, self::$tournament_types);
    }

    /**
     * Translate the integer of replay_downloads into a readable string value
     * 
     * @param int $replay_downloads
     * @param bool $short               The value for post_complete is lengthy, set $short to false to shorten it
     * @return string
     */
    public static function translate_replay_downloads($replay_downloads, $short = false) {
        return self::translate($replay_downloads, array(
            0 => 'Disabled', 1 => 'Enabled',
            2 => $short ? 'Post-Complete' : 'Post-Complete (Downloads enabled after tournament is complete)'
        ));
    }
    /**
     * Translate the integer of replay_uploads into a readable string value
     * 
     * @param int $replay_uploads
     * @return string
     */
    public static function translate_replay_uploads($replay_uploads) {
        return self::translate($replay_uploads, array(0 => 'Disabled', 1 => 'Optional', 2 => 'Mandatory'));
    }
    /**
     * Translate a tournament team's integer "status" value into a readable string value
     * @param int $status
     * @return string
     */
    public static function translate_team_status($status) {
        return self::translate($status, array(-1 => 'Banned', 0 => 'Unconfirmed', 1 => 'Confirmed'));
    }
    /**
     * Translates tournament elimination int to readable string
     * @param int $elimination
     * @return string
     */
    public function translate_elimination($elimination) {
        return self::translate($elimination, array(1 => 'Single', 2 => 'Double'));
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
     * Simply reduces several status options down to a simple: active | not active
     * 
     * It will return true for any of the following values for $status:
     * Active, Active-Groups, Active-Brackets, Complete
     *
     * @param BBTournament $tournament
     * @return boolean
     */
    public static function tournament_is_active($tournament) {
        if(!($tournament instanceof BBTournament)) return false;
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
    public static function tournament_in_group_rounds(BBTournament &$tournament) {
        return $tournament->status == 'Active-Groups';
    }

    /**
     * Evaluates the given tournament to see if it has group rounds in it
     * 
     * Unlike in_group_rounds, this will also return true if the tournament has group rounds in a previous phase
     * 
     * @param BBTournament $tournament
     * @return boolean
     */
    public static function tournament_has_group_rounds(BBTournament &$tournament) {
        if($tournament->status == 'Active-Groups' || $tournament->status == 'Active-Brackets') return true;
        return $tournament->status == 'Complete' && $tournament->type_id == BinaryBeast::TOURNEY_TYPE_CUP;
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
    public static function tournament_in_brackets(BBTournament &$tournament) {
        return $tournament->status == 'Active' || $tournament->status == 'Active-Brackets';
    }
    /**
     * Evaluates the given tournament to see if it has brackets
     * 
     * Much like tournament_in_brackets, except this method also allows the tournament to be Complete
     * 
     * @param BBTournament $tournament
     * @return boolean
     */
    public static function tournament_has_brackets(BBTournament &$tournament) {
        return $tournament->status == 'Active' || $tournament->status == 'Active-Brackets' || $tournament->status == 'Complete';
    }

    /**
     * This method can tell us wether or not the provided tournament is 
     * currently in the position to be started
     * 
     * If the value returned is a string, it's the error message that can be used with set_error,
     * 
     * The only returned value that could indicate the tournament is ready, is (boolean) true
     * 
     * @param BBTournament $tournament
     * @return string|boolean
     */
    public static function tournament_can_start(BBTournament &$tournament) {

        //Doens't even exist!
        if(is_null($tournament->id)) {
            return 'Tournament does not have a tourney_id, save it first!';
        }

        //Tournament is already complete
        if($tournament->status == 'Complete') {
            return 'This tournament is already finished';
        }

        //Currently in the final stages
        if($tournament->status == 'Active-Brackets' || $tournament->status == 'Active') {
            return 'This tournament is currently in it\'s final bracket stage, there\'s nothing left to start (consider executing $tournament->close(), or $tournament->reopen())';
        }

        //Tournament has unsaved changes
        if($tournament->changed) {
            return 'Tournament currently has unsaved changes, you must save doing something as drastic as starting the tournament';
        }

        /**
         * Honestly any other errors at this point require more work, and ther'e no point
         * if the API would let us know anyway - so from this point, we assume green lights
         */
        return true;
    }

    /**
     * Helps us figure out which stage is next for the tournament
     * 
     * Returns null already "Complete"
     * 
     * Biggest reason for putting this into a separate method is again.. BinaryBeast will undoubtedly
     *      be revisiting the way it handles transitions between phases in order to allow administrators to
     *      actually define every stage, with unlimited flexibility - so this logic will have to change soon
     * 
     * @param BBTournament $tournament
     * @return string
     */
    public static function get_next_tournament_stage(BBTournament &$tournament) {
        if(!self::tournament_is_active($tournament)) {
            //Next step for cups - group rounds
            if($tournament->type_id == BinaryBeast::TOURNEY_TYPE_CUP) {
                return 'Active-Groups';
            }

            //Next step for elimination - brackets
            return 'Active';
        }

        //For group rounds - brackets
        if($tournament->status == 'Active-Groups') {
            return 'Active-Brackets';
        }

        //For brackets - Complete
        if($tournament->status == 'Active' || $tournament->status == 'Active-Brackets') {
            return 'Complete';
        }

        //Tournament is already finished, nowhere left to go
        return null;
    }
    
    /**
     * Returns an array of empty groups, keyed by group name
     * 
     * Convenient if you'd like to use it to arrange your teams into groups before starting your tournament
     * 
     * @param BBTournament $tournament
     * @return array
     */
    public static function get_empty_groups(BBTournament &$tournament) {
        //Initialize the output array
        $groups = array();

        //to avoid calculating sizeof within a loop, just calculate it once now
        $labels_count = sizeof(self::$group_labels);

        //For each group configured, create an empty array keyed by the group label
        for($x = 0; $x < $tournament->group_count; $x++) {
            //If we've exceeded our list of labels, just start doubling up (AA, AB, AC, not going to even bother trying to move beyond A)
            if($x >= $labels_count) {
                $x2 = $x - $labels_count;
                $label = self::$group_labels[$x] . self::$group_labels[$x2];
            }
            else $label = self::$group_labels[$x];

            $groups[$label] = array();
        }

        //Success!
        return $groups;
    }
    
    /**
     * Returns a boolean flagging whehter or not the provided $bracket integer is relative
     *  to the tournament's current $status
     * 
     * @param BBTournament $tournament
     * @param int $bracket
     * @return boolean
     */
    public static function bracket_matches_tournament_status(BBTournament &$tournament, $bracket) {
        if($bracket == 0) return $tournament->status == 'Active-Groups';
        else if($bracket >= 1 && $bracket <= 4) return $tournament->status == 'Active' || $tournament->status == 'Active-Brackets';
        return false;
    }

    /**
     * Returns the standardized (all lower case) value for the provided $seeding value
     * 
     * For groups, your only options are 'random', and 'manual'
     * For brackets, your options are 'random', 'manual', 'balanced', 'sports'
     * 
     * Determining whether or not the value is valid is sensitive to the tournament type and status
     * 
     * @param BBTournament  $tournament     A reference to the tournament, so we can check the status and tournament type
     * @param string        $seeding        The seeding value you're trying to use
     * @return string   (null if invalid)
     */
    public static function validate_seeding_type(BBTournament &$tournament, $seeding) {
        $seeding = strtolower($seeding);

        //If tourney type is cup, and groups haven't started yet, we know next stage is groups, which means only random and manual are valid
        if($tournament->type_id == BinaryBeast::TOURNEY_TYPE_CUP && !self::tournament_is_active($tournament)) {
            return in_array($seeding, array('random','manual')) ? $seeding : null;
        }

        //If we're starting brackets, all seeding methods are available
        return in_array($seeding, array('random','manual','balanced','sports')) ? $seeding : null;
    }
	
	/**
	 * Based on the provided best_of value, calculate the number
	 *	of wins required to win the match
	 * @param int $best_of
	 * @return int
	 */
	public static function get_wins_needed($best_of) {
		return ($best_of + 1) / 2;
	}

	/**
	 * Returns an array of brackets available in the given touranment
	 * For example, for single elim elim brackets with bronze enabled, it may return
	 *	something like this: ['winners', 'bronze']
	 * 
	 * By default it returns bracket labels = set $lables to false and it will reutnr bracket integers instead
	 * 
	 * @param BBTournament	$tournament
	 * @param boolean		$labels		true by default - returns an array of brackets by label, instead of by number
	 *		ie 'groups' instead of 0, 'winners' instead of 1
	 * @param boolean		$arrays		false by default - if true, each value returned will be an empty array, as opposed to a string
	 * @return array
	 */
	public static function get_available_brackets(BBTournament &$tournament, $labels = true, $arrays = false) {
		$brackets = array();

		//Group rounds
		if($tournament->type_id ==  BinaryBeast::TOURNEY_TYPE_CUP) self::get_bracket($brackets, 0, 'groups', $labels, $arrays);

		//Winners
		self::get_bracket($brackets, 1, 'winners', $labels, $arrays);

		//Losers / finals
		if($tournament->elimination > 1) {
			self::get_bracket($brackets, 2, 'losers', $labels, $arrays);
			self::get_bracket($brackets, 3, 'finals', $labels, $arrays);
		}

		//Bronze
		else if($tournament->bronze) self::get_bracket($brackets, 4, 'bronze', $labels, $arrays);

		//Success!
		return $brackets;
	}
	/**
	 * Used by get_available_brackets to add a bracket to the 
	 *	$brackets array, based on labels / arrays 
     * @ignore
     * @param array     $brackets
     * @param int       $bracket
     * @param string    $label
     * @param boolean   $labels
     * @param boolean   $arrays
	 * @return void - works on $brackets by reference
	 */
	private static function get_bracket(&$brackets, $bracket, $label, $labels, $arrays) {
		//Use label if $labels = true
		if($labels) $bracket = $label;
		if($arrays) $brackets[$bracket] = array();
		else		$brackets[] = $bracket;
		
	}
    
    /**
     * Prints outs the iframe HTML needed to embed a tournament
     * 
     * @param BBTournament|string $tournament
     *  You may provide either the BBTournament object, or just the tournament id
     * @param boolean $groups
     *  By default if a tournament with rounds has progressed to the brackets, the groups will not be displayed
     *  however if you set this to true, the group rounds will be displayed instead
     * @param int|string $width
     *  Optionally define the width of the iframe
     *  Can either by a string (IE '100%', or a number (100 => becomes '100px')
     * @param int|string $height
     *  Optionally define the height of the iframe
     *  Can either by a string (IE '100%', or a number (100 => becomes '100px')
     * @param string $class
     *  A class name to apply to the iframe
     *  Defaults to 'binarybeast'
     * 
     * @return boolean
     *  prints out the html directly
     *  returns false if there was an error (like unable to determine the tournament id)
     */
    public static function embed_tournament($tournament, $groups = false, $width = 800, $height = 600, $class = 'binarybeast') {
        //If given a BBTournament, extract the ID
        if($tournament instanceof BBTournament) {
            $tournament = $tournament->id;
        }

        //Invalid tournament id
        if(!$tournament || is_null($tournament)) return false;
 
        //Figure out the URL based on $groups
        $url = 'http://binarybeast.com/' . $tournament . '/';
        if($groups) $url .= 'groups/';
 
        $url .= 'full';
 
        //Create an inline style based on $width and $height
        $width = 'width="' . $width . (is_numeric($width) ? 'px' : '') . '"';
        $height = 'height="' . $height . (is_numeric($height) ? 'px' : '') . '"';

        //Print out the result
        echo '<iframe src="', $url, '" class="', $class, '" ', $width, ' ', $height, ' scrolling="auto" frameborder="0" allowtransparency="true"></iframe>';
        return true;
    }

    /**
     * Prints outs the iframe HTML needed to embed a group rounds within a tournament
     * 
     * @param BBTournament|string $tournament
     *  You may provide either the BBTournament object, or just the tournament id
     * @param int|string $width
     *  Optionally define the width of the iframe
     *  Can either by a string (IE '100%', or a number (100 => becomes '100px')
     * @param int|string $height
     *  Optionally define the height of the iframe
     *  Can either by a string (IE '100%', or a number (100 => becomes '100px')
     * @param string $class
     *  A class name to apply to the iframe
     *  Defaults to 'binarybeast'
     * 
     * @return boolean
     *  prints out the html directly
     *  returns false if there was an error (like unable to determine the tournament id)
     */
    public static function embed_tournament_groups($tournament, $width = 800, $height = 600, $class = 'binarybeast') {
        return self::embed_tournament($tournament, true, $width, $height, $class);
    }
    

}

?>