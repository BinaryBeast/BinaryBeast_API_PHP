<?php

/**
 * Model object representing the round format (best_of / date etc) within a {@link BBTournament}
 * 
 * 
 * 
 * @property int $best_of
 * <b>Default: 1</b><br />
 * Best of value for this round<br />
 * best_of 1 means first team to win one game wins the match<br />
 * best_of 3 means first team to win 2 two games wins the match<br />
 * {@link BBRound::$wins_needed}
 * 
 * @property-read int $wins_needed
 * <b>Read Only</b><br />
 * The number of wins a participant to win a match, based on the value of best_of<br />
 * Simple formula of ($best_of + 1) / 2<br />
 * {@link BBRound::$best_of}<br />
 * 
 * @property string|int $map
 * The first map in this round<br />
 * You can define this as the map_id integer, or any map name string<br />
 * Using a map_id is recommended over a simple map string - which may or may not be resolved as a map_id
 * 
 * @property-read int $map_id
 * <b>Read Only</b><br />
 * Value set when loading the round - the unique int id for the value of $map
 * 
 * @property string $date
 *  A description of when this round should start<br />
 *  For the moment this is a simple unformated string, it does not even validate the format
 * 
 * 
 * @package BinaryBeast
 * @subpackage Model
 * 
 * 
 * @version 3.0.0
 * @date 2013-03-26
 * @author Brandon Simmons <contact@binarybeast.com>
 * @license http://www.opensource.org/licenses/mit-license.php
 * @license http://www.gnu.org/licenses/gpl.html
 */
class BBRound extends BBModel {

    //Service names for the parent class to use for common tasks
    //const SERVICE_LOAD   = 'Tourney.TourneyLoad.Info'; //This class is instantiated manually, we don't call the load service from here
    const SERVICE_CREATE = 'Tourney.TourneyRound.Update';
    const SERVICE_UPDATE = 'Tourney.TourneyRound.Update';
    const SERVICE_DELETE = 'Tourney.TourneyRound.Delete';

    //Cache setup (cache for 10 minutes)
    const CACHE_OBJECT_TYPE     = BBCache::TYPE_TOURNAMENT;
    const CACHE_TTL_LIST        = 10;
    const CACHE_TTL_LOAD        = 10;

    /**
     * Keep a reference to the tournament that instantiated this class
     * @var BBTournament
     */
    private $tournament = null;

    /**
     * The bracket this round belongs to
     * 
     * @var int
     */
    public $bracket;
    /**
     * The round number this object represents with $bracket
     * 
     * @var int
     */
    public $round;

    /**
     * Default values for a new tournament
     * @see BinaryBeast::update()
     * @var array
     */
    protected $default_values = array(
        'best_of'           => 1,
        'map_id'            => null,
        'map'               => null,
        'date'              => null,
    );

    /**
     * Since PHP doens't allow overloading the constructor with a different paramater list,
     * we'll simply use a psuedo-constructor and call it init()
     * 
     * @param BBTournament $tournament
     * @param int $bracket
     * @param int $round
     * @return void
     */
    public function init(BBTournament &$tournament, $bracket, $round) {
        $this->tournament   = &$tournament;
        $this->bracket      = $bracket;
        $this->round        = $round;

		//Let BBModel know who our parent is, so changes are flagged correctly
		$this->parent = &$this->tournament;
    }

    /**
     * Overloaded so that we can validate the value of best_of,
	 *		and re-calculate the wins_needed when it changes
     * 
     * @see BBModel::__set()
     * 
     * @ignore
     * 
     * @return void
     */
    public function __set($name, $value) {
        //Make sure that when setting best_of, that it's a valid value
        if($name == 'best_of') {
			$value = BBHelper::get_best_of($value);
			//Store directly into $data - if we reset, it'll be overwritten automatically
			$this->data['wins_needed'] = BBHelper::get_wins_needed($value);
		}
        //setting map_id - change map instead
        if($name == 'map_id') $name = 'map';

        //Let the default method handle the rest
        parent::__set($name, $value);
    }

    /**
     * Overloadsd BBModel because we have unique needs as far as how to
     * let know BinaryBeast what our unique id is, we a round
     * doesn't actually have a unique id value, it's a combination 
     * of tournament id, round, and bracket
     * 
     * We also notify the tournament this round no longer has unsaved changes
     * 
     * @return boolean
     */
    public function save($return_result = false, $child_args = null) {

        //Nothing to save
        if(!$this->changed) return true;

        /**
         * Build the arguments to send to BinaryBeast
         * Unfortunately for this service, we have to be careful to 
         * include all values, even if unchanged, because this particular
         * service is rather old on BB's end and won't ingore the fact
         * that some values may be missing, it'll actually update the db to 
         * "null" if we were to call the update service without defining it
         */
        $svc = self::SERVICE_UPDATE;
        $args = array_merge(array(
            'tourney_id'            => $this->tournament->tourney_id,
            'bracket'               => $this->bracket,
            'round'                 => $this->round
        ), $this->data);

        //GOGOGO!
        $result = $this->call($svc, $args);

		//Success!
        if($result->result == BinaryBeast::RESULT_SUCCESS) {
            $this->sync_changes();
            return true;
        }

        /**
         * Oh noes!
         */
        else return $this->set_error($result);
    }
    /**
     * Since we dont' have an id, and we don't create rounds
     *  one at a time, we handle this flag differently than BBmodel
     * 
     * @return boolean
     */
    public function is_new() {
        return false;
    }
}

/**
 * The object structure used in {@link BBTournament::rounds()}
 * 
 * This class is never used, it soley exists for documentation
 *  
 * @package BinaryBeast
 * @subpackage Model_ObjectStructure
 */
abstract class BBRoundObject {
    /**
     * Array of BBRound objects for each round in the group rounds
     * @var BBRound[]
     */
    public $groups;
    /**
     * Array of BBRound objects for each round in the Winners' bracket
     * @var BBRound[]
     */
    public $winners;
    /**
     * Array of BBRound objects for each round in the Losers' bracket
     * @var BBRound[]
     */
    public $losers;
    /**
     * Array of BBRound objects for each round in the grand finals
     * @var BBRound[]
     */
    public $finals;
    /**
     * Array of BBRound objects for each round in the Bronze bracket
     * @var BBRound[]
     */
    public $bronze;
}

?>