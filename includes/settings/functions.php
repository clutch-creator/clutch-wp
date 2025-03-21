<?php
/**
 * Settings functions.
 */

namespace Clutch\WP\Settings;

/**
 * Retrieves the content of an SVG icon file.
 *
 * @param string $icon The name of the icon (without extension).
 * @return string The SVG content if the file exists, or an empty string otherwise.
 */
function get_icon($icon)
{
	// Normalize the icon name
	$icon = basename($icon);

	$path = __DIR__ . '/assets/icons/' . $icon . '.svg';

	if (file_exists($path)) {
		return file_get_contents($path);
	}

	return '';
}

/**
 * Retrieves the menu locations setting.
 *
 * @return string The menu locations as a comma-separated string. Defaults to 'Main, Footer' if not set.
 */
function get_setting_menu_locations()
{
	$options = get_option('clutch_options');

	return isset($options['menu_locations'])
		? $options['menu_locations']
		: 'Main, Footer';
}
