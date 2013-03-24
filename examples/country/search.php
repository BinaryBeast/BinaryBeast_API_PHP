<?php
/**
 * Example demonstrating how to load a list of countries using a filter 
 * 
 * @package BinaryBeast
 * @subpackage Examples
 */

require('../../BinaryBeast.php');
$bb = new BinaryBeast();
$bb->disable_ssl_verification();


$countries = $bb->country->search('united');
foreach($countries as $country) {
    echo $country->country . ' (' . $country->country_code . ')<br />';
}

?>