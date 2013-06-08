<?php

/**
 * Model object representing the round format (best_of / date etc) within a {@link BBTournament}
 *
 * @todo write quick examples / tutorials here
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
 * @property-read int|null $map_id
 * <b>Read Only</b><br />
 * Value set when loading the round - the unique int id for the value of $map
 *
 * @property-read string|null $map_icon
 * URL of an icon of the map
 *
 * @property-read string|null $map_image
 * URL of a full image of the map
 *
 * @property string $game_code
 * Game code of the map if applicable
 * 
 * @property string $date
 *  A description of when this round should start<br />
 *  For the moment this is a simple string, it does not even validate the format
 *
 *
 * @package BinaryBeast
 * @subpackage Model
 * 
 * 
 * @version 3.0.4
 * @date    2013-05-03
 * @author  Brandon Simmons <contact@binarybeast.com>
 * @license http://www.opensource.org/licenses/mit-license.php
 * @license http://www.gnu.org/licenses/gpl.html
 */
class BBRound extends BBModel {

    //<editor-fold defaultstate="collapsed" desc="API service names">
    const SERVICE_CREATE = 'Tourney.TourneyRound.Update';
    const SERVICE_UPDATE = 'Tourney.TourneyRound.Update';
    const SERVICE_DELETE = 'Tourney.TourneyRound.Delete';
    //</editor-fold>

    //<editor-fold defaultstate="collapsed" desc="Cache settings">
    const CACHE_OBJECT_TYPE     = BBCache::TYPE_TOURNAMENT;
    const CACHE_TTL_LIST        = 10;
    const CACHE_TTL_LOAD        = 10;
    //</editor-fold>

    //<editor-fold defaultstate="collapsed" desc="Private properties / children arrays">
    /**
     * Keep a reference to the tournament that instantiated this class
     * @var BBTournament
     */
    private $tournament = null;

    /**
     * The bracket this round belongs to
     *
     * @todo this shouldn't be public...
     * 
     * @var int
     */
    public $bracket;
    /**
     * The round number this object represents with $bracket
     *
     * @todo this shouldn't be public...
     * 
     * @var int
     */
    public $round;
    //</editor-fold>

    //<editor-fold defaultstate="collapsed" desc="BBModel implementations">
    protected $default_values = array(
        'best_of'           => 1,
        'map_id'            => null,
        'map'               => null,
        'date'              => null,
    );
    protected $read_only = array('map_id', 'game_code', 'map_icon', 'map_image');
    //</editor-fold>

    /**
     * Since PHP doesn't allow overloading the constructor with a different parameter list,
     * we'll simply use a pseudo-constructor and call it init()
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
     * @ignore
     * {@inheritdoc}
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
     * Save the changes to this round
     *
     * Overloaded for special API argument requirements
     *
     * {@inheritdoc}
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
            //Clear cache
            $this->clear_list_cache();

            $this->sync_changes();
            return true;
        }

        /**
         * Oh noes!
         */
        else return $this->set_error($result);
    }
    /**
     * Since we don't have an id, and we don't create rounds
     *  one at a time, we handle this flag differently than BBModel
     *
     * @return boolean
     */
    public function is_new() {
        return false;
    }

    /**
     * Overloads the list-clearing method so we can
     *  specify clear tournament-object cache
     *
     * {@inheritdoc}
     */
    public function clear_list_cache() {
        if(!is_null($this->tournament)) {
            $this->tournament->clear_id_cache( BBTournament::SERVICE_LOAD_ROUNDS);
        }
    }
}