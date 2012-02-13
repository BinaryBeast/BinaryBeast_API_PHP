<?php

/* @var $bb BinaryBeast */



/**
 * Example BinaryBeast API script
 * 
 * How to search for a game in our database
 *  
 * This can be an important service to have, as you need to know how we label our game_codes if you want
 * to associate your tournament with a specific game
 */

//Require the main class and instantiate with our API Key
require('../BinaryBeast.php');
$bb = new BinaryBeast('e17d31bfcbedd1c39bcb018c5f0d0fbf.4dcb36f5cc0d74.24632846');


//Let's do a simple search, retrieve a list of games that have the word 'war' in it
$result = $bb->game_search('war');
foreach($result->games as $game)
{
    echo '<img src="', $game->game_icon . '" />', $game->game, ' (game_code: ', $game->game_code, ')<br />';
}

?>