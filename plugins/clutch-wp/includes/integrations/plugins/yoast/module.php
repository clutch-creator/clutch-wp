<?php
/**
 * Yoast SEO integration for Clutch
 */
namespace Clutch\WP\Integrations\Plugins\Yoast;

use function Clutch\WP\Utils\first_non_empty_str;

/**
 * Initialize the Yoast SEO integration
 */
add_action('plugins_loaded', function () {
	// Check if Yoast SEO is active
	if (!defined('WPSEO_VERSION')) {
		return;
	}

	// Add filters to modify SEO data
	add_filter(
		'clutch/prepare_post_seo',
		__NAMESPACE__ . '\\filter_post_seo_data',
		10,
		2
	);
	add_filter(
		'clutch/prepare_post_type_seo',
		__NAMESPACE__ . '\\filter_post_type_seo_data',
		10,
		3
	);

	add_filter(
		'clutch/prepare_post_fields',
		__NAMESPACE__ . '\\prepare_post_fields',
		10,
		1
	);

	// Add filters for taxonomy SEO
	add_filter(
		'clutch/prepare_taxonomy_term_seo',
		__NAMESPACE__ . '\\filter_taxonomy_term_seo_data',
		10,
		2
	);
	add_filter(
		'clutch/prepare_taxonomy_archive_seo',
		__NAMESPACE__ . '\\filter_taxonomy_archive_seo_data',
		10,
		3
	);
});

function prepare_post_fields($response_data)
{
	// remove yoast fields from response
	unset($response_data['yoast_head']);
	unset($response_data['yoast_head_json']);

	return $response_data;
}

function yoast_seo_to_common($seo_result, $seo_yoast)
{
	if (!$seo_yoast) {
		return $seo_result;
	}

	/* ---------------------------------------------------------------------
	 * 1. Basic tags
	 * ------------------------------------------------------------------ */
	if (!empty($seo_yoast->title)) {
		$seo_result['title'] = $seo_yoast->title;
	}

	if (!empty($seo_yoast->description)) {
		$seo_result['description'] = $seo_yoast->description;
	}

	/* ---------------------------------------------------------------------
	 * 2. Robots
	 * ------------------------------------------------------------------ */
	$seo_result['robots'] = $seo_yoast->robots ?? $seo_result['robots'];

	/* ---------------------------------------------------------------------
	 * 3.  Open-Graph
	 * ------------------------------------------------------------------ */
	$og_images = [];
	if (
		!empty($seo_yoast->open_graph_images) &&
		is_array($seo_yoast->open_graph_images)
	) {
		foreach ($seo_yoast->open_graph_images as $img) {
			$og_images[] = [
				'url' => $img['url'] ?? '',
				'width' => $img['width'] ?? null,
				'height' => $img['height'] ?? null,
				'alt' => $img['alt'] ?? '',
				'type' => $img['type'] ?? '',
			];
		}
	}

	$seo_result['og']['title'] = first_non_empty_str(
		$seo_yoast->open_graph_title,
		$seo_result['title'] ?? ''
	);

	$seo_result['og']['description'] = first_non_empty_str(
		$seo_yoast->open_graph_description,
		$seo_result['description'] ?? ''
	);

	$seo_result['og']['type'] = first_non_empty_str(
		$seo_yoast->open_graph_type,
		$seo_result['og']['type'] ?? 'article'
	);

	$seo_result['og']['locale'] = first_non_empty_str(
		$seo_yoast->open_graph_locale,
		$seo_result['locale'] ?? ''
	);

	$seo_result['og']['author'] = first_non_empty_str(
		$seo_yoast->post_author,
		$seo_result['og']['author'] ?? ''
	);

	if (!empty($og_images)) {
		$seo_result['og']['image'] = first_non_empty_str(
			$og_images[0]['url'],
			$seo_result['og']['image'] ?? null
		);
	}

	$seo_result['og']['images'] = !empty($og_images)
		? $og_images
		: $seo_result['og']['images'] ?? [];

	/* ---------------------------------------------------------------------
	 * 4. Twitter
	 * ------------------------------------------------------------------ */
	$seo_result['twitter'] = [
		'card' => first_non_empty_str(
			$seo_yoast->twitter_card,
			$seo_result['twitter']['card'] ?? 'summary_large_image'
		),
		'title' => first_non_empty_str(
			$seo_yoast->twitter_title,
			$seo_result['og']['title'] ?? '',
			$seo_result['twitter']['title'] ?? ''
		),
		'description' => first_non_empty_str(
			$seo_yoast->twitter_description,
			$seo_result['og']['description'] ?? '',
			$seo_result['twitter']['description'] ?? ''
		),
		'image' => first_non_empty_str(
			$seo_yoast->twitter_image,
			$seo_result['og']['image'] ?? '',
			$seo_result['twitter']['image'] ?? ''
		),
		'site' => first_non_empty_str(
			$seo_yoast->twitter_site,
			$seo_result['og']['site_name'] ?? '',
			get_option('twitter_site') ?? ''
		),
		'creator' => first_non_empty_str(
			$seo_yoast->twitter_creator,
			$seo_result['twitter']['creator'] ?? '',
			get_option('twitter_creator') ?? ''
		),
	];

	/* ---------------------------------------------------------------------
	 * 5. Schema (json-ld)
	 * ------------------------------------------------------------------ */
	if ($seo_yoast->schema) {
		$seo_result['schema'] = $seo_yoast->schema;
	}

	/* ---------------------------------------------------------------------
	 * 6. Breadcrumbs
	 * ------------------------------------------------------------------ */
	if ($seo_yoast->breadcrumbs) {
		$seo_result['breadcrumbs'] = $seo_yoast->breadcrumbs;
	}

	return $seo_result;
}

