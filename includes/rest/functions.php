<?php
/**
 * This file defines helper functions for preparing REST API responses in Clutch.
 *
 * @package Clutch\WP\Rest
 */

namespace Clutch\WP\Rest;

use function Clutch\WP\ACF\get_acf_post_type_meta_fields_meta_types;

/**
 * Prepares a post object for REST API response.
 *
 * @param int   $postId The ID of the post being prepared.
 * @param array $response_data The original response data.
 * @return array The modified response data.
 */
function prepare_post_for_rest($postId, $response_data)
{
	// Add raw meta (respect show_in_rest, exclude keys starting with underscore)
	$registered_meta = get_registered_meta_keys('post', $response_data['type']);
	$all_meta = get_post_meta($postId);

	$response_data['meta'] = [];

	foreach ($all_meta as $key => $value) {
		if (
			!str_starts_with($key, '_') &&
			(!empty($registered_meta[$key]['show_in_rest'])
				? $registered_meta[$key]['show_in_rest']
				: false)
		) {
			$response_data['meta'][$key] = $value[0];
		}
	}

	// Apply filters for custom fields
	$response_data = apply_filters(
		'clutch/prepare_post_fields',
		$response_data,
		$postId
	);

	// Add taxonomies directly to response_data using rest_base (e.g., tags, categories)
	$taxonomies = get_object_taxonomies(get_post_type($postId), 'objects');

	foreach ($taxonomies as $taxonomy) {
		if (!$taxonomy->show_in_rest) {
			continue;
		}

		$rest_base = $taxonomy->rest_base ?: $taxonomy->name;
		$terms = get_the_terms($postId, $taxonomy->name);
		if (!is_wp_error($terms) && !empty($terms)) {
			$response_data[$rest_base] = array_map(function ($term) use (
				$taxonomy,
				$rest_base
			) {
				return [
					'_clutch_type' => 'taxonomy_term',
					'id' => $term->term_id,
					'taxonomy' => $taxonomy->name,
				];
			}, $terms);
		} else {
			$response_data[$rest_base] = []; // Ensure key exists even if no terms
		}
	}

	// Replace author with _clutch_type node or null
	if (isset($response_data['author'])) {
		$response_data['author'] =
			$response_data['author'] !== 0
				? [
					'_clutch_type' => 'user',
					'id' => $response_data['author'],
				]
				: null;
	}

	// Replace featured_media with _clutch_type node or null
	if (isset($response_data['featured_media'])) {
		$response_data['featured_media'] =
			$response_data['featured_media'] !== 0
				? [
					'_clutch_type' => 'media',
					'id' => $response_data['featured_media'],
				]
				: null;
	}

	// cleanup dates
	if (isset($response_data['date'])) {
		$response_data['date'] = [
			'_clutch_type' => 'date',
			'date' => $response_data['date_gmt'] ?: $response_data['date'],
		];
	}

	if (isset($response_data['modified'])) {
		$response_data['modified'] = [
			'_clutch_type' => 'date',
			'date' =>
				$response_data['modified_gmt'] ?: $response_data['modified'],
		];
	}

	// all content fields should just return the rendered content
	if (isset($response_data['title'])) {
		$response_data['title'] = $response_data['title']['rendered'];
	}
	if (isset($response_data['content'])) {
		$response_data['content'] = $response_data['content']['rendered'];
	}
	if (isset($response_data['excerpt'])) {
		$response_data['excerpt'] = $response_data['excerpt']['rendered'];
	}
	if (isset($response_data['description'])) {
		$response_data['description'] =
			$response_data['description']['rendered'];
	}
	if (isset($response_data['caption'])) {
		$response_data['caption'] = $response_data['caption']['rendered'];
	}

	// link
	if (isset($response_data['link'])) {
		$permaInfo = get_permalink_info($response_data['link']);

		$response_data['link'] = [
			'_clutch_type' => 'link',
			...$permaInfo,
		];
	}

	// Remove unnecessary keys
	unset(
		$response_data['guid'],
		$response_data['modified_gmt'],
		$response_data['date_gmt'],
		$response_data['template'],
		$response_data['ping_status'],
		$response_data['_links'],
		$response_data['_embedded']
	);

	return $response_data;
}

/**
 * Prepares a term object for REST API response.
 *
 * @param int   $termId The ID of the term being prepared.
 * @param array $response_data The original response data.
 * @return array The modified response data.
 */
function prepare_term_for_rest($termId, $response_data)
{
	// Add raw meta (respect show_in_rest, exclude keys starting with underscore)
	$registered_meta = get_registered_meta_keys(
		'term',
		$response_data['taxonomy']
	);
	$all_meta = get_term_meta($termId);

	$response_data['meta'] = [];

	foreach ($all_meta as $key => $value) {
		if (
			!str_starts_with($key, '_') &&
			(!empty($registered_meta[$key]['show_in_rest'])
				? $registered_meta[$key]['show_in_rest']
				: false)
		) {
			$response_data['meta'][$key] = $value[0];
		}
	}

	// Apply filters for custom fields
	$response_data = apply_filters(
		'clutch/prepare_term_fields',
		$response_data,
		$termId
	);

	// link
	if (isset($response_data['link'])) {
		$permaInfo = get_permalink_info($response_data['link']);

		$response_data['link'] = [
			'_clutch_type' => 'link',
			...$permaInfo,
		];
	}

	// Cleanup unnecessary keys
	unset($response_data['_links'], $response_data['_embedded']);

	return $response_data;
}

function get_post_type_meta_fields_types($post_type)
{
	$result = [];

	// add registered meta fields
	$metas = get_registered_meta_keys('post', $post_type);

	foreach ($metas as $key => $meta) {
		if (isset($meta['type'])) {
			$result[$key] = $meta['type'];
		}
	}

	// acf for some reason doesnt register the fields as meta
	$acf_fields = get_acf_post_type_meta_fields_meta_types($post_type);

	// merge acf fields which is already a key to type
	$result = array_merge($result, $acf_fields);

	return $result;
}
