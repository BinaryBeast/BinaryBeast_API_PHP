<?php

/**
 * This class represents a single game within a match result withint a tournament
 * 
 * @version 1.0.0
 * @date 2013-02-01
 * @author Brandon Simmons
 */
class BBMatchGame extends BBModel {

    //Service names for the parent class to use for common tasks
    const SERVICE_LOAD   = 'Tourney.TourneyLoad.Info';
    const SERVICE_CREATE = 'Tourney.TourneyCreate.Create';
    const SERVICE_UPDATE = 'Tourney.TourneyUpdate.Settings';
    const SERVICE_DELETE = 'Tourney.TourneyDelete.Delete';

    //Array of teams within this tournament
    protected $tourney_match_id;

    //So the parent class knows which property use as the unique id
    protected $id_property = 'tourney_match_id';

    //Array of games within this match
    private $games;

    /**
     * Default values for a new tournament
     * @see BinaryBeast::update()
     * @var array
     */
    protected $default_values = array(
    );

    /**
     * Override the getter method - we may have to access games within the match instead of just general info
     */
    public function __get($name) {
        
        //If attempting to access the array of participants, load them now
        if($name == 'games' && is_null($this->games)) {
            
        }

        //Execute default __get method defined in the base BBModel class
        return parent::__get($name);
    }

    /**
     * Overrides the parent method import_values, but still uses it
     * This method uses the property tourney_data to use parent::import_values to 
     * save the result values into this class
     * 
     * @param object
     */
    protected function import_values($result) {
        parent::import_values($result->match_info);
    }
}

?>