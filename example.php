<?php
/**
* I wrote this script in order to demonstrate basic use of the BinaryBeast PHP class written to interact with BinaryBeast's API
*
* For more detailed documentation, please @see http://wiki.binarybeast.com/index.php?title=BinaryBeast_API
*
* For any concerns, feel free to send us an email: contact@binarybeast.com
*/





/**
* Wrapper methods quick reference list
* 
* @see @link http://wiki.binarybeast.com/index.php?title=API_PHP
* 
* tournament_create($title, $description, $public, $game_code, $type_id, $elimination, $max_teams, $team_mode, $group_count, $teams_from_group, $date_start, $location, $teams, $return_data)
* tournament_update($tourney_id, $title, $description, $public, $game_code, $type_id, $elimination, $max_teams, $team_mode, $group_count, $teams_from_group, $date_start, $location)
* tournament_delete($tourney_id)
* tournament_start($tourney_id, $seeding = 'random', $teams = null)
* 
* team_insert($tourney_id, $display_name, $country_code = null, $status = 1, $notes = null, $players = null, $network_name = null)
* team_delete($tourney_team_id)
* team_report_win($tourney_id, $tourney_team_id, $o_tourney_team_id = null, $score = 1, $o_score = 0, $replay = null, $map = null, $notes = null, $force = false)
* team_get_opponent($tourney_team_id)
* 
* game_search($filter)
* game_list_top($limit)
* 
* country_search($filter)
*/

//Get the BinaryBeast API interface written in PHP
require('BinaryBeast.php');


/**
*
* First, let's create an api class instance, and log in
*
* Here, I use a real APIKey that you are welcomed to use, but DO NOT USE IT FOR A PRODUCTION ENVIRONMENT
* it is for testing purposes only, I only included it for testing convenience
* 
* Alternatively, you can use the login method to use simple email / password authentication
*
*/
//@see @link http://wiki.binarybeast.com/index.php?title=API_PHP
$bb = new BinaryBeast('e17d31bfcbedd1c39bcb018c5f0d0fbf.4dcb36f5cc0d74.24632846');

//Alternatively, if you don't want to use the API Key (for whatever reason...) you could se the login method to use a simple email / password combination
//$bb->login('APITester@binarybeast.com', 'password');

/**
*
* 
* Create the tournament!
* 
*
*/

