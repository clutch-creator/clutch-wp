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

function get_acf_post_type_fields($post_type)
{
	if (is_acf_installed() === false) {
		return [];
	}

	$fieldsResult = [];
	$field_groups = acf_get_field_groups([
		'post_type' => $post_type,
	]);

	// 2. walk through the groups and their fields
	foreach ($field_groups as $group) {
		$fields = acf_get_fields($group['key']);

		if ($fields) {
			foreach ($fields as $field) {
				$fieldsResult[$field['name']] = $field;
			}
		}
	}

	return $fieldsResult;
}

/**
 * Translate ONE ACF field to a WP meta schema type.
 *
 * @param array $field  Full ACF field array.
 * @return string       Valid schema type.
 */
function acf_field_to_meta_type($field)
{
	/*--------------------------------------------------------------*
	 * 1) Straightforward 1:1 mappings
	 *--------------------------------------------------------------*/
	$simple = [
		// string-y
		'text' => 'string',
		'textarea' => 'string',
		'wysiwyg' => 'string',
		'email' => 'string',
		'url' => 'string',
		'password' => 'string',
		'color_picker' => 'string',
		'oembed' => 'string',
		'page_link' => 'string',

		// numeric
		'number' => 'number',
		'range' => 'number',

		// true/false
		'true_false' => 'boolean',

		// media / date
		'file' => 'integer', // attachment ID
		'image' => 'integer', // attachment ID
		'date_picker' => 'string',
		'date_time_picker' => 'string',
		'time_picker' => 'string',

		// other objects
		'google_map' => 'object',
	];

	if (isset($simple[$field['type']])) {
		return $simple[$field['type']];
	}

	/*--------------------------------------------------------------*
	 * 2) Types whose return value depends on a setting
	 *--------------------------------------------------------------*/
	switch ($field['type']) {
		// Checkbox always delivers an array of selected values.
		case 'checkbox':
			return 'array';

		// Select: string for single, array for multiple.
		case 'select':
			return empty($field['multiple']) ? 'string' : 'array';

		// Radio is single choice.
		case 'radio':
			return 'string';

		// Post, user, taxonomy: integer for single, array for multi.
		case 'post_object':
		case 'user':
		case 'taxonomy':
			return empty($field['multiple']) ? 'integer' : 'array';

		// Relationship & gallery always return an array of IDs/objects.
		case 'relationship':
		case 'gallery':
			return 'array';

		// Complex containers.
		case 'repeater':
		case 'flexible_content':
			return 'array';

		case 'group':
		case 'clone':
			return 'object';

		default:
			// Anything unknown becomes “object” so that the data still passes validation.
			return 'object';
	}
}

function get_acf_post_type_meta_fields_meta_types($post_type)
{
	$fields = get_acf_post_type_fields($post_type);
	$meta_types = [];

	foreach ($fields as $field) {
		$meta_types[$field['name']] = acf_field_to_meta_type($field);
	}

	return $meta_types;
}
