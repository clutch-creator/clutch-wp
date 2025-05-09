<?php
/**
 * This file contains functionality to enhance Advanced Custom Fields (ACF) integration with Clutch.
 * It includes filters to format ACF values for REST API responses and ensures proper handling of field groups.
 */

namespace Clutch\WP\MetaBox;

require_once __DIR__ . '/functions.php';

if (!defined('ABSPATH')) {
	exit();
}

add_filter(
	'clutch/prepare_post_fields',
	__NAMESPACE__ . '\\apply_metabox_fields_on_response',
	10,
	2
);

add_filter(
	'clutch/prepare_term_fields',
	function ($response_data, $term_id) {
		return apply_metabox_fields_on_response(
			$response_data,
			$term_id,
			'term'
		);
	},
	10,
	2
);
