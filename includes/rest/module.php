<?php
/**
 * Adds custom REST API endpoints that clutch uses
 */
namespace Clutch\WP\Rest;

if (!defined('ABSPATH')) {
	exit();
}

function rest_get_info()
{
	if (!function_exists('get_plugin_data')) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	// get clutch plugin info
	$plugin_data = get_plugin_data(CLUTCHWP_FILE);

	$response = [
		'name' => $plugin_data['Name'],
		'version' => $plugin_data['Version'],
		'uri' => $plugin_data['PluginURI'],
	];

	return new \WP_REST_Response($response);
}

function rest_get_post_types()
{
	$post_types = get_post_types(
		['public' => true, 'show_in_rest' => true],
		'objects'
	);
	$response = [];

	foreach ($post_types as $post_type) {
		$posts = get_posts([
			'post_type' => $post_type->name,
			'posts_per_page' => 1,
			'post_status' => 'publish',
		]);

		$response[] = [
			'name' => $post_type->name,
			'description' => $post_type->description,
			'label' => $post_type->label,
			'singular_label' => $post_type->labels->singular_name,
			'rewrite' => $post_type->rewrite,
			'menu_icon' => $post_type->menu_icon,
			'rest_base' => $post_type->rest_base ?: $post_type->name,
			'rest_namespace' => $post_type->rest_namespace ?: 'wp/v2',
			'first_post_slug' => !empty($posts) ? $posts[0]->post_name : null,
		];
	}

	return new \WP_REST_Response($response);
}

function rest_get_taxonomies()
{
	$taxonomies = get_taxonomies(
		[
			'public' => true,
			'show_in_rest' => true,
		],
		'objects'
	);
	$response = [];

	foreach ($taxonomies as $taxonomy) {
		$terms = get_terms([
			'taxonomy' => $taxonomy->name,
			'hide_empty' => false,
			'number' => 1,
		]);

		$response[] = [
			'name' => $taxonomy->name,
			'description' => $taxonomy->description,
			'label' => $taxonomy->label,
			'singular_label' => $taxonomy->labels->singular_name,
			'rest_base' => $taxonomy->rest_base ?: $taxonomy->name,
			'rest_namespace' => $taxonomy->rest_namespace ?: 'wp/v2',
			'first_term_slug' =>
				!empty($terms) && !is_wp_error($terms) ? $terms[0]->slug : null,
		];
	}

	return new \WP_REST_Response($response);
}

function rest_get_permalink_info(\WP_REST_Request $request)
{
	$url = $request->get_param('url');
	$response = ['object_type' => 'unknown', 'details' => []];
	$post_id = url_to_postid($url);

	if ($post_id) {
		$post = get_post($post_id);
		$post_type = get_post_type_object($post->post_type);

		if ($post) {
			$response['object_type'] = 'post';
			$response['details'] = [
				'ID' => $post->ID,
				'name' => $post->post_name,
				'rest_base' => $post_type->rest_base ?: $post_type->name,
				'rest_namespace' => $post_type->rest_namespace ?: 'wp/v2',
			];
		}
	} else {
		$path = wp_parse_url($url, PHP_URL_PATH);
		$slug = end(explode('/', trim($path, '/')));
		$taxonomies = get_taxonomies(['public' => true], 'objects');

		$foundTerm = false;
		foreach ($taxonomies as $taxonomy) {
			$term = get_term_by('slug', $slug, $taxonomy->name);

			if ($term) {
				$foundTerm = true;
				$response['object_type'] = 'taxonomy_term';
				$response['details'] = [
					'ID' => $term->term_id,
					'name' => $term->name,
					'taxonomy_name' => $taxonomy->name,
					'rest_base' => $taxonomy->rest_base ?: $taxonomy->name,
					'rest_namespace' => $taxonomy->rest_namespace ?: 'wp/v2',
				];
				break;
			}
		}

		if (!$foundTerm) {
			foreach ($taxonomies as $taxonomy) {
				$rewrite_slug = isset($taxonomy->rewrite['slug'])
					? $taxonomy->rewrite['slug']
					: '';

				if ($slug === $rewrite_slug) {
					$response['object_type'] = 'taxonomy';
					$response['details'] = [
						'name' => $taxonomy->name,
						'rest_base' => $taxonomy->rest_base ?: $taxonomy->name,
						'rest_namespace' =>
							$taxonomy->rest_namespace ?: 'wp/v2',
					];
					break;
				}
			}
		}
	}

	// lastly, check if it's a date archive
	if ($response['object_type'] === 'unknown') {
		$parts = explode('/', trim($path, '/'));
		$year = isset($parts[0]) ? (int) $parts[0] : null;
		$month = isset($parts[1]) ? (int) $parts[1] : null;
		$day = isset($parts[2]) ? (int) $parts[2] : null;

		if ($year >= 1000 && $year <= 9999) {
			$response['object_type'] = 'date_archive';
			$response['details'] = [
				'year' => $year,
				'month' => $month,
				'day' => $day,
			];
		}
	}

	return new \WP_REST_Response($response);
}

