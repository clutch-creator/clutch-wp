<?php
/**
 * Adds functionality/handling for clutch support of ACF
 */
namespace Clutch\WP\ACF;

if (!defined('ABSPATH')) {
	exit();
}
add_filter(
	'acf/rest/format_value_for_rest',
	function ($value_formatted, $post_id, $field, $value, $format) {
		if ($field['type'] === 'user') {
			return [
				'type' => 'user',
				'id' => $value,
				'rest_base' => 'users',
			];
		}

		if ($field['type'] === 'taxonomy') {
			return [
				'type' => 'taxonomy',
				'id' => $value,
				'rest_base' => 'posts',
			];
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
					'type' => 'post',
					'id' => $post_id,
					'rest_base' => $post_type_object->rest_base,
					'post_type' => $post_type,
				];
			}, $post_ids);

			return is_array($value) ? $posts : $posts[0];
		}
		return $value_formatted;
	},
	CLUTCHWP_PRIORITY,
	5
);