/**
 * Filter post SEO data with Yoast SEO values
 *
 * @param array $seo_data The default SEO data
 * @param \WP_Post $post The post object
 * @return array Modified SEO data
 */
function filter_post_seo_data($seo_data, $post)
{
	if (!$post) {
		return $seo_data;
	}

	//--------------------------------------------------------------
	// 1. Try the “presentation” object (Yoast 14-24).
	// https://developer.yoast.com/customization/apis/surfaces-api/
	//--------------------------------------------------------------
	if (!function_exists('YoastSEO')) {
		// Yoast not active?
		return $seo_data;
	}

	$yoast = \YoastSEO();
	$seo_yoast = $yoast->meta->for_post($post->ID);

	return yoast_seo_to_common($seo_data, $seo_yoast);
}

/**
 * Filter post type archive SEO data with Yoast SEO values
 *
 * @param array $seo_data The default SEO data
 * @param string $post_type Post type name
 * @return array Modified SEO data
 */
function filter_post_type_seo_data($seo_data, $post_type)
{
	//--------------------------------------------------------------
	// 1. Try the “presentation” object (Yoast 14-24).
	// https://developer.yoast.com/customization/apis/surfaces-api/
	//--------------------------------------------------------------
	if (!function_exists('YoastSEO')) {
		// Yoast not active?
		return $seo_data;
	}

	$yoast = \YoastSEO();
	$seo_yoast = $yoast->meta->for_post_type_archive($post_type);

	return yoast_seo_to_common($seo_data, $seo_yoast);
}

/**
 * Filter taxonomy term SEO data with Yoast SEO values
 *
 * @param array    $seo_data The default SEO data
 * @param \WP_Term $term     The taxonomy term object
 * @return array Modified SEO data
 */
function filter_taxonomy_term_seo_data($seo_data, $term)
{
	if (!function_exists('YoastSEO')) {
		return $seo_data;
	}
	$yoast = \YoastSEO();
	if (method_exists($yoast->meta, 'for_term')) {
		$seo_yoast = $yoast->meta->for_term($term->term_id, $term->taxonomy);
		return yoast_seo_to_common($seo_data, $seo_yoast);
	}
	return $seo_data;
}

/**
 * Filter taxonomy archive SEO data with Yoast SEO values
 *
 * @param array  $seo_data      The default SEO data
 * @param string $taxonomy      Taxonomy slug
 * @param object $taxonomy_obj  Taxonomy object
 * @return array Modified SEO data
 */
function filter_taxonomy_archive_seo_data($seo_data, $taxonomy, $taxonomy_obj)
{
	if (!function_exists('YoastSEO')) {
		return $seo_data;
	}
	$yoast = \YoastSEO();
	if (method_exists($yoast->meta, 'for_term_archive')) {
		$seo_yoast = $yoast->meta->for_term_archive($taxonomy);
		return yoast_seo_to_common($seo_data, $seo_yoast);
	}
	return $seo_data;
}
