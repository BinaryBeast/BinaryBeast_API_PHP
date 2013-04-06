<?php
/**
 * Simple example demonstrating how to manually load group rounds
 * 
 * @filesource
 * 
 * @version 1.0.0
 * @date 2013-04-05
 * @author Brandon Simmons
 * 
 * @package BinaryBeast
 * @subpackage Examples
 */

require('../../../BinaryBeast.php');
$bb = new BinaryBeast();
$bb->disable_ssl_verification();

/*
 * First - create a tournament with groups
 */
$tournament = $bb->tournament();
$tournament->title              = 'API Demo - Loading Groups';
$tournament->description        = 'Simple API PHP Library demonstrating how to fetch raw group-rounds data';
$tournament->elimination        = BinaryBeast::ELIMINATION_DOUBLE;
$tournament->type_id            = BinaryBeast::TOURNEY_TYPE_CUP;
//Only need 1 group
$tournament->group_count        = 1;
$tournament->teams_from_group   = 2;
//Group of 6
for($x = 0; $x < 6; $x++) {
    $team = $tournament->team();
    $team->confirm();
    $team->display_name = 'Demo Player ' . ($x + 1);
}
//
if(!$tournament->save()) {
    var_dump(array('Error saving tournament', 'errors' => $bb->error_history));
    die();
}
//Start groups
if(!$tournament->start()) {
    var_dump(array('Error starting the groups', 'errors' => $bb->error_history));
    die();
}

?>

<h1>Group A</h1>
<?php
foreach($tournament->groups->a as $round => $matches) {
    echo '<h3>Round ' . ($round + 1) . '</h3>';

    foreach($matches as $i => $match) {
        echo '<h4>Match ' . ($i + 1) . '</h4>';

        /* @var $match BBMatchObject */
        if(!is_null($match->team)) {
            echo $match->team->display_name;

            //Waiting on an opponent
            if(is_null($match->opponent)) {
                echo ' - Waiting on an Opponent';
            }
            else {
                echo ' vs. ' . $match->opponent->display_name;

                //Print the winner name
                if(!is_null($match->match)) {
                    if(!is_null($match->match->id)) {
                        echo ' (Winner: ' . $match->match->winner->display_name . ')';
                    }
                }

            }
        }
        //Waiting on an opponent
        else if(!is_null($match->opponent)) {
                echo $match->opponent->display_name . ' - Waiting on an Opponent <br />';
        }
    }
}
?>

<?php
    //Display option of deleting the example tournament
    require('../delete/delete.php');
?>