<?php

/**
 * Locally Caches API request results
 * 
 * Uses a local MySQL database through PDO to store the values, it uses service-defined TTL values 
 *  to handle expired cache, and also provides methods that can be used to manually clear cache
 * 
 * The main focus of this class was to cut down on the number of API requests an application must make
 * 
 * It can cache anything that you could get through $bb->call
 * 
 * <b>MESSAGE TO DEVELOPERS!!!</b>
 * <pre>
 *      If you want to take advantage of the integrate API response caching in this class,
 *      you must have PDO_MySLQ installed, and you must define the connection details
 *      in {@link BBConfiguration}!
 * </pre>
 * 
 * @package BinaryBeast
 * @subpackage Library
 * 
 * @version 3.0.6
 * @date    2013-06-05
 * @since   2013-02-12
 * @author  Brandon Simmons <contact@binarybeast.com>
 * @license http://www.opensource.org/licenses/mit-license.php
 * @license http://www.gnu.org/licenses/gpl.html
 */
class BBCache {

    //<editor-fold defaultstate="collapsed" desc="Private properties">
    /**
     * @ignore
     * @var PDO
     */
    private $db;

    /**
     * @ignore
     * @var BinaryBeast
     */
    private $bb;

    /**
     * @ignore
     * @var BBConfiguration
     */
    private $config;

    /**
     * DSN Prefix values for each database type
     * @ignore
     */
    private $dsn_prefix = array('mysql' => 'mysql'/*, 'postgres' => 'pgsql', 'postgresql' => 'pgsql'*/);

    /**
     * Default ports for each database type
     * (keyed by dns_prefix)
     * @ignore
     */
    private $db_ports = array('mysql' => 3306, 'pgsql' => 5432);

    /**
     * After successfully connecting and checking for the existance
     * @ignore
     */
    private $connected = false;

