<?php

/**
 * Country searching / listing simple model
 * 
 * 
 * You'll need this class to find country_codes For {@link BBTeam::country_code}
 * 
 * 
 * ### Example use
 * 
 * The following examples assume <var>$bb</var> is an instance of {@link BinaryBeast}
 * 
 * <b>Example - list all countries containing the word 'united'</b>
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
 * @version 3.0.0
 * @date 2013-03-17
 * @author Brandon Simmons <contact@binarybeast.com>
 * @license http://www.opensource.org/licenses/mit-license.php
 * @license http://www.gnu.org/licenses/gpl.html
 */
class BBCountry extends BBSimpleModel {
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

/**
 * The data structure for values returned from the BBCountry services
 * 
 * This class is never used, it soley exists for documentation
 * 
 * @property-read string $country
 *  The country name
 * 
 * @property-read string $country_code
 * <b>3 characters (ISO 3166-1 alpha-3)</b><br />
 * This is the value BinaryBest uses to identify a team<br />
 * Notably used when creating teams: {@link BBTeam::country_code}<br />
 * Source: {@link http://en.wikipedia.org/wiki/ISO_3166-1_alpha-3}<br />
 * 
 * @property-read string $country_code_short
 * <b>32 characters (ISO 3166-1 alpha-2)</b><br />
 * The shorter 2-character country identifier<br />
 * Wikipedia: {@link http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2}<br />
 * 
 * 
 * @package BinaryBeast
 * @subpackage SimpleModel_ObjectStructure
 */
abstract class BBCountryObject {
    //Nothing here - used for documentation only
}

?>