<?php
/**
 * Prism.js Syntax Highlighter WordPress plugin
 *
 * The Prism.js Syntax Highlighter WordPress plugin is a lightweight
 * plugin that integrates the Prism.js syntax highlighter into WordPress.
 *
 * @author      Jody Boucher <jody@jodyboucher.com>
 * @version     1.2.0
 * @license     GPL-2.0+
 * @copyright   2016-2017 Jody Boucher
 *
 *
 * Plugin Name: Prism.js Syntax Highlighter
 * Plugin URI:  https://github.com/jodyboucher/wp-plugin-prismjs-syntax-highlighter
 * Description: Lightweight plugin that integrates the Prism.js syntax highlighter into WordPress
 * Version:     1.2.0
 * Author:      Jody Boucher
 * Author URI:  https://jodyboucher.com
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * --------------------------------------------------------------------------------
 * Prism.js Syntax Highlighter WordPress plugin
 * Copyright (C) 2016-2017  Jody Boucher
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 * -------------------------------------------------------------------------------- */

namespace JodyBoucher\WordPress\Plugins\PrismJsSyntaxHighlighter;

// Exit if accessed directly!
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require( 'util.php' );

/**
 * Class Prismjs_Syntax_Highlighter
 * Implements 'Prism.js Syntax Highlighter' WordPress plugin
 */
class Prismjs_Syntax_Highlighter {
	const VERSION = '1.0.0';

	const SETTINGS_GROUP = 'prismjs-settings';

	const OPTION_DEFAULT_LANGUAGE = 'default_language';
	const OPTION_DEFAULT_INLINE = 'default_inline';
	const OPTION_DEFAULT_LINE_NUMBERS = 'default_show_line_numbers';
	const OPTION_CUSTOM_CSS = 'custom_css';
	const OPTION_CUSTOM_JS = 'custom_js';
	const OPTION_SHOW_CSS_WARNING_NOTICE = 'notice_theme_css_warning';

	/**
	 * Reference to instance of Prismjs_Syntax_Highlighter
	 *
	 * @var Prismjs_Syntax_Highlighter|null
	 */
	private static $instance = null;

	/**
	 * Indicated if content contains code.
	 * true === content contains code, otherwise false
	 *
	 * @var bool
	 */
	private $has_code = false;

	/**
	 * Indicates if content has been checked for code.
	 * true === content has been checked, otherwise false
	 *
	 * @var bool
	 */
	private $has_been_code_checked = false;

