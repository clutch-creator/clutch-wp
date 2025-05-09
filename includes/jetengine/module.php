<?php
/**
 * This file contains functionality to enhance JetEngine integration with Clutch.
 * It includes filters to format JetEngine values for REST API responses.
 */

namespace Clutch\WP\JetEngine;

require_once __DIR__ . '/functions.php';

if (!defined('ABSPATH')) {
	exit();
}

add_filter(
	'clutch/prepare_post_fields',
	function ($response_data, $post_id) {
		return apply_jetengine_fields_on_response($response_data, $post_id);
	},
	10,
	2
);

add_filter(
	'clutch/prepare_term_fields',
	function ($response_data, $term_id) {
		return apply_jetengine_fields_on_response($response_data, $term_id);
	},
	10,
	2
);
