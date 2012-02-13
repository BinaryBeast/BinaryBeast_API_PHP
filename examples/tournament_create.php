<?php

/* @var $bb BinaryBeast */



/**
 * Example BinaryBeast API script
 * 
 * How to create a tournament
 *  
 */


//Require the main class and instantiate with our API Key
require('../BinaryBeast.php');
//This is a valid test key, please use responsibly ;)
$bb = new BinaryBeast('e17d31bfcbedd1c39bcb018c5f0d0fbf.4dcb36f5cc0d74.24632846');

//if you want to use email/password instead, that's fine, just use $bb->login
//$bb->login('APITester@binarybeast.com', 'password');


/**
 *
 * Create the tournament!
 *
 * Only one argument, an associative array of options 
 * 
 * here are the available options:
 *   string $title
 *   string $description
 *   int    $public
 *   string $game_code            	SC2: StarCraft 2, BW: StarCraft BroodWar, QL: Quake Live ... you can search through our games using $bb->game_search('filter'))
 *   int    $type_id              	(0 = elimination brackets, 1 = group rounds to elimination brackets, also the BinaryBeast.php class has constants to help with this)
 *   int    $elimination          	(1 = single, 2 = double
 *   int    $max_teams
 *   int    $team_mode            	(id est 1 = 1v1, 2 = 2v2)
 *   int    $group_count
 *   int    $teams_from_group
 *   date   $date_start                  YYYY-MM-DD HH:SS
 *   string $location                    Simple description of where players should meet to play their matches, or where the event takes place
 *   array  $teams			You may automatically add players, with a simple indexed array of player names
 *   int    $return_data          	(0 = TourneyID and URL only, 1 = List of team id's inserted (from teams array), 2 = team id's and full tourney info dump)
 */

/**
 * You can either look at binarybeast.php or git for a list of options 
 */
$result = $bb->tournament_create(array(
    'title'         => 'PHP (2.7.0) API Test'
    
    //Setup the elimination and type_id using some convenient constants defined in the main BinaryBeast class
    , 'elimination' => BinaryBeast::ELIMINATION_DOUBLE
    , 'type_id'     => BinaryBeast::TOURNEY_TYPE_BRACKETS

    , 'game_code'   => 'SC2'
));

/**
 * EVERY request returns an object, and ALWAYS includes the value 'result'
 * 
 * 51  = This value is returend if PHP is unable to verify the SSL Host
 * 200 = success
 * 403 = not authorized
 * 500 = binarybeast error (I really hope you never see one of these! :D)
 * 
 * 
 * Some services have custom values for result, see the documentation for their meanings
 */
if($result->result != 200)
{
    /**
    * If the result is 51, then SSL Verification failed, we can get around this by disabling the verification
    */
    if($result->result == 51)
    {
        echo '<br /><br /><hr /><b>Warning</b> (BinaryBeastAPI): SSL Verification failed<br />Disabling SSL Verification, but please considering determining the casue before using this in a production environment<br /><br />';

        //Disable the verification
        $bb->disable_ssl_verification();

        //Give it another go
        $result = $bb->tournament_create(array(
            'title'         => 'API Test'
            , 'elimination' => BinaryBeast::ELIMINATION_SINGLE
            , 'type_id'     => BinaryBeast::TOURNEY_TYPE_BRACKETS
        ));   
        if($result->result == 200) echo 'Disabling SSL Verification resolved the issue<br /><br />';
        else
        {
            echo 'Disabling SSL Verification did NOT resolve the issue';
            exit;
        }
    }
    else
    {
        //From here, we can try to figure out what went wrong
        //It's possible that we had an invalid api_key, or some other bad paramaters
        var_dump($result);

        echo '<br /><br /><hr />The service call failed, please contact binarybeast for assistance';

        exit;
    }
}

//Let's get the tourney id, obviously we'll need that later if we want to do anything with it
$tourney_id = $result->tourney_id;
$url = $result->url;

//Let's print out a link to view the tour we just made
echo 'Executed in ' . $result->api_total_time . '<br />';
echo 'Tournament created successfully! <a href="' . $url . '" target="_blank">Click here to view it</a><br /><hr /><br />';

?>