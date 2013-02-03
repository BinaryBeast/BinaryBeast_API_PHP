<?php

/**
 * A very basic class that provides some convenience methods, for helping
 * developers determine tournament-specific values.. such 
 * as calculating the number of rounds in a bracket, or 
 * determining the next power of 2 (like 11 would become 16)
 * 
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 * 
 * @version 1.0.0
 * @date 2013-02-03
 * @author Brandon Simmons
 */
class BBHelper {

    /**
     * Given a bracket integer, return the string description, 
     * for example 1 would return "Winners"
     * 
     * See the constants defined in this class
     * 
     * @param int   $bracket
     * @param bool  $short       For shortened version, convenient for array keys (ie bronze instead of 'Bronze Bracket (3rd place)'
     * @return string
     */
    public function get_bracket_label($bracket, $short = false) {
        //Array of labels that directly relate to the Bracket integer (like 0 = Groups), 
        //Values depend on wether or not $short was requested
        $labels = $short
            ? array('groups', 'winners', 'losers', 'finals', 'bronze')
            : array(
            'Groups',
            'Winners Bracket',
            'Losers Bracket',
            'Finals',
            'Bronze Bracket (3rd place)',
        );

        return $labels[$bracket];
    }

    /**
     * Returns the next power of two given the number of players
     * 
     * IE 31 => 32
     * 
     * @param int $players
     * @return int
     */
    public function get_bracket_size($players) {
        //Reasonable limits
        if($players < 2) return 2;
        if($players > 1024) return 1024;

        //Increment until we hit a power of two (using binary math (AND Operator)
        while($players & ($players - 1)) ++$players;

        return $players;
    }

    /**
     * Given the number of players in a tournament,
     * calculate the number of rounds in the Winners' bracket
     * 
     * @param int $players
     * @return int
     */
    public function get_winner_rounds($players) {
        //Yummy logarithm sexiness!
        return log($players, 2);
    }

    /**
     * Given the number of players in a tournament,
     * calculate the number of rounds in the Losers' bracket
     * 
     * Note that BinaryBeast adds an extra "fake" round for redrawing
     * the winner of a bracket, so the result of this method may not match 
     * remote BinaryBeast values
     * 
     * @param int $players
     * @return int
     */
    public function get_loser_rounds($players) {
        //Basically the number of rounds in the winners bracket, plus another bracket half the size - 1
        return $this->get_winner_rounds($players)
                + $this->get_winner_rounds($players / 2) - 1;
    }

}

?>