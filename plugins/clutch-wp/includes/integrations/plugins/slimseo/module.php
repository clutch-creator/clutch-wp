<?php
/**
 * SlimSEO integration for Clutch
 */
namespace Clutch\WP\Integrations\Plugins\SlimSEO;

/**
 * Initialize the SlimSEO integration
 */
add_action('plugins_loaded', function () {
	// Check if SlimSEO is active
	if (!class_exists('SlimSEO\\Plugin')) {
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

/**
 * Filter post SEO data with SlimSEO values
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

	// Get SlimSEO meta data
	$title = get_post_meta($post->ID, 'slim_seo_title', true);
	$description = get_post_meta($post->ID, 'slim_seo_description', true);
	$canonical = get_post_meta($post->ID, 'slim_seo_canonical_url', true);
	$robots = get_post_meta($post->ID, 'slim_seo_robots', true);
	$facebook_image = get_post_meta($post->ID, 'slim_seo_facebook_image', true);
	$twitter_image = get_post_meta($post->ID, 'slim_seo_twitter_image', true);

	// Apply SlimSEO values when available
	if (!empty($title)) {
		// SlimSEO uses syntax: %%term_title%%, replace variables
		$title = slim_seo_replace_vars($title, $post);
		$seo_data['title'] = $title;
	} elseif (class_exists('SlimSEO\\MetaTags\\Title')) {
		// Use SlimSEO's title generation logic
		$slim_seo_title = new \SlimSEO\MetaTags\Title();
		$generated_title = $slim_seo_title->get_title();
		if (!empty($generated_title)) {
			$seo_data['title'] = $generated_title;
		}
	}

	if (!empty($description)) {
		$description = slim_seo_replace_vars($description, $post);
		$seo_data['description'] = $description;
	} elseif (class_exists('SlimSEO\\MetaTags\\Description')) {
		// Use SlimSEO's description generation logic
		$slim_seo_desc = new \SlimSEO\MetaTags\Description();
		$generated_desc = $slim_seo_desc->get_description();
		if (!empty($generated_desc)) {
			$seo_data['description'] = $generated_desc;
		}
	}

	if (!empty($canonical)) {
		$seo_data['canonical'] = $canonical;
	}

	// Parse robots
	if (is_array($robots)) {
		if (in_array('noindex', $robots)) {
			$seo_data['robots']['index'] = 'noindex';
		}
		if (in_array('nofollow', $robots)) {
			$seo_data['robots']['follow'] = 'nofollow';
		}

		// Handle additional robot directives
		$advanced_directives = array_filter($robots, function ($dir) {
			return !in_array($dir, ['noindex', 'nofollow', 'index', 'follow']);
		});

		if (!empty($advanced_directives)) {
			$seo_data['robots']['advanced'] = array_values(
				$advanced_directives
			);
		}
	}

	// Update OpenGraph data
	$seo_data['og']['title'] = $seo_data['title'];
	$seo_data['og']['description'] = $seo_data['description'];

	if (!empty($facebook_image)) {
		$seo_data['og']['image'] = $facebook_image;
	}

	// Update Twitter data
	$seo_data['twitter']['title'] = $seo_data['title'];
	$seo_data['twitter']['description'] = $seo_data['description'];

	if (!empty($twitter_image)) {
		$seo_data['twitter']['image'] = $twitter_image;
	} elseif (!empty($facebook_image)) {
		$seo_data['twitter']['image'] = $facebook_image;
	}

	// Get breadcrumbs
	if (class_exists('SlimSEO\\Breadcrumbs\\Frontend')) {
		$seo_data['breadcrumbs'] = get_slimseo_breadcrumbs($post);
	}

	// Add schema data
	if (class_exists('SlimSEO\\Schema\\Manager')) {
		$seo_data['json_ld'] = [
			[
				'@context' => 'https://schema.org',
				'@type' => $post->post_type === 'page' ? 'WebPage' : 'Article',
				'headline' => $seo_data['title'],
				'description' => $seo_data['description'],
				'url' => get_permalink($post),
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
 * Filter taxonomy term SEO data with SlimSEO values
 *
 * @param array    $seo_data The default SEO data
 * @param \\WP_Term $term     The taxonomy term object
 * @return array Modified SEO data
 */
function filter_taxonomy_term_seo_data($seo_data, $term)
{
	if (!$term) {
		return $seo_data;
	}
	$title = get_term_meta($term->term_id, 'slim_seo_title', true);
	$description = get_term_meta($term->term_id, 'slim_seo_description', true);
	$robots = get_term_meta($term->term_id, 'slim_seo_robots', true);
	$facebook_image = get_term_meta(
		$term->term_id,
		'slim_seo_facebook_image',
		true
	);
	$twitter_image = get_term_meta(
		$term->term_id,
		'slim_seo_twitter_image',
		true
	);

	if (!empty($title)) {
		$title = slim_seo_replace_vars($title, null, ['term' => $term]);
		$seo_data['title'] = $title;
		$seo_data['og']['title'] = $title;
		$seo_data['twitter']['title'] = $title;
	}
	if (!empty($description)) {
		$description = slim_seo_replace_vars($description, null, [
			'term' => $term,
		]);
		$seo_data['description'] = $description;
		$seo_data['og']['description'] = $description;
		$seo_data['twitter']['description'] = $description;
	}
	if (is_array($robots)) {
		if (in_array('noindex', $robots, true)) {
			$seo_data['robots']['index'] = 'noindex';
		}
		if (in_array('nofollow', $robots, true)) {
			$seo_data['robots']['follow'] = 'nofollow';
		}
		$advanced = array_filter($robots, function ($dir) {
			return !in_array(
				$dir,
				['noindex', 'nofollow', 'index', 'follow'],
				true
			);
		});
		if (!empty($advanced)) {
			$seo_data['robots']['advanced'] = array_values($advanced);
		}
	}
	if (!empty($facebook_image)) {
		$seo_data['og']['image'] = $facebook_image;
	}
	if (!empty($twitter_image)) {
		$seo_data['twitter']['image'] = $twitter_image;
	} elseif (!empty($facebook_image)) {
		$seo_data['twitter']['image'] = $facebook_image;
	}

	return $seo_data;
}

/**
 * Filter taxonomy archive SEO data with SlimSEO values
 *
 * @param array  $seo_data     The default SEO data
 * @param string $taxonomy     Taxonomy slug
 * @param object $taxonomy_obj Taxonomy object
 * @return array Modified SEO data
 */
function filter_taxonomy_archive_seo_data($seo_data, $taxonomy, $taxonomy_obj)
{
	$options = get_option('slim_seo');
	if (empty($options)) {
		return $seo_data;
	}
	$key_title = "title_{$taxonomy}_archive";
	$key_desc = "description_{$taxonomy}_archive";
	$key_robots = "robots_{$taxonomy}_archive";
	if (!empty($options[$key_title])) {
		$title = slim_seo_replace_vars($options[$key_title], null, [
			'taxonomy' => $taxonomy,
		]);
		$seo_data['title'] = $title;
		$seo_data['og']['title'] = $title;
		$seo_data['twitter']['title'] = $title;
	}
	if (!empty($options[$key_desc])) {
		$desc = slim_seo_replace_vars($options[$key_desc], null, [
			'taxonomy' => $taxonomy,
		]);
		$seo_data['description'] = $desc;
		$seo_data['og']['description'] = $desc;
		$seo_data['twitter']['description'] = $desc;
	}
	if (!empty($options[$key_robots]) && is_array($options[$key_robots])) {
		if (in_array('noindex', $options[$key_robots], true)) {
			$seo_data['robots']['index'] = 'noindex';
		}
		if (in_array('nofollow', $options[$key_robots], true)) {
			$seo_data['robots']['follow'] = 'nofollow';
		}
		$adv = array_filter($options[$key_robots], function ($dir) {
			return !in_array(
				$dir,
				['noindex', 'nofollow', 'index', 'follow'],
				true
			);
		});
		if (!empty($adv)) {
			$seo_data['robots']['advanced'] = array_values($adv);
		}
	}

	return $seo_data;
}

/**
 * Filter post type archive SEO data with SlimSEO values
 *
 * @param array $seo_data The default SEO data
 * @param string $post_type Post type name
 * @param object $post_type_obj Post type object
 * @return array Modified SEO data
 */
function filter_post_type_seo_data($seo_data, $post_type, $post_type_obj)
{
	// Get SlimSEO options
	$slim_seo_options = get_option('slim_seo');

	if (!$slim_seo_options) {
		return $seo_data;
	}

	$title_key = "title_{$post_type}_archive";
	$desc_key = "description_{$post_type}_archive";
	$robots_key = "robots_{$post_type}_archive";

	// Set title
	if (!empty($slim_seo_options[$title_key])) {
		$title = slim_seo_replace_vars($slim_seo_options[$title_key], null, [
			'post_type' => $post_type,
		]);
		$seo_data['title'] = $title;
		$seo_data['og']['title'] = $title;
		$seo_data['twitter']['title'] = $title;
	}

	// Set description
	if (!empty($slim_seo_options[$desc_key])) {
		$description = slim_seo_replace_vars(
			$slim_seo_options[$desc_key],
			null,
			['post_type' => $post_type]
		);
		$seo_data['description'] = $description;
		$seo_data['og']['description'] = $description;
		$seo_data['twitter']['description'] = $description;
	}

	// Handle robots
	if (
		!empty($slim_seo_options[$robots_key]) &&
		is_array($slim_seo_options[$robots_key])
	) {
		if (in_array('noindex', $slim_seo_options[$robots_key])) {
			$seo_data['robots']['index'] = 'noindex';
		}
		if (in_array('nofollow', $slim_seo_options[$robots_key])) {
			$seo_data['robots']['follow'] = 'nofollow';
		}

		// Handle additional directives
		$advanced_directives = array_filter(
			$slim_seo_options[$robots_key],
			function ($dir) {
				return !in_array($dir, [
					'noindex',
					'nofollow',
					'index',
					'follow',
				]);
			}
		);

		if (!empty($advanced_directives)) {
			$seo_data['robots']['advanced'] = array_values(
				$advanced_directives
			);
		}
	}

	return $seo_data;
}

/**
 * Get SlimSEO breadcrumbs
 *
 * @param \WP_Post $post Post object
 * @return array Breadcrumbs data
 */
function get_slimseo_breadcrumbs($post)
{
	$breadcrumbs = [];

	// Add home
	$breadcrumbs[] = [
		'url' => home_url(),
		'text' => __('Home', 'slim-seo'),
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
			$category = $categories[0];

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

/**
 * Replace SlimSEO template variables
 *
 * @param string $string String with variables
 * @param \WP_Post|null $post Post object
 * @param array $context Additional context variables
 * @return string String with replaced variables
 */
function slim_seo_replace_vars($string, $post = null, $context = [])
{
	// Simple variable replacement logic
	$replacements = [
		'%%title%%' => $post ? $post->post_title : get_bloginfo('name'),
		'%%tagline%%' => get_bloginfo('description'),
		'%%sitename%%' => get_bloginfo('name'),
		'%%sep%%' => '-',
	];

	if ($post) {
		$replacements['%%excerpt%%'] = wp_trim_words(
			wp_strip_all_tags($post->post_content),
			30,
			'...'
		);
		$replacements['%%date%%'] = get_the_date('', $post);
		$replacements['%%modified%%'] = get_the_modified_date('', $post);
	}

	if (!empty($context['post_type'])) {
		$post_type_obj = get_post_type_object($context['post_type']);
		if ($post_type_obj) {
			$replacements['%%pt_singular%%'] =
				$post_type_obj->labels->singular_name;
			$replacements['%%pt_plural%%'] = $post_type_obj->label;
		}
	}

	return str_replace(
		array_keys($replacements),
		array_values($replacements),
		$string
	);
}
