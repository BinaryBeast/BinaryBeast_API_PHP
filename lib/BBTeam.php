<?php

/**
 * This class represents a single BinaryBeast tournament
 * 
 * @version 0.0.1
 * @date 2013-01-22
 * @author Brandon Simmons
 */
class BBTeam extends BBModel {

    //Service names for the parent class to use for common tasks
    const SERVICE_LOAD   = 'Tourney.TourneyLoad.Team';
    const SERVICE_CREATE = 'Tourney.TourneyTeam.Insert';
    const SERVICE_UPDATE = 'Tourney.TourneyTeam.Update';
    const SERVICE_DELETE = 'Tourney.TourneyTeam.Delete';

    /**
     * Define the load method
     */
    protected function load() {
        
    }
}

?>