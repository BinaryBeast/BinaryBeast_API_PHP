<?php

/**
 * Country searching / listing simple model
 * 
 * 
 * The primary use of this class is to set {@link http://binarybeast.com/content/api/docs/php/class-BBTeam.html#m$country_code BBTeam::$country_code} values, to define countires for tournament participants
 * 
 * 
 * <br /><br />
 * The following examples assume <var>$bb</var> is an instance of {@link BinaryBeast}
 * <br />
 * 
 * 
 * ### Example: Search for countries that contain 'united' ###
 * <code>
 *  $countries = $bb->country->search('united');
 *  foreach($countries as $country) {
 *      echo $country->country . ' (' . $country->country_code . ')<br />';
 *  }
 * </code>
 * <b>Result:</b>
 * <pre>
 *  Tanzania, United Republic of (TZA)
 *  United Arab Emirates (ARE)
 *  United Kingdom (GBR)
 *  United States (USA)
 *  United States Minor Outlying Islands (UMI)
 * </pre>
 * 
 * 
 * BinaryBeast's list of countries was populated from the ISO_3166-1 listing: {@link http://en.wikipedia.org/wiki/ISO_3166-1_alpha-3}
 * 
 * @package BinaryBeast
 * @subpackage SimpleModel
 * 
 * @version 3.0.2
 * @date 2013-04-05
 * @author Brandon Simmons <contact@binarybeast.com>
 * @license http://www.opensource.org/licenses/mit-license.php
 * @license http://www.gnu.org/licenses/gpl.html
 */
class BBCountry extends BBSimpleModel {
    /**
     * Service name used to search for a list of countries
     * @var string
     */
    const SERVICE_SEARCH    = 'Country.CountrySearch.Search';

    /**
     * Results are cached for a week, it is highly unlikely that 
     *  the results will EVER change
     * @var int
     */
    const CACHE_TTL_LIST        = 10080;
    const CACHE_OBJECT_TYPE     = BBCache::TYPE_COUNTRY;

    /**
     * Returns a list of countries that match the given $filter value
     * 
     * @param string $filter
     * @return BBCountryObject[]
     */
    public function search($filter) {
        //Don't even bother trying if filter is too short
        if(strlen($filter) < 2) return $this->set_error('"' . $filter . '" is too short, $filter must be at least 2 characters long');

        //GOGOGO!
        return $this->get_list(self::SERVICE_SEARCH, array('country' => $filter), 'countries');
    }
}

?>