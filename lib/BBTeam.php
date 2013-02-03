<?php

/**
 * This class represents a single BinaryBeast tournament
 * 
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 * 
 * @version 1.0.0
 * @date 2013-02-02
 * @author Brandon Simmons
 */
class BBTeam extends BBModel {

    //Service names for the parent class to use for common tasks
    const SERVICE_LOAD   = 'Tourney.TourneyLoad.Team';
    const SERVICE_CREATE = 'Tourney.TourneyTeam.Insert';
    const SERVICE_UPDATE = 'Tourney.TourneyTeam.Update';
    const SERVICE_DELETE = 'Tourney.TourneyTeam.Delete';

    //This Team's ID, using BinaryBeast's naming convention
    public $tourney_team_id;

    //So BBModal knows which property use as the unique id
    protected $id_property = 'tourney_team_id';
    
    //Helps BBModal know how to extract the team data from the API result
    protected $data_extraction_key = 'team_info';
}


?>