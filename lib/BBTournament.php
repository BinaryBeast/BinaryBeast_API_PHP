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

    /**
     * Default values for a new tournament
     * @see BinaryBeast::update()
     * @var array
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
     * Override the getter method - we may have to load teams instead of just general info
     */
    public function __get($name) {
        
        //If attempting to access the array of participants, load them now
        if($name == 'teams' && is_null($this->teams)) {
            
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
        /*
         * If called from BBModel, we may have been passed this value directly as a result of wrapping
         * a list of objects, in which case ->tourney_data wouldn't exist, make sure we pass it
         * back up to BBModel's import_values as was
         */
        parent::import_values(isset($result->tourney_data) ? $result->tourney_data : $result);
    }

    /**
     * Load a list of tournaments created by the user of the current api_key
     * 
     * Note: each tournament is actually instantiated as a new BBTournament class, so you 
     * can update / delete them in iterations etc etc
     * 
     * @param 
     */
    public function list_my($filter = null, $limit = 30, $private = true) {
        $result = $this->bb->call('Tourney.TourneyList.Creator', array(
            'filter'    => $filter,
            'page_size' => $limit,
            'private'   => $private,
        ));

        //OH NOES!
        if($result->result != BinaryBeast::RESULT_SUCCESS) {
            $this->result = $result;
            $this->set_error($result);
            return false;
        }

        //Success! Cast each returned tournament in a local BBTournament and return
        return $this->wrap_list($result->list);
    }
}

?>