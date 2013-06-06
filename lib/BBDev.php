<?php

/**
 * Used to dump formatted error messages
 *
 * @package     BinaryBeast
 * @subpackage  Library
 *
 * @version 1.0.2
 * @date    2013-06-06
 * @since   2013-05-14
 * @author  Brandon Simmons <brandon@binarybeast.com>
 */
class BBDev {
    /**
     * Used to make sure we only print the <style> definitions once
     * @var boolean
     */
    private static $style_drawn = false;

    /**
     * When debugging is enabled, we
     *  registered a shutdown callback so that we
     *  can capture fatal errors
     *
     * We use this boolean to make sure it's only registered once
     * @var bool
     */
    private static $registered = false;

    /**
     * Enable development mode
     *
     * Used to register an on_shutdown callback to capture
     *  fatal PHP errors
     *
     * @static
     *
     * @date    2013-05-14
     *
     * @return void
     */
    public static function enable() {
        if(!self::$registered) {
            register_shutdown_function(array('BBDev', 'on_shutdown'));
            self::$registered = true;
        }
    }

    /**
     * Callback invoked when the script ends
     *
     * Allows us to handle fatal PHP errors,
     *  if they're caused by this plugin, we'll
     *  display them with print_error
     *
     * @return void
     */
    public static function on_shutdown() {
        if(!is_null($error = error_get_last())) {
            $error = (object)$error;

            //Only display if we can find BinaryBeast or /BB*.php in the file path
            if(stripos($error->file, 'binarybeast') === false) {
                if(!stripos(str_replace('\\', '/', $error->file), '/BB') === false) {
                    return;
                }
            }

            //Display the error
            self::print_error(null, $error, 'PHP Error', false);
        }
    }

    /**
     * Print <style> css styles and jquery
     *
     * @static
     *
     * @return void
     */
    private static function print_styles() {
        ?>
            <!--
                BinaryBeast
                Styling for debug output
            -->
            <script>
                window.jQuery || document.write('<script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.0/jquery.min.js"><\/script>');
            </script>
            <script>
                /**
                 * Show / Hide trace debug details
                 */
                $(function() {
                    $('a.trace').not('.processed').addClass('processed').click(function() {
                        var $trace = $(this).next();

                        //Show
                        if($trace.hasClass('hidden')) {
                            $(this).html('- Hide');
                            $trace.removeClass('hidden');
                        }
                        //Hide
                        else {
                            $(this).html('+ Show');
                            $trace.addClass('hidden');
                        }
                    });

                    return false;
                });
            </script>
            <style>
                .bb_error {
                    width: 850px;

                    float: left;
                    clear: both;
                    margin: 20px auto;
                    border: 3px solid #F90;
                    border-radius: 5px;

                    background: #DDD;
                    color: #000;
                }

                .bb_error .content {
                    white-space: pre-wrap;
                    word-wrap: break-word;
                    overflow: auto;
                }

                .bb_error h1 {
                    padding: 15px;
                    margin: 0;

                    border-bottom: 2px dashed #333;

                    font-size: 20px;
                    line-height: 20px;
                }
                .bb_error h3 {
                    padding: 10px;
                    margin: 0;
                    border-bottom: 1px solid #000;

                    font-size: 16px;
                    line-height: 16px;
                }
                .bb_error h4 {
                    padding: 10px;
                    margin: 10px 0 0 0;
                    border-bottom: 1px solid #000;

                    font-size: 16px;
                    line-height: 16px;
                }
                .bb_error > .content {
                    display: block;

                    width: 100%;
                    padding: 15px;
                    margin: 0;

                    font-size: 12px;
                    line-height: 14px;
                }

                    .bb_error.fatal h1 {
                        background: #F00;
                        color: #000;
                    }
                    .bb_error.fatal h3 {
                        background: #B20000;
                        color: #000;
                    }
                    .bb_error.fatal > .content {
                        background: #4C0000;
                        color: #FFF;
                    }

                    .bb_error.warning h1 {
                        font-size: 18px;
                        line-height: 18px;
                    }
                    .bb_error.warning h3 {
                        font-size: 14px;
                        line-height: 14px;
                    }
                    .bb_error.warning > .content {
                        font-size: 10px;
                        line-height: 12px;
                    }

            .bb_error .trace {
                display: block;

                margin: 0 5px 10px 5px;
                background: #444;
                color: #FFF;

                border: 1px solid #000;
                border-radius: 2px;
            }
                .bb_error .trace .content {
                    font-size: 11px;
                    line-height: 13px;
                }
                .bb_error a.trace {
                    display: block;
                    height: 11px;
                    padding: 3px;
                    margin: 5px 0 0 0;

                    font-size: 12px;
                    line-height: 12px;

                    text-decoration: none;
                }
                    .bb_error .trace.hidden {
                        display: none;
                    }
            </style>
            <!--
                /BinaryBeast
            -->
        <?php
    }

