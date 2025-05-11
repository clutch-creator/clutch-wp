<?php
/**
 * Yoast SEO integration for Clutch
 */
namespace Clutch\WP\Integrations\Plugins\Yoast;

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
});

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

	// Get Yoast meta data
	$title = get_post_meta($post->ID, '_yoast_wpseo_title', true);
	$description = get_post_meta($post->ID, '_yoast_wpseo_metadesc', true);
	$canonical = get_post_meta($post->ID, '_yoast_wpseo_canonical', true);
	$noindex = get_post_meta(
		$post->ID,
		'_yoast_wpseo_meta-robots-noindex',
		true
	);
	$nofollow = get_post_meta(
		$post->ID,
		'_yoast_wpseo_meta-robots-nofollow',
		true
	);
	$advanced_robots = get_post_meta(
		$post->ID,
		'_yoast_wpseo_meta-robots-adv',
		true
	);

	// OpenGraph data
	$og_title = get_post_meta($post->ID, '_yoast_wpseo_opengraph-title', true);
	$og_description = get_post_meta(
		$post->ID,
		'_yoast_wpseo_opengraph-description',
		true
	);
	$og_image_id = get_post_meta(
		$post->ID,
		'_yoast_wpseo_opengraph-image-id',
		true
	);
	$og_image = get_post_meta($post->ID, '_yoast_wpseo_opengraph-image', true);

	// Twitter data
	$twitter_title = get_post_meta(
		$post->ID,
		'_yoast_wpseo_twitter-title',
		true
	);
	$twitter_description = get_post_meta(
		$post->ID,
		'_yoast_wpseo_twitter-description',
		true
	);
	$twitter_image_id = get_post_meta(
		$post->ID,
		'_yoast_wpseo_twitter-image-id',
		true
	);
	$twitter_image = get_post_meta(
		$post->ID,
		'_yoast_wpseo_twitter-image',
		true
	);

	// Apply title template if empty
	if (empty($title)) {
		$title = \WPSEO_Frontend::get_instance()->title($post->post_title);
	} else {
		$title = wpseo_replace_vars($title, $post);
	}

	// Apply meta description template if empty
	if (empty($description)) {
		$description = \WPSEO_Frontend::get_instance()->metadesc(false);
	} else {
		$description = wpseo_replace_vars($description, $post);
	}

	// Update SEO data array with Yoast values
	if (!empty($title)) {
		$seo_data['title'] = $title;
	}

	if (!empty($description)) {
		$seo_data['description'] = $description;
	}

	if (!empty($canonical)) {
		$seo_data['canonical'] = $canonical;
	}

	// Update robots data
	if ($noindex == 1) {
		$seo_data['robots']['index'] = 'noindex';
	}

	if ($nofollow == 1) {
		$seo_data['robots']['follow'] = 'nofollow';
	}

	if (!empty($advanced_robots)) {
		$advanced = explode(',', $advanced_robots);
		$seo_data['robots']['advanced'] = array_filter($advanced);
	}

	// Update OpenGraph data
	if (!empty($og_title)) {
		$seo_data['og']['title'] = wpseo_replace_vars($og_title, $post);
	} else {
		$seo_data['og']['title'] = $seo_data['title'];
	}

	if (!empty($og_description)) {
		$seo_data['og']['description'] = wpseo_replace_vars(
			$og_description,
			$post
		);
	} else {
		$seo_data['og']['description'] = $seo_data['description'];
	}

	if (!empty($og_image_id)) {
		$og_image = wp_get_attachment_image_url($og_image_id, 'full');
	}

	if (!empty($og_image)) {
		$seo_data['og']['image'] = $og_image;
	}

	// Update Twitter data
	if (!empty($twitter_title)) {
		$seo_data['twitter']['title'] = wpseo_replace_vars(
			$twitter_title,
			$post
		);
	} else {
		$seo_data['twitter']['title'] = $seo_data['og']['title'];
	}

	if (!empty($twitter_description)) {
		$seo_data['twitter']['description'] = wpseo_replace_vars(
			$twitter_description,
			$post
		);
	} else {
		$seo_data['twitter']['description'] = $seo_data['og']['description'];
	}

	if (!empty($twitter_image_id)) {
		$twitter_image = wp_get_attachment_image_url($twitter_image_id, 'full');
	}

	if (!empty($twitter_image)) {
		$seo_data['twitter']['image'] = $twitter_image;
	} else {
		$seo_data['twitter']['image'] = $seo_data['og']['image'];
	}

	// Get breadcrumbs
	if (class_exists('WPSEO_Frontend')) {
		$seo_data['breadcrumbs'] = get_yoast_breadcrumbs($post);
	}

	// Add schema data
	if (class_exists('WPSEO_Schema')) {
		// We can't fully extract Yoast's Schema output here, but we can note
		// that Yoast handles this in its frontend output
		$seo_data['json_ld'] = [
			[
				'@context' => 'https://schema.org',
				'@type' => $post->post_type === 'page' ? 'WebPage' : 'Article',
				'headline' => $seo_data['title'],
				'description' => $seo_data['description'],
				'url' => get_permalink($post),
				'mainEntityOfPage' => get_permalink($post),
				'datePublished' => get_the_date('c', $post),
				'dateModified' => get_the_modified_date('c', $post),
			],
		];

		if (!empty($seo_data['og']['image'])) {
			$seo_data['json_ld'][0]['image'] = $seo_data['og']['image'];
		}
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

// Initialize the integration
init();
