<?php

/**
 * Very simple non-editable class that hosts
 * a few methods for returning / searching through our
 * list of countries
 * 
 * Adding / updating teams or players etc, you have the option 
 * of defining a country_code - you can use this class to find that country_code
 * 
 * Note that on BinaryBeast's end, country_code is the ISO-3 character value, taken directly
 * from wikipedia: http://en.wikipedia.org/wiki/ISO_3166-1_alpha-3
 * 
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 * 
 * @version 1.0.0
 * @date 2013-02-08
 * @author Brandon Simmons
 */
class BBCountry extends BBSimpleModel {
    const SERVICE_SEARCH    = 'Country.CountrySearch.Search';

    /**
     * Returns a list of countries that match the given $filter value
     * 
     * @param string $filter
     */
    public function search($filter) {
        //Don't even bother trying if filter is too short
        if(strlen($filter) < 2) return $this->set_error('"' . $filter . '" is too short, $filter must be at least 2 characters long');

        //GOGOGO!
        return $this->get_list(self::SERVICE_SEARCH, array('country' => $filter), 'countries');
    }
}

?>