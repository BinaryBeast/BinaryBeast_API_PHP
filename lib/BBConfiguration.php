<?php

/**
 * Configuration for the BinaryBeast API Library
 * 
 * This is stored in a seperate file to make it easier to pull changes to the library, without losing
 *  any configuration values
 */
final class BBConfiguration {
    /**
     *     <b>Your BinaryBeast Account API Key<b>
     *     <pre>
     *         You can find / manage your API key in your user settings at BinaryBeast.com
     *         {@link http://binarybeast.com/user/settings/api}
     *     
     *         Side note, here's a useful tool to debug as you develop: {@link http://binarybeast.com/user/settings/api_history}
     *     </pre>
     * 
     * 
     * @var string
     */
    public $api_key = 'e17d31bfcbedd1c39bcb018c5f0d0fbf.4dcb36f5cc0d74.24632846';

    /*
     * 
     * Custom Libraries
     * 
     */

    /**
     * Path to custom classes made to extend core BBModel classes
     * 
     * You can extend any of the following classe:
     *  BBTournament, BBTeam, BBMatch, and BBMatchGame
     * 
     * @var string
     */
    public $custom_library_path = 'lib/custom/';

    /**
     * The name of the class used to extend BBTournament 
     * <b>Filename must be BBTournament.php</b>
     * 
     * @var string
     */
    public $custom_tournament = null;
    /**
     * The name of the class used to extend BBTeam 
     * <b>Filename must be BBTeam.php</b>
     * 
     * @var string
     */
    public $custom_team = 'SuperTeam';
    /**
     * The name of the class used to extend BBMatch 
     * <b>Filename must be BBMatch.php</b>
     * 
     * @var string
     */
    public $custom_match = null;
    /**
     * The name of the class used to extend BBMatchGame
     * <b>Filename must be BBMatchGame.php</b>
     * 
     * @var string
     */
    public $custom_match_game = null;


    /*
     * 
     * Database settings for BBCache
     * 
     */

    /**
     * Database type, currently supported values: 'mysql' ('postgresql' next probably)
     * @var string
     */
    public $cache_db_type = 'mysql';

    /**
     * Server to connect to, ie 'localhost', 'mydb.site.com', etc
     * @var string
     */
    public $cache_db_server = 'localhost';

    /**
     * Optional port to use when connecting to $server
     *      If not provided, we will use the default port
     *      based on the database $type defined
     * @var int
     */
    public $cache_db_port = null;
    /**
     * Database name
     * @var string
     */
    public $cache_db_database = 'test';

    /**
     * Name of the table to use
     *      This class will create the table, since we expect it to be in a certain format
     * @var string
     */
    public $cache_db_table = 'bb_api_cache';
    /**
     * Username for logging into the database
     * @var string
     */
    public $cache_db_username = 'test_user';
    /**
     * Password for logging into the database
     * @var string
     */
    public $cache_db_password = null;

} 

?>