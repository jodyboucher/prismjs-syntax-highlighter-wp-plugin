<?php
/**
 * Prism.js Syntax Highlighter WordPress plugin: utility routines
 *
 * @package   JodyBoucher\Wordpress\PrismjsSyntaxHighlighter
 * @author    Jody Boucher <jody@jodyboucher.com>
 * @license   GPL2
 * @copyright 2016 Jody Boucher
 */

namespace JodyBoucher\Wordpress\PrismjsSyntaxHighlighter;

if ( ! function_exists( 'debug_log' ) ) {
	/**
	 * Write a message to the PHP system log.
	 *
	 * @param string|array|object $message The message to write to log.
	 */
	function debug_log( $message ) {
		if ( WP_DEBUG === true ) {
			// Get the name of the calling function.
			$trace = debug_backtrace();
			$name  = $trace[1]['function'];

			// Write out the message.
			if ( is_array( $message ) || is_object( $message ) ) {
				error_log( '[' . $name . '] ' );
				error_log( wp_json_encode( $message ) );
			} else {
				// Prefix the message with the name of the calling function.
				$message = '[' . $name . '] ' . $message;

				error_log( $message );
			}
		}
	}
}

if ( ! class_exists( 'ArrayHelper' ) ) {
	/**
	 * Class ArrayHelper
	 *
	 * Some basic array helper routines
	 */
	class ArrayHelper {
		/**
		 * Checks if the given key or index exists in the array
		 *
		 * @param mixed $key   Value to check.
		 * @param array $array An array with keys to check.
		 *
		 * @return bool
		 */
		public static function key_exists( $key, $array ) {
			return isset( $array[ $key ] ) || array_key_exists( $key, $array );
		}

		/**
		 * Gets the value of the given key in the array, or default if key does not exist
		 *
		 * @param mixed $key     The key of the value to obtain.
		 * @param array $array   The array to obtain value form.
		 * @param mixed $default The value to obtain if key does not exist.
		 *
		 * @return mixed The value associated with key, otherwise default.
		 */
		public static function get_value_or_default( $key, $array, $default ) {
			return self::key_exists( $key, $array ) ? $array[ $key ] : $default;
		}
	}
}
