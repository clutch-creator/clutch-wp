<?php
/**
 * This file defines custom REST API endpoints for Clutch.
 * It includes endpoints for retrieving plugin info, post types, taxonomies, and clearing cache.
 */
namespace Clutch\WP\Rest;

add_action('rest_api_init', function () {
	register_rest_route('clutch/v1', '/block-styles', [
		'methods' => 'GET',
		'callback' => __NAMESPACE__ . '\\rest_get_block_styles',
		'permission_callback' => function () {
			return current_user_can('edit_posts'); // Ensure the user has the appropriate capability
		},
	]);
});

/**
 * Retrieves all block styles stored as custom posts.
 *
 * @return \WP_REST_Response A REST response containing block style data.
 */
function rest_get_block_styles()
{
	// Retrieve the selected host from user meta (user is already logged in due to
	// permission callback)
	$selected_host = get_user_meta(
		get_current_user_id(),
		'selected_clutch_host',
		true
	);

	// Fetch block styles from remote json file
	$json_url = esc_url($selected_host) . '/clutch/classes.json';
	$json_content = @file_get_contents($json_url);

	// Check if the JSON content was retrieved successfully
	if (!$json_content) {
		return new \WP_Error('no_data', 'No data found', ['status' => 404]);
	}

	// Decode the JSON content
	$block_styles = json_decode($json_content);

	return new \WP_REST_Response($block_styles, 200);
}
