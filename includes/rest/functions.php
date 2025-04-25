<?php
/**
 * This file defines custom REST API endpoints for Clutch.
 * It includes endpoints for retrieving plugin info, post types, taxonomies, and clearing cache.
 */
namespace Clutch\WP\Rest;

use function Clutch\WP\ACF\apply_acf_fields_on_reponse;
use function Clutch\WP\MetaBox\apply_metabox_fields_on_response;

function prepare_post_for_rest($postId, $response_data)
{
	// Apply ACF fields
	$response_data = apply_acf_fields_on_reponse($response_data, $postId);

	// Apply MetaBox fields
	$response_data = apply_metabox_fields_on_response($response_data, $postId);

	// Add raw meta (respect show_in_rest, exclude keys starting with underscore)
	$registered_meta = get_registered_meta_keys('post');
	$all_meta = get_post_meta($postId);
	$response_data['meta'] = [];

	foreach ($all_meta as $key => $value) {
		if (
			!str_starts_with($key, '_') &&
			(!empty($registered_meta[$key]['show_in_rest'])
				? $registered_meta[$key]['show_in_rest']
				: false)
		) {
			$response_data['meta'][$key] = $value;
		}
	}

	return $response_data;
}
