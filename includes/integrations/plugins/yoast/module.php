<?php
/**
 * Yoast SEO integration for Clutch
 */
namespace Clutch\WP\Integrations\Plugins\Yoast;

use function Clutch\WP\Utils\first_non_empty_str;

/**
 * Initialize the SlimSEO integration
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
});

function prepare_post_fields($response_data)
{
	// remove yoast fields from response
	unset($response_data['yoast_head']);
	unset($response_data['yoast_head_json']);

	return $response_data;
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
	$post_seo = $yoast->meta->for_post($post->ID);

	if (!$post_seo) {
		return $seo_data;
	}

	/* ---------------------------------------------------------------------
	 * 1. Basic tags
	 * ------------------------------------------------------------------ */
	if (!empty($post_seo->title)) {
		$seo_data['title'] = $post_seo->title;
	}

	if (!empty($post_seo->description)) {
		$seo_data['description'] = $post_seo->description;
	}

	if (!empty($post_seo->canonical)) {
		$seo_data['canonical'] = $post_seo->canonical;
	}

	/* ---------------------------------------------------------------------
	 * 2. Robots
	 * ------------------------------------------------------------------ */
	$seo_data['robots'] = $post_seo->robots ?? $seo_data['robots'];

	/* ---------------------------------------------------------------------
	 * 3.  Open-Graph
	 * ------------------------------------------------------------------ */
	$og_images = [];
	if (
		!empty($post_seo->open_graph_images) &&
		is_array($post_seo->open_graph_images)
	) {
		foreach ($post_seo->open_graph_images as $img) {
			$og_images[] = [
				'url' => $img['url'] ?? '',
				'width' => $img['width'] ?? null,
				'height' => $img['height'] ?? null,
				'alt' => $img['alt'] ?? '',
				'type' => $img['type'] ?? '',
			];
		}
	}

	$seo_data['og'] = [
		'title' =>
			$post_seo->open_graph_title ??
			($seo_data['og']['title'] ?? ($seo_data['title'] ?? '')),
		'description' =>
			$post_seo->open_graph_description ??
			($seo_data['og']['description'] ??
				($seo_data['description'] ?? '')),
		'type' =>
			$post_seo->open_graph_type ??
			($seo_data['og']['type'] ?? 'article'),
		'url' => get_permalink($post),
		'site_name' => get_bloginfo('name'),
		'locale' => $post_seo->open_graph_locale ?? get_locale(),
		'published_time' => get_the_date(DATE_W3C, $post),
		'modified_time' => get_the_modified_date(DATE_W3C, $post),
		'author' =>
			$post_seo->post_author ??
			get_the_author_meta('display_name', $post->post_author),
		'image' => $og_images[0]['url'] ?? ($seo_data['og']['image'] ?? null),
		'images' => !empty($og_images)
			? $og_images
			: $seo_data['og']['images'] ?? [],
	];

	// var_dump($post_seo->twitter_title);
	// var_dump($seo_data['twitter']['title']);

	/* ---------------------------------------------------------------------
	 * 4. Twitter
	 * ------------------------------------------------------------------ */
	$seo_data['twitter'] = [
		'card' => first_non_empty_str(
			$post_seo->twitter_card,
			$seo_data['twitter']['card'] ?? 'summary_large_image'
		),
		'title' => first_non_empty_str(
			$post_seo->twitter_title,
			$seo_data['og']['title'] ?? '',
			$seo_data['twitter']['title'] ?? ''
		),
		'description' => first_non_empty_str(
			$post_seo->twitter_description,
			$seo_data['og']['description'] ?? '',
			$seo_data['twitter']['description'] ?? ''
		),
		'image' => first_non_empty_str(
			$post_seo->twitter_image,
			$seo_data['og']['image'] ?? '',
			$seo_data['twitter']['image'] ?? ''
		),
		'site' => first_non_empty_str(
			$post_seo->twitter_site,
			$seo_data['og']['site_name'] ?? '',
			get_option('twitter_site') ?? ''
		),
		'creator' => first_non_empty_str(
			$post_seo->twitter_creator,
			$seo_data['twitter']['creator'] ?? '',
			get_option('twitter_creator') ?? ''
		),
	];

	/* ---------------------------------------------------------------------
	 * 5. Schema (json-ld)
	 * ------------------------------------------------------------------ */
	if ($post_seo->schema) {
		$seo_data['schema'] = $post_seo->schema;
	}

	/* ---------------------------------------------------------------------
	 * 6. Breadcrumbs
	 * ------------------------------------------------------------------ */
	if ($post_seo->breadcrumbs) {
		$seo_data['breadcrumbs'] = $post_seo->breadcrumbs;
	}

	return $seo_data;
}

