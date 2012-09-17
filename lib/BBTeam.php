<?php

/**
 * This class represents a single BinaryBeast tournament
 */
class BBTeam extends BBModel {

    //Service names for the parent class to use for common tasks
    const SERVICE_LOAD   = 'Tourney.TourneyLoad.Team';
    const SERVICE_SAVE   = 'Tourney.TourneyTeam.Update';
    const SERVICE_DELETE = 'Tourney.TourneyTeam.Delete';

    /**
     * Define the load method
     */
    protected function load() {
        
    }
}

?>