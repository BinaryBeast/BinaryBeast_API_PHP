<?php


/**
 * The object structure used in {@link BBTournament::rounds()}
 * 
 * This class is never used, it soley exists for documentation
 * 
 * @version 1.0.0
 * @date 2013-04-05
 * @author Brandon Simmons
 *  
 * @package BinaryBeast
 * @subpackage Model_ObjectStructure
 */
abstract class BBRoundObject {
    /**
     * Array of BBRound objects for each round in the group rounds
     * @var BBRound[]
     */
    public $groups;
    /**
     * Array of BBRound objects for each round in the Winners' bracket
     * @var BBRound[]
     */
    public $winners;
    /**
     * Array of BBRound objects for each round in the Losers' bracket
     * @var BBRound[]
     */
    public $losers;
    /**
     * Array of BBRound objects for each round in the grand finals
     * @var BBRound[]
     */
    public $finals;
    /**
     * Array of BBRound objects for each round in the Bronze bracket
     * @var BBRound[]
     */
    public $bronze;
}

?>