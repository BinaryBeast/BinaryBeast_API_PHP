<?php

/**
 * This class represents a single BinaryBeast tournament, 
 * and provides static methods for loading lists of public and private tournaments
 * 
 * @version 1.0.0
 * @date 2013-01-22
 * @author Brandon Simmons
 */
class BBTournament extends BBModel {

    //Service names for the parent class to use for common tasks
    const SERVICE_LOAD   = 'Tourney.TourneyLoad.Info';
    const SERVICE_CREATE = 'Tourney.TourneyCreate.Create';
    const SERVICE_UPDATE = 'Tourney.TourneyUpdate.Settings';
    const SERVICE_DELETE = 'Tourney.TourneyDelete.Delete';

    //Array of teams within this tournament
    private $teams;
    protected $tourney_id;

    //So the parent class knows which property use as the unique id
    protected $id_property = 'tourney_id';

    /*
     * Default values for a new tournament
     */
    protected $default_values = array(
        'title'             => 'PHP API Test',
        'game_code'         => 'HotS',
        'type_id'           => 0,
        'elimination'       => 1,
        'team_mode'         => 1,
        'group_count'       => 0,
        'teams_from_group'  => 2,
        'location'          => null,
        'max_teams'         => 32,
        'replay_uploads'    => BinaryBeast::REPLAY_UPLOADS_OPTIONAL,
        'replay_downloads'  => BinaryBeast::REPLAY_DOWNLOADS_ENABLED,
        'description'       => '',
        'return_data'       => 0,
    );

    /**
     * Constructor - saves a refernence to the API class, and second param can either by the tournament id, or the data to directly save
     * 
     * @param BinaryBeast $bb
     * @param mixed $tourney     Either provide the tourney_id to auto-load, or an object of data from an already loaded tournament
     */
    function __construct(&$bb, $tourney) {
        parent::__construct($bb);

        //If they provided the data directly save it all locally
        if(is_object($tourney)) {
            $this->import_values(200, $tourney);
            $this->tourney_id = $tourney->tourney_id;
        }

        //Save the ID
        else if(is_string($tourney)) {
            $this->tourney_id = $tourney;
        }
    }

    /**
     * Override the getter method - we may have to load teams instead of just general info
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