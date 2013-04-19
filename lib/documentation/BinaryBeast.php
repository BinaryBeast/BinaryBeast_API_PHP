<?php

/**
 * The data structure for basic API response values
 * 
 * This class is never used, it solely exists for documentation
 * 
 * @property-read int $result
 *  Basic result code<br />
 *  See {@link BinaryBeast::last_friendly_result} for a translation<br />
 *  You can also use {@link BBHelper::translate_result()} to translate a result code
 *
 * @property-read object[] $list
 *  For certain services, an array of object data may be returned, and is most often defined as 'list'
 *
 * @property-read object $data
 *  For certain services, an object of data may be returned, and is often defined as 'data'
 *
 * @package BinaryBeast
 * @subpackage Library_ObjectStructure
 */
abstract class BBResultObject {
    //Nothing here - used for documentation only
}

?>