    /**
     * If dev_mode is enabled, dump the input to the screen
     *
     * @since 2013-05-14
     *
     * @param BinaryBeast|null  $bb
     * @param string|object     $error
     * @param string|null       $title
     * @param boolean|array     $trace
     * @param boolean           $warning
     * @param boolean           $fatal
     *
     * @return void
     */
    public static function print_error($bb, $error, $title = null, $trace = true, $warning = false, $fatal = false) {
        //dev mode disabled
        if(!is_null($bb)) {
            if(!$bb->dev_mode()) {
                return;
            }
        }

        //If not already given a trace array
        if(!is_array($trace)) {
            if($trace === true) {
                $trace = debug_backtrace();
            }
            else $trace = false;
        }

        //Base class, add fatal / warning if appropriate
        $div_class      = 'bb_error';

        //Fatal errors
        if($fatal) {
            $div_class .= ' fatal';
            $h1              = '(FATAL!) BinaryBeast: Fatal Error Encountered';
        }

        //Warnings
        else if($warning) {
            $div_class .= ' warning';
            $h1 = 'BinaryBeast: Warning';
        }

        //Standard non-fatal error
        else {
            $h1 = 'BinaryBeast: Error Encountered';
        }

        //Objects / Arrays: print_r + html entities to make it readable in HTML
        if(is_array($error) || is_object($error)) {
            $content = print_r($error, true);
        }

        //Print directly
        else {
            $content = $error;
        }

        //Dump stylesheet first
        if(!self::$style_drawn) {
            self::print_styles();
        }

        //Extract data from class instances
        $trace = self::parse_item($trace);

        ?>
            <div class="<?php echo $div_class; ?>">
                <h1>
                    <?php echo $h1; ?>
                </h1>
                <?php if($title): ?>
                        <h3>
                            <?php echo $title; ?>
                        </h3>
                <?php endif; ?>
                <pre class="content">
<?php echo $content; ?>
                </pre>
                <?php if(is_array($trace)): ?>
                    <h4>Debug Backtrace</h4>
                    <?php foreach($trace as $trace_item): ?>
                        <?php
                            $trace_content = print_r($trace_item, true);
                        ?>
                        <a href="#" class="trace">+ Show</a>
                        <div class="trace hidden">
                            <pre class="content">
<?php echo $trace_content; ?>
                            </pre>
                        </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        <?php
    }

    /**
     * Recursively parse track data so we can
     *  extract values from class instances
     *
     * @param mixed
     * @return mixed
     */
    public static function parse_item($data) {
        //Objects and Instances
        if(is_object($data)) {
            //Try getting the class name
            if($class = get_class($data)) {
                //If it starts with 'BB', or extends BBSimpleModel / BinaryBeast, extract values only
                if($data instanceof BBSimpleModel || $data instanceof BinaryBeast || (strpos($class, 'BB') === 0) ) {
                    //Init replacement value, and add the class name
                    $tmp = (object)array('class' => $class);
                    foreach($data as $key => $value) {
                        $tmp->{$key} = $value;
                    }
                    return self::parse_item($tmp);
                }
            }

            //Standard object
            foreach($data as $key => $value) {
                $data->{$key} = self::parse_item($value);
            }
        }

        //Arrays
        else if(is_array($data)) {
            foreach($data as $key => $value) {
                $data[$key] = self::parse_item($value);
            }
        }

        return $data;
    }
}