<?php

/**
 * Proof of concept class - showing how to extend the functionality of the core library classes
 * 
 * We're overloading the BBTeam class so that we can define a few methods to handle loading
 *  teams by local user_ids, instead of by team_id
 * 
 * @version 1.0.0
 * @date 2013-03-10
 * @author Brandon Simmons
 */
class LocalTeam extends BBTeam {
    /**
     * The local user_id of the user this team represents
     * @var int
     */
    public $user_id;

    /**
     * Overload the data import, so that we can
     *  evaluate the value of $notes to try to find the $user_id
     */
    public function import_values($data) {
        //Default action first, actually import the data so we can work with it
        parent::import_values($data);

        //The api sent back the value notes_decoded - standardize it as an object if it has a value
        if(!is_null($this->notes_decoded)) {
            $this->notes_decoded = (object)$this->notes_decoded;

            //Success!
            if(isset($this->notes_decoded->user_id)) {
                $this->user_id = $this->notes_decoded->user_id;
            }
        }
    }

    /**
     * Save a local user_id with this team, so that we can later refer to it 
     *  by directly providing local user ids
     * 
     * @param int $user_id
     * @return boolean
     */
    public function set_user_id($user_id) {
        
    }
}

?>