	/**
	 * Sets up a new Prismjs_Syntax_Highlighter instance.
	 */
	public function __construct() {
		debug_log( 'function start' );

		if ( is_admin() ) {
			register_activation_hook(
				__FILE__,
				array(
					'JodyBoucher\WordPress\Plugins\PrismJsSyntaxHighlighter\Prismjs_Syntax_Highlighter',
					'on_activation',
				)
			);

			// Admin hooks.
			add_action( 'admin_init', array( $this, 'register_admin_init' ) );
			add_action( 'admin_notices', array( $this, 'register_admin_notices' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_scripts' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'register_action_links' ) );
			add_action( 'admin_menu', array( $this, 'register_options_page' ) );
		}

		if ( ! is_admin() ) {
			// Posts hooks.
			add_action( 'wp_enqueue_scripts', array( $this, 'register_styles' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );
		}
		debug_log( 'function end' );
	}

	/**
	 * Returns this instance of Prismjs_Syntax_Highlighter
	 *
	 * @return Prismjs_Syntax_Highlighter|null
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Contains plugin activation logic.
	 */
	public static function on_activation() {
		debug_log( 'function start' );

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		// Set default options values.
		$options = get_option( self::SETTINGS_GROUP );
		if ( ! $options ) {
			$options = array();
		}

		if ( ! isset( $options[ self::OPTION_DEFAULT_LANGUAGE ] ) ) {
			$options[ self::OPTION_DEFAULT_LANGUAGE ] = 'markup';
		}

		if ( ! isset( $options[ self::OPTION_DEFAULT_INLINE ] ) ) {
			$options[ self::OPTION_DEFAULT_INLINE ] = '';
		}

		if ( ! isset( $options[ self::OPTION_DEFAULT_LINE_NUMBERS ] ) ) {
			$options[ self::OPTION_DEFAULT_LINE_NUMBERS ] = '1';
		}

		if ( ! isset( $options[ self::OPTION_CUSTOM_CSS ] ) ) {
			$options[ self::OPTION_CUSTOM_CSS ] = '';
		}

		if ( ! isset( $options[ self::OPTION_CUSTOM_JS ] ) ) {
			$options[ self::OPTION_CUSTOM_JS ] = '';
		}

		if ( ! isset( $options[ self::OPTION_SHOW_CSS_WARNING_NOTICE ] ) ) {
			$options[ self::OPTION_SHOW_CSS_WARNING_NOTICE ] = false;
		}

		update_option( self::SETTINGS_GROUP, $options );

		// Check if the current theme modifies <pre> or <code> styles
		// if it does, notify the user.
		$theme_css_contents = file_get_contents( get_stylesheet_directory() . '/style.css' );

		if ( preg_match( '/(pre|code) ?(,|\{)/', $theme_css_contents ) ) {
			$options[ self::OPTION_SHOW_CSS_WARNING_NOTICE ] = true;
			update_option( self::SETTINGS_GROUP, $options );
		}

		debug_log( 'function end' );
	}

	/*
	 * ------------------------------------------------------------------------
	 * Hook Callbacks
	 * ------------------------------------------------------------------------
	 */

	/**
	 * Register the plugin links.
	 * This function is registered with the 'plugin_action_links' filter hook.
	 *
	 * @param array $links The array of links to display on the plugins page.
	 *
	 * @return array The updated array of plugin links.
	 */
	public function register_action_links( $links ) {
		debug_log( 'function start/end' );

		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'options-general.php?page=prismjs-syntax-highlighter' ) . '">Settings</a>',
			),
			$links
		);
	}

	/**
	 * Register settings.
	 * This function is registered with the 'admin_init' action hook.
	 */
	function register_admin_init() {
		debug_log( 'function start' );

		global $typenow;

		// Check user permissions.
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
			debug_log( '-- bad permissions' );

			return;
		}

		// Verify the post type.
		if ( ! isset( $typenow ) && ! in_array( $typenow, array( 'post', 'page' ), true ) ) {
			debug_log( '-- not post' );

			return;
		}

		// Check if WYSIWYG is enabled.
		if ( get_user_option( 'rich_editing' ) === 'true' ) {
			debug_log( '-- WYSIWYG enabled' );

			// Register TinyMCE filter hooks.
			add_filter( 'mce_buttons', array( $this, 'register_mce_button' ) );
			add_filter( 'mce_external_plugins', array( $this, 'register_mce_plugin' ) );

			// Configure settings.
			$menu_page = 'prismjs-settings-admin';
			$section   = 'prismjs_main_section';

			register_setting( 'prismjs-settings-group', self::SETTINGS_GROUP, array( $this, 'validate_settings' ) );
			add_settings_section( $section, 'Main Settings', null, $menu_page );

			add_settings_field(
				self::OPTION_DEFAULT_LANGUAGE,
				'Default language:',
				array( $this, 'setting_default_language' ),
				$menu_page,
				$section
			);
			add_settings_field(
				self::OPTION_DEFAULT_INLINE,
				'Default highlighting to inline:',
				array( $this, 'setting_default_inline' ),
				$menu_page,
				$section
			);
			add_settings_field(
				self::OPTION_DEFAULT_LINE_NUMBERS,
				'Show line numbers by default:',
				array( $this, 'setting_default_line_number' ),
				$menu_page,
				$section
			);
			add_settings_field(
				self::OPTION_CUSTOM_CSS,
				'Custom Prism CSS filename:',
				array( $this, 'setting_custom_css' ),
				$menu_page,
				$section
			);
			add_settings_field(
				self::OPTION_CUSTOM_JS,
				'Custom Prism JS filename:',
				array( $this, 'setting_custom_js' ),
				$menu_page,
				$section
			);
			add_settings_field(
				self::OPTION_SHOW_CSS_WARNING_NOTICE,
				'Show CSS warning notice:',
				array( $this, 'setting_show_css_warning_notice' ),
				$menu_page,
				$section
			);
		}

