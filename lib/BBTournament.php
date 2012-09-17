<?php

/**
 * This class represents a single BinaryBeast tournament
 */
class BBTournament extends BBModel {

    //Service names for the parent class to use for common tasks
    const SERVICE_LOAD   = 'Tourney.TourneyLoad.Info';
    const SERVICE_SAVE   = 'Tourney.TourneyUpdate.Settings';
    const SERVICE_DELETE = 'Tourney.TourneyDelete.Delete';

    //Array of teams within this tournament
    private $teams;

    /**
     * Define the load method
     */
    protected function load() {
        
    }
    
    /**
     * Override the getter method - we may have to load teams instead of just general inf
     */
    public function __get($name) {
        
        //Load teams
        if($name == 'teams' && is_null($this->teams)) {
            
        }

        //Pass control to the default getter
        return parent::__get($name);
    }

}

?>