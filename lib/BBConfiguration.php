<?php

/**
 * Configuration for the BinaryBeast API Library
 * 
 * This is stored in a separate file to make it easier to pull changes to the library, without losing
 *  any configuration values
 * 
 * @package BinaryBeast
 * @subpackage Library
 */
final class BBConfiguration {
    /**
     *  <b>Your BinaryBeast Account API Key</b><br />
     *  You can find / manage your API key in your user settings here: {@link http://binarybeast.com/user/settings/api}<br />
     * 
     * @var string
     */
    public $api_key = 'e17d31bfcbedd1c39bcb018c5f0d0fbf.4dcb36f5cc0d74.24632846';

    /**
     * Custom model extension class names
     * 
     * Extended models must reside in /lib/custom/{new_class_name}.php
     * 
     * For example, if you override BBTournament with LocalTournament, you must save it in
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
    public $cache_db_database = null;//'test';

    /**
     * Name of the table to use
     *      This class will create the table, since we expect it to be in a certain format
     * @var string
     */
    public $cache_db_table = null;//'bb_api_cache';
    /**
     * Username for logging into the database
     * @var string
     */
    public $cache_db_username = null;//'test_user';
    /**
     * Password for logging into the database
     * @var string
     */
    public $cache_db_password = null;

} 

?>