<?php

try
{
    require_once dirname(__FILE__) . '/vendor/autoload.php';
    if(!function_exists('wp_logger'))
    {
        function wp_logger($message = null, $context = null)
        {
            if(!empty($message))
            {
                return \DigitaleHelden\Logger\Logger::logger()->info($message, $context);
            } else {
                return \DigitaleHelden\Logger\Logger::logger();
            }
        }
    }
}
catch(\Exception $e){}