    /**
     * PDO connection options per db type
     * (keyed by dns_prefix)
     * @ignore
     */
    private $pdo_options = array('mysql' => array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'), 'pgsql' => array());
    //</editor-fold>

    //<editor-fold defaultstate="collapsed" desc="Cache type constants">
    /**
     * ID for associating cached responses with a tournament
     * @var int
     */
    const TYPE_TOURNAMENT           = 0;
    /**
     * ID for associating cached responses with a team
     * @var int
     */
    const TYPE_TEAM                 = 1;
    /**
     * ID for associating cached responses with countries
     * @var int
     */
    const TYPE_COUNTRY              = 2;
    /**
     * ID for associating cached responses with a games
     * @var int
     */
    const TYPE_GAME                 = 3;
    /**
     * ID for associating cached responses with a races
     * @var int
     */
    const TYPE_RACE                 = 4;
    /**
     * ID for associating cached responses with a maps
     * @var int
     */
    const TYPE_MAP                  = 5;
    /**
     * ID for associating cached responses with a callbacks
     * @var int
     */
    const TYPE_CALLBACK				= 6;
    //</editor-fold>

    //<editor-fold defaultstate="collapsed" desc="TTL Duration Helper Constants">
    /**
     * TTL value for caching a result for 1 hour
     * @var int
     */
    const TTL_HOUR = 60;
    /**
     * TTL value for caching a result for 1 day
     * @var int
     */
    const TTL_DAY = 1440;
    /**
     * TTL value for caching a result for 1 week
     * @var int
     */
    const TTL_WEEK = 10080;
    /**
     * TTL value for caching a result for 30 days
     * @var int
     */
    const TTL_MONTH = 43200;
    //</editor-fold>

    /**
     * Constructor
     * Stores local references to the API library, and the database connection
     * 
     * @ignore
     * 
     * @param BinaryBeast       $bb
     * @param BBConfiguration   $config
     */
    function __construct(BinaryBeast &$bb, BBConfiguration &$config) {
        $this->bb       = &$bb;
        $this->config   = &$config;

        //If an error was returned while trying to connect, add it to BinaryBeast::$error_history
        if($this->check_values()) {
            if(($error = $this->connect()) !== true) {
                $bb->set_error($error, 'BBCache');
            }
        }
    }

    /**
     * @ignore
     */
	function __sleep() {
		return array('connected', 'config');
	}

    //<editor-fold defaultstate="collapsed" desc="Private validation / data preparation methods">
    /**
     * Simply returns a boolean to indicate whether or not
     *  all required values have been defined, because we'll
     *  simply fail silently if not configured
     * 
     * @ignore
     * @return boolean
     */
    private function check_values() {
        $values = array($this->config->cache_db_type, $this->config->cache_db_server, $this->config->cache_db_database,
            $this->config->cache_db_table, $this->config->cache_db_username);

        //Invalid
        if(in_array(null, $values)) return false;

        //Success!
        return true;
    }

    /**
     * Compress a value to save into the database
     * Allows us to save large API result sets directly into the database,
     *  without having to worry too much about taking up too much space
     *
     * @ignore
     * @param array     object to compress
     * @return string
     */
    private function compress($result) {
		//First, JSON encode the array / object into a string
		return json_encode($result);
    }
    /**
     * Decompress a value fetched from the database
     * @ignore
     */
    private function decompress($text) {
		//json
		return json_decode($text);
    }
    //</editor-fold>

    /**
     * Attempt to connect to the database
     * If any errors encounter while connecting, we will return
     *  the error message,
     *  otherwise if we're successful, we return true
     * 
     * So evaluate the result using === true
     * 
     * @ignore
     * @return boolean
     */
    private function connect() {
        //Determine the DSN prefix and port
        if(!isset($this->dsn_prefix[$this->config->cache_db_type])) {
            return 'Invalid database type: ' . $this->config->cache_db_type;
        }
        else {
            $dsn_prefix = $this->dsn_prefix[$this->config->cache_db_type];
        }

        //Use default port if not specified
        if(is_null($this->config->cache_db_port))   $port = $this->db_ports[$dsn_prefix];
        else                                        $port = $this->config->cache_db_port;

        /**
         * Make sure PDO for our database type is enabled
         * This is done AFTER calculating the dsn_prefix, because the 
         *  dsn_prefix happens to be named the same as the extension we need
         */
        if(!extension_loaded('pdo_' . $dsn_prefix)) {
            return 'pdo_' . $dsn_prefix . ' not enabled/installed!';
        }

        //Try to establish the connection, and store it staticly
        try {
            $this->db = new PDO("$dsn_prefix:host=" . $this->config->cache_db_server . ';dbname=' . $this->config->cache_db_database . ';port=' . $port,
                $this->config->cache_db_username, $this->config->cache_db_password, $this->pdo_options[$dsn_prefix]
            );
        } catch(PDOException $error) {
            return 'Error connecting to the database (' . $error->getMessage() . ')';
        }

        //Success! Now, make sure the table exists, create it if not
        if(!$this->check_table()) {
            if(!$this->create_table()) {
                return $this->db->errorInfo();
            }
        }

        //Success!
        $this->connected = true;
        return true;
    }

    //<editor-fold defaultstate="collapsed" desc="SQL / Schema / Data manipulation methods">
    /**
     * Check to see if our $table exists in the database
     * @todo implement migrations or schema version check etc
     * @ignore
     * @return boolean
     */
    private function check_table() {
        //Table exist?
        if($this->db->query("SELECT COUNT(*) FROM {$this->config->cache_db_table}")) {
            //Update "result" column type to longtext
            return $this->db->exec("
                ALTER TABLE {$this->config->cache_db_table}
                CHANGE `result` `result` longtext NOT NULL
            ") !== false;
        }
        return false;
    }
    /**
     * Attempt to create the table
     * @ignore
     * @return boolean
     */
    private function create_table() {
        return $this->db->exec("
            CREATE TABLE IF NOT EXISTS `{$this->config->cache_db_table}` (
            `id`                int(10)         unsigned NOT NULL AUTO_INCREMENT,
            `service`           varchar(100)    NOT NULL,
            `object_type`       int(4)          unsigned NULL DEFAULT NULL,
            `object_id`         varchar(100)    NULL DEFAULT NULL,
            `result`            longtext        NOT NULL,
            `expires`           datetime        NOT NULL,
            PRIMARY KEY         (`id`),
            UNIQUE KEY          (`service`,`object_type`,`object_id`),
            KEY `expires`       (`expires`),
            KEY `object`        (`object_type`,`object_id`),
            KEY `object_type`   (`object_type`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
        ") !== false;
    }

    /**
     * Used by call() to update an existing record
     *
     * @ignore
     *
     * @param int $id
     * @param int $ttl
     * @param string $result
     * @return boolean
     */
    private function update($id, $result, $ttl) {
        $statement = $this->db->prepare("
            UPDATE {$this->config->cache_db_table}
            SET result = :result, expires = DATE_ADD(UTC_TIMESTAMP(), INTERVAL '$ttl' MINUTE)
            WHERE id = $id
        ");
        return $statement->execute(array(':result' => $result));
    }
    /**
     * Used by call() to create a new cache record
     *
     * @ignore
     *
     * @param string $svc
     * @param int $object_type
     * @param mixed $object_id
     * @param int $ttl
     * @param string $result
     * @return boolean
     */
    private function insert($svc, $object_type, $object_id, $ttl, $result) {
        $statement = $this->db->prepare("
            INSERT INTO {$this->config->cache_db_table}
            (service, object_type, object_id, result, expires)
            VALUES('$svc', $object_type, $object_id, :result, DATE_ADD(UTC_TIMESTAMP(), INTERVAL '$ttl' MINUTE))
        ");
        return $statement->execute(array(':result' => $result));
    }

    /**
     * Build the WHERE clause for our queries, based on the provided
     *  service name, object type, object id, and any combination of
     *
     * Note that the 'WHERE' keyword IS returned
     *
     * @ignore
     *
     * @param string $svc
     * @param int $object_type
     * @param mixed $object_id
     * @return string
     */
    private function build_where($svc = null, $object_type = null, $object_id = null) {
        $where = '';
		//can ben an array, or a single service name
        if(!is_null($svc)) {
			if(is_array($svc)) {
				$where = 'WHERE `service` IN(';
				foreach($svc as $x => $service) $where .= ($x == 0 ? '':', ') . "'$service'";
				$where .= ')';
			}
			else $where .= ($where ? ' AND ' : 'WHERE ') . "`service` = '$svc'";
		}
        if(!is_null($object_type))  $where .= ($where ? ' AND ' : 'WHERE ') . "`object_type` = '$object_type'";
        if(!is_null($object_id))    $where .= ($where ? ' AND ' : 'WHERE ') . "`object_id` = '$object_id'";
        return $where;
    }
    //</editor-fold>

    //<editor-fold defaultstate="collapsed" desc="Cache clearing methods">
    /**
     * As the name indicates, this method will delete any records that have expired, forcing new API calls when requested again
     * @return boolean
     */
    public function clear_expired() {
        return $this->db->exec("
            DELETE FROM {$this->config->cache_db_table}
            WHERE TIMESTAMPDIFF(SECOND, UTC_TIMESTAMP(), expires) <= 0
        ") !== false;
    }
    /**
     * Clears services associated with the provided service name, object type, object id, or any
     *      combination of them (for example all cached result of a certain service associated with any tournament)
     * 
     * If nothing at all was provided, ALL cache will be deleted
     * 
     * @param string|array		$svc			Can be an array of services
     * @param int				$object_type
     * @param string			$object_id
     * @return boolean
     */
    public function clear($svc = null, $object_type = null, $object_id = null) {
        //Build the WHERE query
        $where = $this->build_where($svc, $object_type, $object_id);
		
        //GOGOGO!!!
        return $this->db->exec("
            DELETE FROM {$this->config->cache_db_table} $where
        ") !== false;
    }
    //</editor-fold>

    /**
     * Checks to see if this class has successfully connected and logged in yet
     * @return boolean
     */
    public function connected() {
        return $this->connected;
    }

    /**
     * Can be used in place of $bb->call, this method will check the local
     * cache table for any results from previous identical calls
     * 
     * It does not match arguments, but it matches tourney_id or tourney_team_id with the service
     * 
     * @param string        $svc
     * @param array         $args
     * @param int           $ttl                In minutes, how long to keep the result as valid
     * @param int           $object_type        Tournament, game, etc - use BBCache::TYPE_ constants for values
     * @param int|string    $object_id
     * 
     * @return boolean
     */
    public function call($svc, $args = null, $ttl = null, $object_type = null, $object_id = null) {
        //Build the WHERE clause to try to find a cacheed response in the local database
        $where = $this->build_where($svc, $object_type, $object_id);

        //First step - try to find an already cached response - if expired, remember the ID and we'll update later
        $id = null;
        $result = $this->db->query("
            SELECT id, result, TIMESTAMPDIFF(MINUTE, UTC_TIMESTAMP(), expires) AS minutes_remaining
            FROM {$this->config->cache_db_table}
            $where
        ");

        //Found it! is ist still valid??
        if($result->rowCount() > 0) {
            $row = $result->fetchObject();

            //Success!
            if(intval($row->minutes_remaining) > 0) {
                //Add a value "from_cache" just FYI
                $result = $this->decompress($row->result);
                $result->from_cache = true;
                return $result;
            }
            else $id = $row->id;
        }

        //We don't have a valid cached response, call the API now
        $api_result = $this->bb->call($svc, $args);

        //Compress the result into a string we can save in the database
        $result_compressed = $this->compress($api_result);

        //If null, convert to string 'NULL' for database, otherwise surround with quores
        $object_type    = is_null($object_type) ? 'NULL' : $object_type;
        $object_id      = is_null($object_id)   ? 'NULL' : "'$object_id'";

        //If we have an id, update it now
        if(!is_null($id)) $this->update($id, $result_compressed, $ttl);

        //No existing record, create one now
        else $this->insert($svc, $object_type, $object_id, $ttl, $result_compressed);

        //Return the direct result from $bb
        return $api_result;
    }
}