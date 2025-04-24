<?php
/**
 * This file contains functionality to enhance Advanced Custom Fields (ACF) integration with Clutch.
 * It includes filters to format ACF values for REST API responses and ensures proper handling of field groups.
 */

namespace Clutch\WP\ACF;

require_once __DIR__ . '/functions.php';

if (!defined('ABSPATH')) {
	exit();
}

add_filter(
	'acf/rest/format_value_for_rest',
	function ($value_formatted, $post_id, $field, $value, $format) {
		if ($field['type'] === 'user') {
			$user_values = is_array($value) ? $value : [$value];
			$users = array_map(function ($user_id) {
				return [
					'_clt_acf' => true,
					'type' => 'user',
					'id' => $user_id,
				];
			}, $user_values);

			return is_array($value) ? $users : $users[0];
		}

		if ($field['type'] === 'taxonomy') {
			$taxonomy_values = is_array($value) ? $value : [$value];
			$terms = array_map(function ($term_id) use ($field) {
				return [
					'_clt_acf' => true,
					'type' => 'taxonomy_term',
					'rest_base' => $field['taxonomy'],
					'id' => $term_id,
				];
			}, $taxonomy_values);

			return is_array($value) ? $terms : $terms[0];
		}

		if (
			$field['type'] === 'relationship' ||
			$field['type'] === 'post_object'
		) {
			$post_ids = is_array($value) ? $value : [$value];
			$posts = array_map(function ($post_id) {
				$post_type = get_post_type($post_id);
				$post_type_object = get_post_type_object($post_type);

				return [
					'_clt_acf' => true,
					'type' => 'post',
					'id' => $post_id,
					'rest_base' =>
						$post_type_object->rest_base ?: $post_type_object->name,
					'rest_namespace' => $post_type_object->rest_namespace,
				];
			}, $post_ids);

			return is_array($value) ? $posts : $posts[0];
		}

		if (
			$field['type'] === 'image' ||
			$field['type'] === 'file' ||
			$field['type'] === 'gallery'
		) {
			$image_values = is_array($value) ? $value : [$value];
			$images = array_map(function ($image_id) {
				return [
					'_clt_acf' => true,
					'type' => 'media',
					'id' => $image_id,
				];
			}, $image_values);

			return is_array($value) ? $images : $images[0];
		}

		// page links use acf standard format
		if ($field['type'] === 'page_link') {
			return acf_format_value($value, $post_id, $field, $format);
		}

		return $value_formatted;
	},
	CLUTCHWP_PRIORITY,
	5
);

add_filter(
	'acf/load_field_group',
	function ($field_group) {
		// Force 'show_in_rest' to true if not edited by force
		if (isset($field_group['show_in_rest_force'])) {
			$field_group['show_in_rest'] = $field_group['show_in_rest_force'];
		} else {
			$field_group['show_in_rest'] = 1;
		}

		return $field_group;
	},
	CLUTCHWP_PRIORITY,
	1
);

add_filter(
	'wp_insert_post_data',
	function ($data, $postarr, $unsanitized, $update) {
		// Only target ACF field groups updates
		if (
			$data['post_type'] === 'acf-field-group' &&
			$update &&
			is_serialized($data['post_content'])
		) {
			// unserialize content
			$content = maybe_unserialize(stripslashes($data['post_content']));

			if (is_array($content)) {
				// check from content show_in_rest
				if (
					$content['show_in_rest'] === 0 ||
					$content['show_in_rest_force'] === 0
				) {
					$content['show_in_rest_force'] = $content['show_in_rest'];
				}

				// update $data content
				$data['post_content'] = maybe_serialize($content);
			}
		}

		return $data;
	},
	CLUTCHWP_PRIORITY,
	4
);
