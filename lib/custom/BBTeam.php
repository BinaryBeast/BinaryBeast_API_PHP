<?php

/**
 * Proof of concept class - showing how to extend the functionality of the core library classes
 * 
 * We're over-loading the BBTeam class so that we can define a few methods to handle loading
 *  teams by local user_ids, instead of by team_id
 * 
 * @version 1.0.0
 * @date 2013-03-10
 * @author Brandon Simmons
 */
class SuperTeam extends BBTeam {
    /**
     * The local user_id of the user this team represents
     * @var int
     */
    public $user_id;
    /**
     * result of json_decode($notes);
     * @var object
     */
    private $user_data;

    /**
     * Overload the data import, so that we can
     *  evaluate the value of $notes to try to find the $user_id
     * 
     * @param type $data
     */
    public function import_values($data) {
        //Default action first, actually import the data so we can work with it
        parent::import_values($data);

        if(!is_null($this->user_data = json_decode($this->notes))) {
            //standardize it as an object
            $this->user_data = (object)$this->user_data;

            //Success!
            if(isset($this->user_data->user_id)) {
                $this->user_id = $this->user_data->user_id;
            }
        }
    }
}

?>