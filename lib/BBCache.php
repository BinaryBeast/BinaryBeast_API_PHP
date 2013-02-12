<?php

/**
 * This class is used to cache API Request results
 * 
 * drastically cuts down on API requests that you need to make, without having to do any
 *      extra coding
 * 
 * It can cache anything that you could get through $bb->call
 * 
 * @version 1.0.0
 * @date 2013-02-11
 * @author Brandon Simmons
 */
class BBCache {
    /** @var PDO */
    private $db;
    /** @var BinaryBeast */
    private $bb;
    /**
     * Table name used to store the api results
     */
    private $table;

    /**
     * Constructor
     * Stores local references to the API library, and the database connection
     * 
     * @param BinaryBeast   $bb
     * @param PDO           $db
     */
    function __construct(BinaryBeast &$bb, PDO $db)
    {
        $this->bb   = $bb;
        $this->db   = $db;
    }

    /**
     * As the name indicates, this method will delete any records that have expired, forcing new API calls when requested again
     * @return boolean
     */
    public function clear_expired()
    {
        return $this->db->query("
            DELETE FROM {$this->table}
            WHERE TIMESTAMPDIFF(SECOND, UTC_TIMESTAMP(), expires) <= 0
        ");
    }
    /**
     * Basically truncates the cache table
     * @return boolean
     */
    public function clear_all()
    {
        return $this->db->query("TRUNCATE TABLE {$this->table}");
    }
    /**
     * Delete all clache for the given touranment
     * 
     * Useful for triggered events that would require fresh API responses
     * 
     * @param string $tourney_id
     */
    public function clear_tourney_id($tourney_id)
    {
       $this->db->query("
           DELETE FROM {$this->table}
           WHERE tourney_id = $tourney_id
       "); 
    }
    /**
     * Clear any cache from a specific tournament and service pair
     * 
     * @param string $svc
     * @param string $tourney_id 
     */
    public function clear_tourney_service($svc, $tourney_id)
    {
        $this->db->query("
            DELETE FROM {$this->table}
            WHERE service = '$svc'
                AND tourney_id = $tourney_id
        ");
    }
    /**
     * Clear any cache from a specific game_code and service pair
     * 
     * @param string $svc
     * @param string $tourney_id 
     */
    public function clear_game_code_service($svc, $game_code)
    {
        $this->db->query("
            DELETE FROM {$this->table}
            WHERE service = '$svc'
                AND game_code = '$game_code'
        ");
    }
    /**
     * Clear any cache from a specific team and service pair
     * 
     * @param string $svc
     * @param string $tourney_team_id 
     */
    public function clear_team_service($svc, $tourney_team_id)
    {
        $this->dbc->query("
            DELETE FROM {$this->table}
            WHERE service = '$svc'
                AND tourney_team_id = $tourney_team_id
        ");
    }
    /**
     * Celete all cache for the given team
     * 
     * Useful for triggered events that would require fresh API responses
     * 
     * @param int $tourney_team_id 
     */
    public function clear_tourney_team_id($tourney_team_id)
    {
        $this->db->query("
            DELETE FROM {$this->table}
            WHERE tourney_team_id = $tourney_team_id
        ");
    }
    /**
     * Celete all cache for the given game_code
     * 
     * Useful for triggered events that would require fresh API responses
     * 
     * @param int $tourney_team_id 
     */
    public function clear_game_code($game_code)
    {
        $this->db->query("
            DELETE FROM {$this->table}
            WHERE game_code = '$game_code'
        ");
    }

    /**
     * Can be used in place of $bb->call, this method will check the local
     * cache table for any results from previous identical calls
     * 
     * It does not match arguments, but it matches tourney_id or tourney_team_id with the service
     * 
     * @param string    $svc
     * @param array     $args
     * @param int       $expire_seconds            In seconds, how long to keep the result as valid
     * @param string    $tourney_id 
     * @param int       $tourney_team_id 
     * @param string    $game_code
     * 
     * @return boolean
     */
    public function call($svc, $args, $expire_seconds, $tourney_id = null, $tourney_team_id = null, $game_code = null)
    {
        //Build the WHERE clause to try to find a cacheed response in the local database
        $Where = "service = '$svc' AND "
                . (is_null($tourney_id)      ? 'tourney_id IS NULL '      : "tourney_id = $tourney_id")
                . ' AND '
                . (is_null($tourney_team_id) ? 'tourney_team_id IS NULL ' : "tourney_team_id = $tourney_team_id")
                . ' AND '
                . (is_null($game_code)       ? 'game_code IS NULL '       : "game_code = '$game_code'");

        $result = $this->db->get_results("
            SELECT result, TIMESTAMPDIFF(SECOND, UTC_TIMESTAMP(), expires) AS seconds_remaining
            FROM {$this->table}
            WHERE $Where
        ");

        //Success!.. but only if it hasn't expired
        if(sizeof($result) > 0)
        {
            $row = &$result[0];

            //Success!
            if(intval($row->seconds_remaining) > 0) return json_decode($row->result);
        }
        
        //GOGO make the call, and save the results as json
        $result = $this->bb->call($svc, $args);

        //Prepare values for the local db
        $result_json = $this->db->escape(json_encode($result));
        if(is_null($tourney_id))        $tourney_id         = 'NULL';
        if(is_null($tourney_team_id))   $tourney_team_id    = 'NULL';
        $game_code  = is_null($game_code)  ? 'NULL' : "'$game_code'";

        //Either create a new record or update the existing one, either way we're caching the API response in our local database
        $this->db->query("
            INSERT INTO {$this->table}
            (service, tourney_id, tourney_team_id, game_code, result, expires)
            VALUES('$svc', $tourney_id, $tourney_team_id, $game_code, '$result_json', DATE_ADD(UTC_TIMESTAMP(), INTERVAL '$expire_seconds' SECOND))

            ON DUPLICATE KEY UPDATE
                result = '$result_json'
                , expires = DATE_ADD(UTC_TIMESTAMP(), INTERVAL '$expire_seconds' SECOND)
        ");

        //Return the direct result from $bb
        return $result;
    }
}

?>