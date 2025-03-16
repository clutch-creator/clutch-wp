<?php
/**
 * Adds custom REST API endpoints that clutch uses
 */
namespace Clutch\WP\Menus;

if (!defined('ABSPATH')) {
	exit();
}

function get_menu_items($menu_id)
{
	$menu_items = wp_get_nav_menu_items($menu_id);
	$items = [];

	// Create a lookup array to store items by their ID
	$item_lookup = [];
	foreach ($menu_items as $item) {
		$item_lookup[$item->ID] = [
			'ID' => $item->ID,
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

function rest_get_menus()
{
	$nav_menus = wp_get_nav_menus();
	$response = [];

	foreach ($nav_menus as $menu) {
		$response[] = [
			'ID' => $menu->term_id,
			'name' => $menu->name,
			'slug' => $menu->slug,
			'items' => get_menu_items($menu->term_id),
		];
	}

	return new \WP_REST_Response($response);
}

function rest_get_menu_by_id(\WP_REST_Request $request)
{
	$menu_id = $request->get_param('id');
	$menu = wp_get_nav_menu_object($menu_id);

	if (!$menu) {
		return new \WP_REST_Response(['message' => 'Menu not found'], 404);
	}

	$response = [
		'ID' => $menu->term_id,
		'name' => $menu->name,
		'slug' => $menu->slug,
		'items' => get_menu_items($menu->term_id),
	];

	return new \WP_REST_Response($response);
}

add_action('rest_api_init', function () {
	register_rest_route('clutch/v1', '/menus', [
		'methods' => 'GET',
		'callback' => __NAMESPACE__ . '\\rest_get_menus',
	]);

	register_rest_route('clutch/v1', '/menus/(?P<id>\d+)', [
		'methods' => 'GET',
		'callback' => __NAMESPACE__ . '\\rest_get_menu_by_id',
	]);
});
