<?php

/**
 * The data structure for values returned from the BBCountry services
 * 
 * This class is never used, it solely exists for documentation
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
 *
 * @version 1.0.1
 * @date    2013-04-13
 * @author  Brandon Simmons <contact@binarybeast.com
 */
abstract class BBCountryObject {
    //Nothing here - used for documentation only
}

?>