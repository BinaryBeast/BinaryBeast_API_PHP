<?php
/**
 * Example script for loading a list of popular tournaments
 *
 * @filesource
 *
 * @package BinaryBeast
 * @subpackage Examples
 *
 * @version     1.0.0
 * @date        2013-05-14
 * @since       2013-05-14
 * @author      Brandon Simmons <contact@binarybeast.com>
 */

$path = str_replace('\\', '/', dirname(__FILE__)) . '/../../../BinaryBeast.php';
require_once($path);
$bb = new BinaryBeast();
$bb->enable_dev_mode();
$bb->disable_ssl_verification();


$tournaments = $bb->tournament->list_popular();

foreach($tournaments as $tournament) {
    echo '<a href="' . $tournament->url . '">' . $tournament->title . '</a><br />';
}