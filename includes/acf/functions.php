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
			'standard'
		);

		// switch ($field['type']) {
		// 	case 'user':
		// 		$user_values = is_array($value) ? $value : [$value];
		// 		$formatted_acf_fields[$key] = array_map(function ($user_id) {
		// 			return [
		// 				'_clt_field' => true,
		// 				'type' => 'user',
		// 				'id' => $user_id,
		// 			];
		// 		}, $user_values);
		// 		if (!is_array($value)) {
		// 			$formatted_acf_fields[$key] =
		// 				$formatted_acf_fields[$key][0];
		// 		}
		// 		break;

		// 	case 'taxonomy':
		// 		$taxonomy_values = is_array($value) ? $value : [$value];
		// 		$formatted_acf_fields[$key] = array_map(function (
		// 			$term_id
		// 		) use ($field) {
		// 			return [
		// 				'_clt_field' => true,
		// 				'type' => 'taxonomy_term',
		// 				'rest_base' => $field['taxonomy'],
		// 				'id' => $term_id,
		// 			];
		// 		}, $taxonomy_values);
		// 		if (!is_array($value)) {
		// 			$formatted_acf_fields[$key] =
		// 				$formatted_acf_fields[$key][0];
		// 		}
		// 		break;

		// 	case 'relationship':
		// 	case 'post_object':
		// 		$post_ids = is_array($value) ? $value : [$value];
		// 		$formatted_acf_fields[$key] = array_map(function ($post_id) {
		// 			$post_type = get_post_type($post_id);
		// 			$post_type_object = get_post_type_object($post_type);

		// 			return [
		// 				'_clt_field' => true,
		// 				'type' => 'post',
		// 				'id' => $post_id,
		// 				'rest_base' =>
		// 					$post_type_object->rest_base ?:
		// 					$post_type_object->name,
		// 				'rest_namespace' => $post_type_object->rest_namespace,
		// 			];
		// 		}, $post_ids);
		// 		if (!is_array($value)) {
		// 			$formatted_acf_fields[$key] =
		// 				$formatted_acf_fields[$key][0];
		// 		}
		// 		break;

		// 	case 'image':
		// 	case 'file':
		// 	case 'gallery':
		// 		$image_values = is_array($value) ? $value : [$value];
		// 		$formatted_acf_fields[$key] = array_map(function ($image_id) {
		// 			return [
		// 				'_clt_field' => true,
		// 				'type' => 'media',
		// 				'id' => $image_id,
		// 			];
		// 		}, $image_values);
		// 		if (!is_array($value)) {
		// 			$formatted_acf_fields[$key] =
		// 				$formatted_acf_fields[$key][0];
		// 		}
		// 		break;

		// 	case 'page_link':
		// 		$formatted_acf_fields[$key] = acf_format_value(
		// 			$value,
		// 			$post_id,
		// 			$field
		// 		);
		// 		break;

		// 	default:
		// 		$formatted_acf_fields[$key] = acf_format_value(
		// 			$value,
		// 			$post_id,
		// 			$field
		// 		);
		// 		break;
		// }
	}

	return $formatted_acf_fields;
}
