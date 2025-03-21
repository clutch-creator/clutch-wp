<?php

namespace Clutch\WP\Settings;

require_once __DIR__ . '/functions.php';

// Register the admin menu
function register_admin_menu()
{
	$svg_icon =
		'data:image/svg+xml;base64,' . base64_encode(get_icon('clutch'));

	add_menu_page(
		'Clutch Settings', // Page title
		'Clutch', // Menu title
		'manage_options', // Capability
		'clutch-settings', // Menu slug
		__NAMESPACE__ . '\\settings_page', // Callback function
		$svg_icon, // Icon
		80 // Position
	);
}
add_action('admin_menu', __NAMESPACE__ . '\\register_admin_menu');

// Register settings
function register_settings()
{
	register_setting(
		'clutch_settings_group', // Option group
		'clutch_options', // Option name
		__NAMESPACE__ . '\\sanitize_options' // Sanitize callback
	);

	add_settings_section(
		'clutch_main_section', // Section ID
		'Main Settings', // Section title
		null, // Callback
		'clutch-settings' // Page
	);

	add_settings_field(
		'menu_locations', // Field ID
		'Menu Locations', // Field title
		__NAMESPACE__ . '\\menu_locations_field_callback', // Callback
		'clutch-settings', // Page
		'clutch_main_section' // Section
	);
}
add_action('admin_init', __NAMESPACE__ . '\\register_settings');

// Sanitize callback
function sanitize_options($input)
{
	$sanitized_input = [];
	if (isset($input['menu_locations'])) {
		$sanitized_input['menu_locations'] = sanitize_text_field(
			$input['menu_locations']
		);
	}
	return $sanitized_input;
}

// Menu locations field callback
function menu_locations_field_callback()
{
	$value = get_setting_menu_locations(); ?>
    <input type="text" 
           name="clutch_options[menu_locations]" 
           value="<?php echo esc_attr($value); ?>" 
           class="regular-text" />
    <?php
}

// Settings page callback
function settings_page()
{
	require_once __DIR__ . '/views/settings.php';
}

// Enqueue styles for Clutch settings page
function enqueue_clutch_settings_styles($hook_suffix)
{
	if ($hook_suffix === 'toplevel_page_clutch-settings') {
		wp_enqueue_style(
			'clutch-settings-stylesheet',
			CLUTCHWP_URL . 'includes/settings/assets/stylesheet.css',
			[],
			'1.0.0'
		);
	}
}
add_action(
	'admin_enqueue_scripts',
	__NAMESPACE__ . '\\enqueue_clutch_settings_styles'
);
