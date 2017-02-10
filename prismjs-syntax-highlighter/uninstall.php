<?php

namespace JodyBoucher\WordPress\Plugins\PrismJsSyntaxHighlighter;

// If uninstall is not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

delete_option( Prismjs_Syntax_Highlighter::SETTINGS_GROUP );

// For site options in multi-site.
delete_site_option( Prismjs_Syntax_Highlighter::SETTINGS_GROUP );