/**
 * Filter post type archive SEO data with Yoast SEO values
 *
 * @param array $seo_data The default SEO data
 * @param string $post_type Post type name
 * @param object $post_type_obj Post type object
 * @return array Modified SEO data
 */
function filter_post_type_seo_data($seo_data, $post_type, $post_type_obj)
{
	// Get Yoast options
	$yoast_options = get_option('wpseo_titles');

	if (!$yoast_options) {
		return $seo_data;
	}

	$title_key = 'title-ptarchive-' . $post_type;
	$desc_key = 'metadesc-ptarchive-' . $post_type;
	$noindex_key = 'noindex-ptarchive-' . $post_type;

	// Get title & description from Yoast
	if (isset($yoast_options[$title_key])) {
		$title = wpseo_replace_vars($yoast_options[$title_key], null);
		if (!empty($title)) {
			$seo_data['title'] = $title;
			$seo_data['og']['title'] = $title;
			$seo_data['twitter']['title'] = $title;
		}
	}

	if (isset($yoast_options[$desc_key])) {
		$description = wpseo_replace_vars($yoast_options[$desc_key], null);
		if (!empty($description)) {
			$seo_data['description'] = $description;
			$seo_data['og']['description'] = $description;
			$seo_data['twitter']['description'] = $description;
		}
	}

	// Handle robot directives
	if (isset($yoast_options[$noindex_key]) && $yoast_options[$noindex_key]) {
		$seo_data['robots']['index'] = 'noindex';
	}

	// Get breadcrumbs
	$seo_data['breadcrumbs'] = [
		[
			'url' => home_url(),
			'text' => get_bloginfo('name'),
		],
		[
			'url' => get_post_type_archive_link($post_type),
			'text' => $post_type_obj->label,
		],
	];

	return $seo_data;
}

/**
 * Get Yoast breadcrumbs
 *
 * @param \WP_Post $post Post object
 * @return array Breadcrumbs data
 */
function get_yoast_breadcrumbs($post)
{
	$breadcrumbs = [];

	// Add home
	$breadcrumbs[] = [
		'url' => home_url(),
		'text' => get_bloginfo('name'),
	];

	// For hierarchical post types, add ancestors
	if (is_post_type_hierarchical($post->post_type)) {
		$ancestors = get_post_ancestors($post);

		if (!empty($ancestors)) {
			// Reverse the array to get the correct order
			$ancestors = array_reverse($ancestors);

			foreach ($ancestors as $ancestor_id) {
				$ancestor = get_post($ancestor_id);
				if ($ancestor) {
					$breadcrumbs[] = [
						'url' => get_permalink($ancestor),
						'text' => $ancestor->post_title,
					];
				}
			}
		}
	}
	// For posts, add categories
	elseif ($post->post_type === 'post') {
		$categories = get_the_category($post->ID);

		if (!empty($categories)) {
			$category = $categories[0]; // Primary category if using Yoast

			// Check if Yoast Primary category is set
			$primary_cat_id = get_post_meta(
				$post->ID,
				'_yoast_wpseo_primary_category',
				true
			);
			if ($primary_cat_id) {
				foreach ($categories as $cat) {
					if ($cat->term_id == $primary_cat_id) {
						$category = $cat;
						break;
					}
				}
			}

			$breadcrumbs[] = [
				'url' => get_category_link($category->term_id),
				'text' => $category->name,
			];
		}
	}
	// For other post types, add the archive link
	elseif ($post->post_type !== 'page') {
		$post_type_obj = get_post_type_object($post->post_type);

		if ($post_type_obj && $post_type_obj->has_archive) {
			$breadcrumbs[] = [
				'url' => get_post_type_archive_link($post->post_type),
				'text' => $post_type_obj->label,
			];
		}
	}

	// Add current post
	$breadcrumbs[] = [
		'url' => get_permalink($post),
		'text' => $post->post_title,
	];

	return $breadcrumbs;
}