function rest_get_front_page()
{
	$front_page_id = get_option('page_on_front');
	if (!$front_page_id) {
		return new \WP_REST_Response(['message' => 'No front page set'], 404);
	}

	$post = get_post($front_page_id);
	if (!$post) {
		return new \WP_REST_Response(
			['message' => 'Front page not found'],
			404
		);
	}

	return new \WP_REST_Response([
		'ID' => $post->ID,
		'title' => $post->post_title,
		'slug' => $post->post_name,
	]);
}

function rest_get_acf_schema(\WP_REST_Request $request)
{
	$post_type = $request->get_param('post_type');

	if (empty($post_type)) {
		return new \WP_REST_Response(
			['message' => 'No post type provided'],
			400
		);
	}

	if (!function_exists('acf_get_field_groups')) {
		return new \WP_REST_Response(
			['message' => 'ACF plugin not active'],
			400
		);
	}

	$field_groups = acf_get_field_groups(['post_type' => $post_type]);
	$schema = [];

	foreach ($field_groups as $group) {
		$fields = acf_get_fields($group['key']);
		$field_schema = [];
		if ($fields) {
			foreach ($fields as $field) {
				$field_schema[] = [
					'type' => $field['type'],
					'name' => $field['name'],
					'label' => $field['label'],
					'taxonomy' => $field['taxonomy'] ?? null,
					'return_format' => $field['return_format'] ?? null,
				];
			}
		}
		$schema[] = [
			'group_title' => $group['title'],
			'fields' => $field_schema,
		];
	}

	return new \WP_REST_Response($schema);
}

function rest_get_block_styles()
{
	$block_styles = get_posts([
		'post_type' => 'clutch_block_styles',
		'posts_per_page' => -1,
		'post_status' => 'publish',
	]);

	$response = [];

	foreach ($block_styles as $block_style) {
		$response[] = [
			'id' => get_post_meta($block_style->ID, 'style_id', true),
			'label' => $block_style->post_title,
			'className' => get_post_meta(
				$block_style->ID,
				'style_classname',
				true
			),
			'style' => $block_style->post_content,
		];
	}

	return new \WP_REST_Response($response);
}

function rest_set_block_styles(\WP_REST_Request $request)
{
	$block_styles = $request->get_json_params();

	if (!is_array($block_styles)) {
		return new \WP_Error(
			'invalid_block_styles',
			'Block styles must be an array',
			['status' => 400]
		);
	}

	// @todo optimize by mutating the retrieved post instead of upserting
	foreach ($block_styles as $block_style) {
		// retrieve custom post for block style
		$existing_block_style = get_posts([
			'numberposts' => 1,
			'fields' => 'ids',
			'post_type' => 'clutch_block_styles',
			'meta_key' => 'style_id',
			'meta_value' => $block_style['id'],
		]);

		// create or update custom post for block style
		if (isset($block_style['id'], $block_style['className'])) {
			wp_insert_post([
				'ID' => $existing_block_style ? $existing_block_style[0] : 0,
				'post_type' => 'clutch_block_styles',
				'post_title' =>
					$block_style['label'] ?: $block_style['className'],
				'post_content' => $block_style['style'],
				'post_status' => 'publish',
				'meta_input' => [
					'style_id' => $block_style['id'],
					'style_classname' => $block_style['className'],
				],
			]);
		}
	}

	return new \WP_REST_Response(null, 200);
}

add_action('rest_api_init', function () {
	register_rest_route('clutch/v1', '/info', [
		'methods' => 'GET',
		'callback' => __NAMESPACE__ . '\\rest_get_info',
	]);

	register_rest_route('clutch/v1', '/post-types', [
		'methods' => 'GET',
		'callback' => __NAMESPACE__ . '\\rest_get_post_types',
	]);

	register_rest_route('clutch/v1', '/taxonomies', [
		'methods' => 'GET',
		'callback' => __NAMESPACE__ . '\\rest_get_taxonomies',
	]);

	register_rest_route('clutch/v1', '/permalink-info', [
		'methods' => 'GET',
		'callback' => __NAMESPACE__ . '\\rest_get_permalink_info',
	]);

	register_rest_route('clutch/v1', '/front-page', [
		'methods' => 'GET',
		'callback' => __NAMESPACE__ . '\\rest_get_front_page',
	]);

	register_rest_route('clutch/v1', '/post-acf-schema', [
		'methods' => 'GET',
		'callback' => __NAMESPACE__ . '\\rest_get_acf_schema',
	]);

	register_rest_route('clutch/v1', '/block-styles', [
		'methods' => 'GET',
		'callback' => __NAMESPACE__ . '\\rest_get_block_styles',
	]);

	register_rest_route('clutch/v1', '/block-styles', [
		'methods' => 'POST',
		'callback' => __NAMESPACE__ . '\\rest_set_block_styles',
	]);
});
