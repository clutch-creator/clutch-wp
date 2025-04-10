<?php
/**
 * This file customizes permalink settings for Clutch.
 * It ensures the permalink structure is set to '/%postname%/' upon plugin activation.
 */
namespace Clutch\WP\Permalinks;

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit();
}

/**
 * Force permalink structure to be /%postname%/ on plugin activation
 */
function activate_clutch_plugin()
{
	global $wp_rewrite;
	$wp_rewrite->set_permalink_structure('/%postname%/');
	$wp_rewrite->flush_rules();
}

register_activation_hook(
	CLUTCHWP_FILE,
	__NAMESPACE__ . '\\activate_clutch_plugin'
);
