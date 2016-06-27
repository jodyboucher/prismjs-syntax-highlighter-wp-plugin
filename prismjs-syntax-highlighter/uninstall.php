<?php
/**
 * Prism.js Syntax Highlighter WordPress plugin: uninstall routines
 *
 * Called when <Prism.js Syntax Highlighter> is uninstalled.
 *
 * @package JodyBoucher\Wordpress\PrismjsSyntaxHighlighter
 * @author  Jody Boucher <jody@jodyboucher.com>
 * @license   GPL2
 * @copyright 2016 Jody Boucher */

namespace JodyBoucher\Wordpress\PrismjsSyntaxHighlighter;

// If uninstall is not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

delete_option( Prismjs_Syntax_Highlighter::SETTINGS_GROUP );

// For site options in Multisite.
delete_site_option( Prismjs_Syntax_Highlighter::SETTINGS_GROUP );
