<?php
/**
 * This file contains functionality to enhance Advanced Custom Fields (ACF) integration with Clutch.
 * It includes filters to format ACF values for REST API responses and ensures proper handling of field groups.
 */

namespace Clutch\WP\ACF;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Check if ACF is installed and active.
 *
 * @return bool True if ACF is installed and active, false otherwise.
 */
function is_acf_installed()
{
	return function_exists('acf_get_field_groups');
}

function get_acf_fields_for_rest($post_id)
{
	if (is_acf_installed() === false) {
		return [];
	}

	$raw_acf_fields = get_fields($post_id, false) ?: [];
	$formatted_acf_fields = [];

	foreach ($raw_acf_fields as $key => $value) {
		$field = get_field_object($key, $post_id);

		if (!$field) {
			$formatted_acf_fields[$key] = $value;
			continue;
		}

		$formatted_acf_fields[$key] = acf_format_value_for_rest(
			$value,
			$post_id,
			$field,
			'light'
		);
	}

	return $formatted_acf_fields;
}

function apply_acf_fields_on_reponse($response_data, $post_id)
{
	if (is_acf_installed() === false) {
		return $response_data;
	}

	$acf_fields = get_acf_fields_for_rest($post_id);

	if (empty($acf_fields)) {
		return $response_data;
	}

	$response_data['acf'] = $acf_fields;

	return $response_data;
}
