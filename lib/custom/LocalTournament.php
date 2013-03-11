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
 * 
 * @property LocalTeam[] $teams documentation for code hinting - to cast returned teams as LocalTeam instances
 */
class LocalTournament extends BBTournament {

    /**
     * Make sure that when loading teams, we send the "json_notes" argument, so that each team
     *  automtatically has a value for notes_decoded
     * 
     * @return LocalTeam[]
     */
    public function &teams($ids = false, $args = array()) {
        return parent::teams($ids, array_merge($args, array('json_notes' => true)));
    }

    /**
     * Returns the BBTeam within this tournament that is linked with the provided local $user_id
     * 
     * @param int $user_id      The local user_id
     * @return BBTeam|null      null if it could not be found
     */
    public function &get_user_team($user_id) {
        //Since we're limiting the scope to this tournament, we can rely on the result of teams()
        if(($teams = &$this->teams()) !== false) {
            
        } 
    }
}

?>