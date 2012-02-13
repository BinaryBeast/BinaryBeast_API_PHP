<?php

/* @var $bb BinaryBeast */



/**
 * Example BinaryBeast API script
 * 
 * We have a full list of ISO countries in our database, you can use our API to find correct country_codes
 */

//Require the main class and instantiate with our API Key
require('../BinaryBeast.php');
$bb = new BinaryBeast('e17d31bfcbedd1c39bcb018c5f0d0fbf.4dcb36f5cc0d74.24632846');


//Let's do a simple search, retrieve a list of countries that have the word 'united' in it
$result = $bb->country_search('united');
foreach($result->countries as $country)
{
    echo $country->country, ' (country_code: ', $country->country_code, ') (country_code_short: ', $country->country_code_short, ')<br />';
}

?>