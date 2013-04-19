<?php
/**
 * Simple example demonstrating how to register and handle a callback
 *
 *
 * @global BBTournament $tournament
 * @global BinaryBeast $bb
 *
 * @filesource
 *
 * @package BinaryBeast
 * @subpackage Examples
 *
 * @version 1.0.0
 * @date    2013-04-13
 * @author  Brandon Simmons <contact@binarybeast.com>
 */

require_once('../__brackets.php');

/**
 * BinaryBeast.com provides a nice callback echo page we can use to very easily test this out
 *
 * http://binarybeast.com/callback/test/ is a simple page that echos a json_encoded string of the values passed to it,
 *  but ONLY if it determines that it was given a valid callback
 *
 * So what we'll do, is register a callback with the new tournament we got from __brackets.php, register
 * the test page from binarybeast, and execute {@link BBCallback::test()}
 *
 * BBCallback::test() will trigger a service available on BinaryBeast that basically triggers a callback execution manually,
 * and even returns the response value from the callback URL - which in our case will be bb.com/callback_test, which echos
 *  the callback data as a json_string
 *
 *
 * So to summarize, we'll register a callback, point it to the callback echo test page on binarybeast, manually trigger a callback test,
 *  and evaluate the response from the callback handler
 */


//Register the callback
$callback_id = $tournament->on_change('http://binarybeast.com/callback/test/', null, false, array('custom_test_argument' => 15));

//Test the callback!
$response = $bb->callback->test($callback_id);

?>
<h3>Raw Response: </h3>
<pre>
    <?php echo $response; ?>

</pre>

<?php

//Now let's convert the response into an object
$data = json_decode($response);

if(!is_null($data)):
    foreach($data as $key => $value):
?>
    <span style="font-weight: 900; padding: 3px 100px 3px 0;"><?php echo $key; ?>:</span><span><?php echo $value; ?></span>
<?php
    endforeach;
endif;