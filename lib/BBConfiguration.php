<?php

/**
 * Configuration for the BinaryBeast API Library
 * 
 * This is stored in a seperate file to make it easier to pull changes to the library, without losing
 *  any configuration values
 */
final class BBConfiguration {
    /**
     *  <b>Your BinaryBeast Account API Key<b>
     *  <pre>
     *      You can find / manage your API key in your user settings at BinaryBeast.com
     *          {@link http://binarybeast.com/user/settings/api}
     *     
     *      Side note, here's a useful tool to debug as you develop: {@link http://binarybeast.com/user/settings/api_history}
     *  </pre>
     * 
     * 
     * @var string
     */
    public $api_key = 'e17d31bfcbedd1c39bcb018c5f0d0fbf.4dcb36f5cc0d74.24632846';

    /**
     * Path to class names used to extend the core BBModel library classes
     * 
     * You can extend any of the following classe:
     *  BBTournament, BBTeam, BBMatch, and BBMatchGame
     * 
     * @var string
     */
    public $models_extensions_lib = 'lib/custom/';
    
    /**
     * Custom model extension class names
     * 
     * Each class must be in a file name that exactly matches the class name
     * 
     * For exmaple, if you override BBTournament with LocalTournament, you must save it in 
     *      in lib/custom/LocalTournament.php
     * 
     * @var string[]
     */
    public $models_extensions = array(
        //'BBTournament'          => 'LocalTournament',
        //'BBTeam'                => 'LocalTeam',
        //'BBMatch'               => null,
        //'BBMatchGame'           => null,
    );

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