<?php
if (!function_exists('debug_log')) {
    function debug_log($message)
    {
        if (WP_DEBUG === true) {
            // Get the name of the calling function
            $trace = debug_backtrace();
            $name = $trace[1]['function'];

            // write out the message
            if (is_array($message) || is_object($message)) {
                error_log('[' . $name . '] ');
                error_log(print_r($message, true));
            } else {
                // prefix the message with the name of the calling function
                $message = '[' . $name . '] ' . $message;

                error_log($message);
            }
        }
    }
}

if (!class_exists('ArrayHelper')) {
    class ArrayHelper
    {
        public static function KeyExists($array, $key)
        {
            return isset($array[$key]) || array_key_exists($key, $array);
        }

        public static function GetValueOrDefault($array, $key, $default)
        {
            return self::KeyExists($array, $key) ? $array[$key] : $default;
        }

    }
}