		debug_log( 'function end' );
	}

	/**
	 * Register and admin notices.
	 * This function is registered with the 'admin_notices' action hook.
	 */
	public function register_admin_notices() {
		debug_log( 'function start' );

		$options      = get_option( self::SETTINGS_GROUP );
		$setting_name = self::SETTINGS_GROUP . '[' . self::OPTION_SHOW_CSS_WARNING_NOTICE . ']';
		if ( isset( $options[ self::OPTION_SHOW_CSS_WARNING_NOTICE ] ) && '1' === $options[ self::OPTION_SHOW_CSS_WARNING_NOTICE ] ) {
			?>
			<div class="updated">
				<h3>Prism.js Syntax Highlighter</h3>
				<p>
					The current theme modifies &lt;pre&gt; and/or &lt;code&gt; styles. These modifications could
					interfere with the display and appearance of Prism.js styled content.
				</p>
				<p>
					<strong>Please <a href="<?php echo esc_url( admin_url() . 'theme-editor.php' ); ?>">edit the
							theme</a> and comment out or remove the relevant lines.</strong>
				</p>
				<form method="post" action="options.php">
					<?php settings_fields( 'prismjs-settings-group' ); ?>

					<input type="hidden" id="<?php echo esc_attr( self::OPTION_SHOW_CSS_WARNING_NOTICE ); ?>"
					       name="<?php echo esc_attr( $setting_name ); ?>" value="0"/>

					<?php submit_button( 'Hide this warning' ); ?>
				</form>
			</div>
			<?php
		}

		debug_log( 'function end' );
	}

	/**
	 * Register and enqueue admin scripts.
	 * This function is registered with the 'admin_enqueue_scripts' action hook.
	 */
	public function register_admin_scripts() {
		debug_log( 'function start' );

		$options = get_option( self::SETTINGS_GROUP );
		if ( ! $options ) {
			$options = array();
		}

		$language     = ArrayHelper::get_value_or_default( self::OPTION_DEFAULT_LANGUAGE, $options, '' );
		$in_line      = ArrayHelper::get_value_or_default( self::OPTION_DEFAULT_INLINE, $options, '' );
		$line_numbers = ArrayHelper::get_value_or_default( self::OPTION_DEFAULT_LINE_NUMBERS, $options, '' );
		echo
			'<script type="text/javascript">
				var defaultLanguage = "' . esc_js( $language ) . '";
				var defaultInline = ' . ( esc_js( $in_line ) === '1' ? 'true' : 'false' ) . ';
				var defaultLineNumbers = ' . ( esc_js( $line_numbers ) === '1' ? 'true' : 'false' ) . ';
			</script>';

		$js_file    = 'prism.js';
		$use_custom = '' !== ArrayHelper::get_value_or_default( self::OPTION_CUSTOM_JS, $options, '' );
		if ( true === $use_custom ) {
			$js_file = esc_attr( $options[ self::OPTION_CUSTOM_JS ] );
		}

		wp_register_script(
			'PrismJsSyntaxHighlighter',
			plugins_url( 'js/' . $js_file, __FILE__ ),
			null,
			self::VERSION,
			true
		);
		wp_enqueue_script( 'PrismJsSyntaxHighlighter' );

		debug_log( 'function end' );
	}

	/**
	 * Register the name of the new button for the TinyMCE editor.
	 * This function is registered with the 'mce_buttons' filter hook.
	 *
	 * @param array $buttons The array of button names to add the new button to.
	 *
	 * @return array The updated array of button names.
	 */
	public function register_mce_button( $buttons ) {
		debug_log( 'function start' );

		array_push( $buttons, 'prismjs' );

		debug_log( 'function end' );

		return $buttons;
	}

	/**
	 * Register the plugin for the TinyMCE editor.
	 * This function is registered with the 'mce_external_plugins' filter hook.
	 *
	 * @param array $plugins Associative array of plugins to add the new plugin to.
	 *
	 * @return array The updated array of plugins.
	 */
	public function register_mce_plugin( $plugins ) {
		debug_log( 'function start' );

		$plugins['prismjs'] = plugins_url( 'js/prismjs-tinymce-plugin.js', __FILE__ );

		debug_log( 'function end' );

		return $plugins;
	}

	/**
	 * Register the options page.
	 * This function is registered with the 'admin_menu' action hook.
	 */
	function register_options_page() {
		debug_log( 'function start' );

		add_options_page(
			'Prism.js Syntax Highlighter',
			'Prism.js',
			'manage_options',
			'prismjs-syntax-highlighter',
			array( $this, 'options_page' )
		);

		debug_log( 'function end' );
	}

	/**
	 * Register and enqueue scripts.
	 * This function is registered with the 'wp_enqueue_scripts' action hook.
	 */
	public function register_scripts() {
		debug_log( 'function start' );

		if ( $this->check_for_code() ) {
			$options = get_option( self::SETTINGS_GROUP );

			debug_log( '--- custom js: ' . $options[ self::OPTION_CUSTOM_JS ] );
			$js_file    = 'prism.js';
			$use_custom = '' !== $options[ self::OPTION_CUSTOM_JS ];
			if ( true === $use_custom ) {
				$js_file = esc_attr( $options[ self::OPTION_CUSTOM_JS ] );
			}

			wp_register_script(
				'prismjs-syntax-highlighter-js',
				plugins_url( 'js/' . $js_file, __FILE__ ),
				null,
				self::VERSION,
				true
			);
			wp_enqueue_script( 'prismjs-syntax-highlighter-js' );
		}

		debug_log( 'function end' );
	}

	/**
	 * Check the post content for <code> tags.
	 */
	private function check_for_code() {
		debug_log( 'function start' );

		if ( ! $this->has_been_code_checked ) {
			debug_log( '-- check for post content' );
			$post = get_post();
			if ( $post && stripos( $post->post_content, '<code' ) !== false ) {
				debug_log( '-- found code' );
				$this->has_code = true;
			} else {
				debug_log( '-- post not found, assume multiple' );
				$this->has_code = true;
			}

			$this->has_been_code_checked = true;
		}

		debug_log( 'function end' );

		return $this->has_code;
	}

	/**
	 * Register and enqueue styles.
	 * This function is registered with the 'wp_enqueue_scripts' action hook.
	 */
	public function register_styles() {
		debug_log( 'function start' );

		if ( $this->check_for_code() ) {
			$options    = get_option( self::SETTINGS_GROUP );
			$css_file   = 'prism.css';
			$use_custom = '' !== $options[ self::OPTION_CUSTOM_CSS ];
			if ( true === $use_custom ) {
				$css_file = esc_attr( $options[ self::OPTION_CUSTOM_CSS ] );
			}

			wp_register_style(
				'prismjs-syntax-highlighter-css',
				plugins_url( 'css/' . $css_file, __FILE__ ),
				null,
				self::VERSION
			);
			wp_enqueue_style( 'prismjs-syntax-highlighter-css' );
		}

		debug_log( 'function end' );
	}

	/*
	 * ------------------------------------------------------------------------
	 * Settings Callbacks
	 * ------------------------------------------------------------------------
	 */

	/**
	 * Outputs the HTML for the custom_css setting to the settings page.
	 */
	public function setting_custom_css() {
		debug_log( 'function start' );

		// Get the current options values.
		$options      = get_option( self::SETTINGS_GROUP );
		$option_value = $options[ self::OPTION_CUSTOM_CSS ];
		debug_log( 'current value of ' . self::OPTION_CUSTOM_CSS . ': ' . $option_value );

		$setting_name = self::SETTINGS_GROUP . '[' . self::OPTION_CUSTOM_CSS . ']';

		?>
		<input type="text"
		       id="<?php echo esc_attr( self::OPTION_CUSTOM_CSS ) ?>"
		       name="<?php echo esc_attr( $setting_name ); ?>"
		       value="<?php echo esc_attr( '' !== $option_value ? esc_attr( $option_value ) : '' ); ?>"
		       title="Custom Prism CSS filename"
		/>
		<p class="description">File must be in <?php echo esc_html( plugin_dir_path( __FILE__ ) . 'css/' ); ?></p>
		<?php

		debug_log( 'function end' );
	}

	/**
	 * Outputs the HTML for the custom_js setting to the settings page.
	 */
	public function setting_custom_js() {
		debug_log( 'function start' );

		// Get the current options values.
		$options      = get_option( self::SETTINGS_GROUP );
		$option_value = $options[ self::OPTION_CUSTOM_JS ];
		debug_log( 'current value of ' . self::OPTION_CUSTOM_JS . ': ' . $option_value );

		$setting_name = self::SETTINGS_GROUP . '[' . self::OPTION_CUSTOM_JS . ']';

		?>
		<input type="text"
		       id="<?php echo esc_attr( self::OPTION_CUSTOM_JS ) ?>"
		       name="<?php echo esc_attr( $setting_name ); ?>"
		       value="<?php echo esc_attr( '' !== $option_value ? esc_attr( $option_value ) : '' ); ?>"
		       title="Custom Prism JS filename"
		/>
		<p class="description">File must be in <?php echo esc_html( plugin_dir_path( __FILE__ ) . 'js/' ); ?></p>
		<?php
		debug_log( 'function end' );
	}

	/**
	 * Outputs the HTML for the default_inline setting to the settings page.
	 */
	public function setting_default_inline() {
		debug_log( 'function start' );

		// Get the current options values.
		$options      = get_option( self::SETTINGS_GROUP );
		$option_value = ArrayHelper::get_value_or_default( self::OPTION_DEFAULT_INLINE, $options, 0 );
		debug_log( 'current value of ' . self::OPTION_CUSTOM_JS . ': ' . $option_value );

		$setting_name = self::SETTINGS_GROUP . '[' . self::OPTION_DEFAULT_INLINE . ']';

		?>
		<input type="checkbox"
		       id="<?php echo esc_attr( self::OPTION_DEFAULT_INLINE ) ?>"
		       name="<?php echo esc_attr( $setting_name ); ?>"
		       value="1" <?php checked( 1, $option_value, true ); ?>
		       title="Default highlighting to inline"
		/>
		<?php

		debug_log( 'function end' );
	}

	/**
	 * Outputs the HTML for the default_language setting to the settings page.
	 */
	public function setting_default_language() {
		debug_log( 'function start' );

		// Get the current options values.
		$options      = get_option( self::SETTINGS_GROUP );
		$option_value = $options[ self::OPTION_DEFAULT_LANGUAGE ];
		debug_log( 'current value of ' . self::OPTION_DEFAULT_LANGUAGE . ': ' . $option_value );

		// Determine the prism js file currently being used.
		$js_file    = 'prism.js';
		$use_custom = '' !== $options[ self::OPTION_CUSTOM_JS ];
		if ( true === $use_custom ) {
			$js_file = esc_attr( $options[ self::OPTION_CUSTOM_JS ] );
		}

		$js_file_path = plugin_dir_path( __FILE__ ) . 'js/' . $js_file;
		debug_log( 'source js file for languages: ' . $js_file_path );
		$js_file_contents = file_get_contents( $js_file_path );
		$languages        = null;
		preg_match_all( '/Prism\.languages\.(\w+) *= *(\{|Prism\.languages\.extend)/', $js_file_contents, $languages );
		debug_log( $languages );
		sort( $languages[1] );

		$setting_name = self::SETTINGS_GROUP . '[' . self::OPTION_DEFAULT_LANGUAGE . ']';

		?>
		<select id="<?php echo esc_attr( self::OPTION_DEFAULT_LANGUAGE ); ?>"
		        name="<?php echo esc_attr( $setting_name ); ?>"
		        title="Default language"
		>
			<?php foreach ( $languages[1] as $language ) : ?>
				<option value="<?php echo esc_attr( $language ); ?>"
					<?php echo selected( $option_value, $language, false ); ?>>
					<?php echo esc_html( $language ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
		debug_log( 'function end' );
	}

	/**
	 * Outputs the HTML for the default_line_number setting to the settings page.
	 */
	public function setting_default_line_number() {
		debug_log( 'function start' );

		// Get the current options values.
		$options      = get_option( self::SETTINGS_GROUP );
		$option_value = ArrayHelper::get_value_or_default( self::OPTION_DEFAULT_LINE_NUMBERS, $options, 0 );
		debug_log( 'current value of ' . self::OPTION_DEFAULT_LINE_NUMBERS . ': ' . $option_value );

		$setting_name = self::SETTINGS_GROUP . '[' . self::OPTION_DEFAULT_LINE_NUMBERS . ']';

		?>
		<input type="checkbox"
		       id="<?php echo esc_attr( self::OPTION_DEFAULT_LINE_NUMBERS ) ?>"
		       name="<?php echo esc_attr( $setting_name ); ?>"
		       value="1" <?php checked( 1, $option_value, true ); ?>
		       title="Show line numbers by default"
		/>
		<?php
		debug_log( 'function end' );
	}

	/*
	 * ------------------------------------------------------------------------
	 * Other Callbacks
	 * ------------------------------------------------------------------------
	 */

	/**
	 * Outputs the HTML for the show_css_warning_notice setting to the settings page.
	 */
	public function setting_show_css_warning_notice() {
		debug_log( 'function start' );

		// Get the current options values.
		$options      = get_option( self::SETTINGS_GROUP );
		$option_value = $options[ self::OPTION_SHOW_CSS_WARNING_NOTICE ];
		debug_log( 'current value of ' . self::OPTION_SHOW_CSS_WARNING_NOTICE . ': ' . $option_value );

		$setting_name = self::SETTINGS_GROUP . '[' . self::OPTION_SHOW_CSS_WARNING_NOTICE . ']';

		?>
		<input type="hidden"
		       id="<?php echo esc_attr( self::OPTION_SHOW_CSS_WARNING_NOTICE ) ?>"
		       name="<?php echo esc_attr( $setting_name ); ?>"
		       value="<?php echo esc_attr( '' !== $option_value ? esc_attr( $option_value ) : 'no' ); ?>"
		/>
		<?php
		debug_log( 'function end' );
	}

	/**
	 * Outputs the HTML content for the settings page.
	 */
	public function options_page() {
		debug_log( 'function start' );
		?>
		<div class="wrap">
			<h1>Prism.js Syntax Highlighter Settings</h1>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'prismjs-settings-group' );
				do_settings_sections( 'prismjs-settings-admin' );
				submit_button();
				?>
			</form>
		</div>
		<?php
		debug_log( 'function end' );
	}

	/**
	 * Validate/Sanitize user inputted settings values.
	 *
	 * @param array $input The array of settings to validate.
	 *
	 * @return mixed The array of validated settings.
	 */
	public function validate_settings( $input ) {
		debug_log( 'function start' );

		// Array for storing the validated settings.
		$output = array();

		// Loop through each of the input settings.
		foreach ( $input as $key => $value ) {
			if ( isset( $input[ $key ] ) ) {
				// Strip all HTML and PHP tags and properly handle quoted strings.
				$output[ $key ] = sanitize_text_field( $input[ $key ] );
			}
		}

		debug_log( 'function end' );

		// Return the array processing any additional functions filtered by this action.
		return apply_filters( 'validate_settings', $output, $input );
	}
}

$prismjs_syntax_highlighter = new Prismjs_Syntax_Highlighter();
