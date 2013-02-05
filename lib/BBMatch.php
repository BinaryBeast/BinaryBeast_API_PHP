<?php

/**
 * This class represents a single match result withint a tournament
 * 
 * Important!  See BBMatch::default_values for possible values and descriptions
 * 
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 * 
 * @version 1.0.0
 * @date 2013-02-02
 * @author Brandon Simmons
 */
class BBMatch extends BBModel {

    //Service names for the parent class to use for common tasks
    const SERVICE_LOAD   = 'Tourney.TourneyLoad.Match'; 
    const SERVICE_CREATE = 'Tourney.TourneyMatch.Update';
    const SERVICE_UPDATE = 'Tourney.TourneyMatch.Update';
    const SERVICE_DELETE = 'Tourney.TourneyMatch.Delete';
    
    //This Match's ID, using BinaryBeast's naming convention
    public $tourney_match_id;

    //So BBModal knows which property use as the unique id
    protected $id_property = 'tourney_match_id';

    //Helps BBModal know how to extract the right value from the API result
    protected $data_extraction_key = 'match_info';

    /**
     * Public array of games within this match
     * @var array<BBMatchGame>
     */
    public $games;

    /**
     * Default values for a new tournament
     * @see BinaryBeast::update()
     * @var array
     */
    protected $default_values = array(
        //tourney_team_id of the match's overall winner
        'tourney_team_id'           => null,

        //tourney_team_id of the matche's overall loser
        'o_tourney_team_id'         => null,

        //Integer - Which bracket was this in?
        'bracket'                   => BinaryBeast::BRACKET_WINNERS,

        //General notes / description on the match
        'notes'                     => null,
    );

    /**
     * Override the getter method - we may have to access games within the match instead of just general info
     */
    public function &__get($name) {
        
        //If attempting to access the array of participants, load them now
        if($name == 'games' && is_null($this->games)) {
            //GOGOGO!
            $this->load_games();

            //Success! now finish the array we just created
            return $this->games;
        }

        //Execute default __get method defined in the base BBModel class
        return parent::__get($name);
    }

    /**
     * Overrides BBModal::load because we need to change the argument
     * of get_round when requesting the data from BinaryBeast
     * 
     * get_round asks BinaryBeast to make sure that it sends the round information
     * used for this match in addition to the match details
     * 
     * All we have to do is build additional paramaters and then let
     * BBModal handle the rest
     * 
     * @param mixed $id     If you did not provide an ID in the instantiation, they can provide one now
     * 
     * @return boolean - true if result is 200, false otherwise
     */
    public function &load($id = null, $args = array()) {
        //Let BBModal handle this, just pass it extra paramater
        return parent::load($id, array_merge(array('get_round' => true), $args) );
    }

    /**
     * BinaryBeast sends 'match_info', as well as the array 'games', so 
     * we need overload BBModal to ensure that the games array is imported
     * as well
     * 
     * Once games is imported, we pass control back to BBModal
     * 
     * If we find the games array, we will cast each value into a new
     *  BBMatchGame class, then pass control back to BBModal for the rest 
     * 
     * @param object $data
     * @return void
     */
    protected function import_values($data) {
        //Found it!
        if(isset($data->games)) {
            //Now loop through each game as instantiate a new BBMatchGame for it
            $this->games = array();
            foreach($data->games as &$game) {
                $this->games[] = new BBMatchGame($bb, $data);
            }
        }
        //Let BBModal handle the rest, business as usual
        return parent::import_values($data);
    }
}

?>