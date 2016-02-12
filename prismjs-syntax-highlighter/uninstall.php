<?php
/**
 * Called when <Prism.js Syntax Highlighter> is uninstalled.
 *
 * @package Prism.js Syntax Highlighter WordPress plugin
 * @author  Jody Boucher <jody@jodyboucher.com>
 */

// If uninstall is not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

delete_option(prismjs_syntax_highlighter::SETTINGS_GROUP);

// For site options in Multisite
delete_site_option(prismjs_syntax_highlighter::SETTINGS_GROUP);
