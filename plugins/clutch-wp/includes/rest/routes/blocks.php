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
	]);

	register_rest_route('clutch/v1', '/block-styles', [
		'methods' => 'POST',
		'callback' => __NAMESPACE__ . '\\rest_set_block_styles',
	]);
});

/**
 * Retrieves all block styles stored as custom posts.
 *
 * @return \WP_REST_Response A REST response containing block style data.
 */
function rest_get_block_styles()
{
	$block_styles = get_posts([
		'post_type' => 'clutch_block_styles',
		'posts_per_page' => -1,
		'post_status' => 'publish',
	]);

	$response = [];

	foreach ($block_styles as $block_style) {
		$response[] = [
			'id' => get_post_meta($block_style->ID, 'style_id', true),
			'label' => $block_style->post_title,
			'className' => get_post_meta(
				$block_style->ID,
				'style_classname',
				true
			),
			'style' => $block_style->post_content,
		];
	}

	// @todo: ensure classes get sorted in the same order they're shown in Clutch
	usort($response, function ($a, $b) {
		return $a['id'] > $b['id'];
	});

	return new \WP_REST_Response($response);
}

/**
 * Updates or creates block styles based on the provided data.
 *
 * @param \WP_REST_Request $request The REST API request object.
 * @return \WP_REST_Response|\WP_Error A REST response indicating success or an error.
 */
function rest_set_block_styles(\WP_REST_Request $request)
{
	$block_styles = $request->get_json_params();

	if (!is_array($block_styles)) {
		return new \WP_Error(
			'invalid_block_styles',
			'Block styles must be an array',
			['status' => 400]
		);
	}

	// @todo optimize by mutating the retrieved post instead of upserting
	foreach ($block_styles as $block_style) {
		// retrieve custom post for block style
		$existing_block_style = get_posts([
			'numberposts' => 1,
			'fields' => 'ids',
			'post_type' => 'clutch_block_styles',
			'meta_key' => 'style_id',
			'meta_value' => $block_style['id'],
		]);

		// create or update custom post for block style
		if (isset($block_style['id'], $block_style['className'])) {
			wp_insert_post([
				'ID' => $existing_block_style ? $existing_block_style[0] : 0,
				'post_type' => 'clutch_block_styles',
				'post_title' =>
					$block_style['label'] ?: $block_style['className'],
				'post_content' => $block_style['style'],
				'post_status' => 'publish',
				'meta_input' => [
					'style_id' => $block_style['id'],
					'style_classname' => $block_style['className'],
				],
			]);
		}
	}

	return new \WP_REST_Response(null, 200);
}
