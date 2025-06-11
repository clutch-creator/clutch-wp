<?php
/**
 * This file manages menu locations and provides REST API endpoints for menus in Clutch.
 * It includes functions to process menu locations and retrieve menu details.
 */

namespace Clutch\WP\Menus;

use function Clutch\WP\Settings\get_setting_menu_locations_parsed;
use function Clutch\WP\Rest\get_permalink_info;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Processes and registers menu locations based on settings.
 *
 * @param array $locations Array of menu locations.
 * @return void
 */
function process_menu_locations($locations)
{
	// unregister all existing menu locations
	$menus = array_keys(get_registered_nav_menus());

	foreach ($menus as $menu) {
		unregister_nav_menu($menu);
	}

	// register fixed menu locations
	$menu_locations = get_setting_menu_locations_parsed();

	if ($menu_locations) {
		register_nav_menus($menu_locations);
	}
}

add_action(
	'after_setup_theme',
	__NAMESPACE__ . '\\process_menu_locations',
	CLUTCHWP_PRIORITY
);

/**
 * Retrieves menu items for a given menu ID and organizes them into a nested structure.
 *
 * @param int $menu_id The ID of the menu to retrieve items for.
 * @return array Nested array of menu items.
 */
function get_menu_items($menu_id)
{
	$menu_items = wp_get_nav_menu_items($menu_id);
	$items = [];

	// Create a lookup array to store items by their ID
	$item_lookup = [];
	foreach ($menu_items as $item) {
		$item_lookup[$item->ID] = [
			'id' => $item->ID,
			'title' => $item->title,
			'url' => $item->url,
			'url_info' => get_permalink_info($item->url),
			'parent' => $item->menu_item_parent,
			'children' => [],
		];
	}

	// Build the nested structure
	foreach ($item_lookup as $item_id => $item) {
		if ($item['parent']) {
			$item_lookup[$item['parent']]['children'][] =
				&$item_lookup[$item_id];
		} else {
			$items[] = &$item_lookup[$item_id];
		}
	}

	return $items;
}

/**
 * Retrieves all registered menu locations and their assigned menus.
 *
 * @return \WP_REST_Response REST response containing menu locations and assignments.
 */
function rest_get_menus_locations()
{
	$menu_locations = get_registered_nav_menus();
	$menu_assignments = get_nav_menu_locations(); // Get assigned menus
	$response = [];

	foreach ($menu_locations as $location => $name) {
		$menu_id = $menu_assignments[$location] ?? null;
		$menu = $menu_id ? wp_get_nav_menu_object($menu_id) : null;

		$response[] = [
			'id' => $location,
			'name' => $name,
			'menu' => $menu
				? [
					'id' => $menu->term_id,
					'name' => $menu->name,
					'slug' => $menu->slug,
				]
				: null,
		];
	}

	return new \WP_REST_Response($response);
}

/**
 * Retrieves menu details and items for a specific menu location.
 *
 * @param \WP_REST_Request $request The REST API request object.
 * @return \WP_REST_Response REST response containing menu details and items.
 */
function rest_get_menu_by_location(\WP_REST_Request $request)
{
	$location_id = $request->get_param('location');
	$menu_locations = get_registered_nav_menus();

	if (!isset($menu_locations[$location_id])) {
		return new \WP_REST_Response(
			['message' => 'Menu location not found'],
			404
		);
	}

	$menu_id = get_nav_menu_locations()[$location_id] ?? null;

	if (!$menu_id) {
		return new \WP_REST_Response(
			['message' => 'No menu assigned to this location'],
			404
		);
	}

	$menu = wp_get_nav_menu_object($menu_id);

	$response = [
		'id' => $menu->term_id,
		'name' => $menu->name,
		'slug' => $menu->slug,
		'items' => get_menu_items($menu->term_id),
	];

	return new \WP_REST_Response($response);
}

add_action('rest_api_init', function () {
	register_rest_route('clutch/v1', '/menus', [
		'methods' => 'GET',
		'callback' => __NAMESPACE__ . '\\rest_get_menus_locations',
		'permission_callback' => function () {
			return current_user_can('edit_posts');
		},
	]);

	register_rest_route('clutch/v1', '/menus/(?P<location>[a-zA-Z0-9_-]+)', [
		'methods' => 'GET',
		'callback' => __NAMESPACE__ . '\\rest_get_menu_by_location',
		'permission_callback' => function () {
			return current_user_can('edit_posts');
		},
	]);
});