//@see @link http://wiki.binarybeast.com/index.php?title=API_PHP:_tournament_create
$result = $bb->tournament_create(array(
    'title'         => 'API Test'
    , 'elimination' => BinaryBeast::ELIMINATION_SINGLE
    , 'type_id'     => BinaryBeast::TOURNEY_TYPE_BRACKETS
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
echo 'Tournament created successfully! <a href="' . $url . '">Click here to view it</a><br /><hr /><br />';

/**
* 
* 
* Update / Change a tournament
* 
* 
*/
//Let's say we've changed our minds, and want double elimination instead of single... EZ, EZ:
$bb->tournament_update($tourney_id, array(
'elimination' => BinaryBeast::ELIMINATION_DOUBLE
));

/**
* 
* 
* Setting up round format
* 
*/
$bb->tournament_round_update($tourney_id, BinaryBeast::BRACKET_WINNERS, 0, 1, 'Shakuras Plateau');
$bb->tournament_round_update($tourney_id, BinaryBeast::BRACKET_WINNERS, 1, 3, 'Metalopolis');
$bb->tournament_round_update($tourney_id, BinaryBeast::BRACKET_WINNERS, 2, 5, "Xel'Naga Caverns");

//Now.. that works fine, but it's a lot of extra unecessary calls to the API, let's use the batch updater to get it all in one call for the loser brackets
$best_ofs = array(1, 1, 3, 5);
$maps     = array("Tal'Darim Altar", 'The Shattered Temple', "Xel'Naga Caverns", 'Metalapolis');
$dates    = array();
$bb->tournament_round_update_batch($tourney_id, BinaryBeast::BRACKET_LOSERS, $best_ofs, $maps, $dates);

//And lastly, let's update the finals format
//WHen you view the tournament, notice that it automatically corrects your invalid BO6 to BO7, as a BO series MUST NOT be evently divisible by 2
$bb->tournament_round_update($tourney_id, BinaryBeast::BRACKET_FINALS, 0, 6, 'Metalopolis');

/**
*
* Adding teams
*
* I created an array here, to simply make it easier to loop through and create them all at once
*
*/
$teams = array('Team 1', '第2组', 'الفريق 3', 'צוות 4', 'Команда 5');

//Init an array to store the returned team id's
$team_ids = array();

//We'll just add them in a loop
foreach($teams as $team)
{
    //@see @link http://wiki.binarybeast.com/index.php?title=API_PHP:_team_insert
    $result = $bb->team_insert($tourney_id, $team);

    //Let's store an array of inserted teams, so we can match up
    //team id's with the actual names later on
    $team_ids[ $result->tourney_team_id ] = $team;

    echo $team . ' added, his tourney_team_id is: ' . $result->tourney_team_id . '<br />';
}

//Let's move along, add a bit of seperation for the next test
echo '<br /><hr /><br />';

//Let's grab the last team's TourneyTeamID (BinaryBeast side team id)
//We need it so we can give him a win later
$tourney_team_id = $result->tourney_team_id;

/**
* 
* Check-in system
* 
*/
//In the above example, our teams were automatically confirmed, and will therefore be included in the draw
//However, let's say that we'd like to build a check-in (bb calls it confirmation) system

//First step, is to add a team, but DONT auto-confirm him
$result = $bb->team_insert($tourney_id, 'check-in user', array(
    'status'        => 0,    //Status of 0 means that he is NOT confirmed - now that I think about it.. maybe I should added a few constants to BinaryBeast.. might make it a bit easier.. like BinaryBeast::STATUS_CONFIRMED = 1;
    'country_code' => 'NOR' //and he may have a specific country flag as well, let's say he's from Norway
));

//Some point in the future, you open confirmations, and he confirms... here is what what we need to do to update the team on BinaryBeast's end
$bb->team_confirm($result->tourney_team_id);

//Success! the player will now be drawn into  the tournament

//Other important functions to note:
//$bb->team_unconfirm($tourney_team_id);
//$bb->team_ban($tourney_team_id);

/**
*
* Changing team settings
*
*/
//Say you need to change this team's country and maybe his in-game name... we can do that easily through team_update
$bb->team_update($tourney_team_id, array(
    'country_code' => 'JPN'                     //he moved to japan 
    , 'network_display_name' => 'newName.012' //and he changed his battle.net name / character code
));
//Meh, pretty sure it'll work heh, not going to even bother evaluating the result

/**
*
* 
* Start the brackets
* 
*
*/
//@see @link http://wiki.binarybeast.com/index.php?title=API_PHP:_tournament_start
$result = $bb->tournament_start($tourney_id);

if($result->result != 200)
{
    //Try to figure out what went wrong
    var_dump($result);

    die('<br /><br /><hr />Tournament was not started successfully!!');
}
else echo 'Tournament brackets have been started!<br /><hr /><br />';

/**
*
* Remember we saved the TeamID of the last team we inserted (Team 4)
* Now, let's see who his current opponent is
*
* 
* Possible values for 'result'
*      200: The team currently has an oppponent
*      734: The team does not currently have an opponent, he's waiting on another match to finish
*      735: The team has been eliminated
*/
//@see @link http://wiki.binarybeast.com/index.php?title=API_PHP:_team_get_opponent
$result = $bb->team_get_opponent($tourney_team_id);

/**
* This method returns a few different possible values for result, and from there we can tell the status of this team
*/
switch($result->result)
{
    //We found an opponent!
    case 200:
        //Since we stored the team id's inserted in $team_id's, we can now use that to figure out team 4's current opponent
        $opponent_id = $result->o_tourney_team_id;
        $opponent = $team_ids[ $opponent_id ];

        echo 'Team 4\'s current opponent is: ' . $opponent;

        break;

    //He does not yet have an opponent
    case 734:
        echo 'Team 4 is waiting for another match to finish, he does not have an opponent right now';
        break;

    //He was eliminated!
    //Of course, in this case, it's impossible lol, since we haven't reported any wins yet
    case 735:
        //And from here, we can find out who eliminated him, by taking a look at the returned value 'victor'
        $winner = $team_ids[ $result->victor->tourney_team_id ];

        echo 'Team 4 sucks, he was knocked out somehow.... lol wtf, ', $winner, ' beat him??';

        break;
}

//On to the next test, let's have some space
echo '<br /><br /><hr />';


/**
*
* Now, let's give Team4 a win!
*
* Let's say it's a bo3 and he won 2-1
*
* We won't bother trying to report it if he doesn't have an opponent
*
*/
if($opponent_id)
{
    //@see @link http://wiki.binarybeast.com/index.php?title=API_PHP:_team_report_win
    $result = $bb->team_report_win($tourney_id, $tourney_team_id, null, 2, 1);

    //Should be fairly straight forward...
    if($result->result == 200)
    {
        echo 'Team 4 defeated ', $opponent, '!';
    }
    else
    {
        var_dump($result);

        die('Uh oh, something went wrong while trying to report a win for Team4!');
    }
}

echo '<br /><br /><hr />';


/**
* 
* Now let's display a short list of the most popular games in BinaryBeast's database
* 
*/
//@see @link http://wiki.binarybeast.com/index.php?title=API_PHP:_game_list_top
$result = $bb->game_list_top(10);
foreach($result->games as $game)
{
    echo '<a href="http://binarybeast.com/game/load/', $game->game_code, '"><img src="', $game->game_icon, '" /> ', $game->game , ' (game_code: ', $game->game_code, ')<a></br />';
}


/**
*
* That's a rather simple example of what the API can do... if you have any questions at all,
* Throw an email our way (contact@binarybeast.com), give us a call(Skype: BinaryBeast), or
* find us on the website!
*
* Thanks for your interest in the BinaryBeast API, I hope it works well for you
*
*/
?>