<?php
/**
 * This file defines custom REST API endpoints for Clutch.
 * It includes endpoints for retrieving plugin info, post types, taxonomies, and clearing cache.
 */
namespace Clutch\WP\Rest;

require_once __DIR__ . '/functions.php';

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

function get_permalink_info($url)
{
	$response = ['object_type' => 'unknown', 'details' => []];

	// Check if the URL is external
	$site_url = wp_parse_url(home_url(), PHP_URL_HOST);
	$url_host = wp_parse_url($url, PHP_URL_HOST);

	if ($site_url !== $url_host) {
		$response['object_type'] = 'external';
		return $response;
	}

	$post_id = url_to_postid($url);

	if ($post_id) {
		$post = get_post($post_id);
		$post_type = get_post_type_object($post->post_type);

		if ($post) {
			$response['object_type'] = 'post';
			$response['details'] = [
				'id' => $post->ID,
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
					'id' => $term->term_id,
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

	return $response;
}

function rest_get_permalink_info(\WP_REST_Request $request)
{
	$url = $request->get_param('url');

	$response = get_permalink_info($url);

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
		'id' => $post->ID,
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

	// @todo: ensure classes get sorted in the same order they're shown in Clutch
	usort($response, function ($a, $b) {
		return $a['id'] > $b['id'];
	});

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

function rest_clear_cache()
{
	// WPEngine cache clear
	if (class_exists('\wpecommon')) {
		/** @disregard */
		\wpecommon::purge_memcached();
		/** @disregard */
		\wpecommon::purge_varnish_cache();
	}

	// Example WP Rocket cache clear
	if (function_exists('rocket_clean_domain')) {
		/** @disregard */
		rocket_clean_domain();
	}

	// Example Varnish cache clear
	if (function_exists('purge_varnish_cache')) {
		/** @disregard */
		purge_varnish_cache();
	}

	return new \WP_REST_Response(['message' => 'Cache cleared']);
}

function rest_get_post_preview_data(\WP_REST_Request $request)
{
	$slug = $request->get_param('slug');

	// Get the post by slug
	$post = get_page_by_path($slug, OBJECT, get_post_types(['public' => true]));

	if (!$post) {
		return new \WP_REST_Response(['message' => 'Post not found'], 404);
	}

	// Replace post content with autosave content
	$revisions = wp_get_post_revisions($post->ID);

	if (!empty($revisions)) {
		$latest = array_shift($revisions); // get latest revision
		$post->post_title = $latest->post_title ?: $post->post_title;
		$post->post_content = $latest->post_content ?: $post->post_content;
		$post->post_excerpt = $latest->post_excerpt ?: $post->post_excerpt;
	}

	// Prepare REST response using the core controller
	$controller = new \WP_REST_Posts_Controller($post->post_type);
	$response = $controller->prepare_item_for_response($post, $request);

	return $response;
}

function rest_get_posts(\WP_REST_Request $request)
{
	// Validate that post_type is valid
	$post_type = $request->get_param('post_type') ?: 'post';
	if (!post_type_exists($post_type)) {
		return new \WP_Error(
			'invalid_post_type',
			__('Invalid post type.', 'textdomain'),
			['status' => 400]
		);
	}

	$default_status = 'attachment' === $post_type ? 'inherit' : 'publish';

	// ---------------------------------------------------------------------
	// 1. Basic pagination / post-type args
	// ---------------------------------------------------------------------
	$args = [
		'post_type' => $post_type,
		'post_status' => $default_status,
		'posts_per_page' => $request->get_param('per_page') ?: 10,
		'paged' => $request->get_param('page') ?: 1,
		'no_found_rows' => false,
		'ignore_sticky_posts' => true,
		'order' => strtoupper($request->get_param('order') ?: 'DESC'),
		'orderby' => $request->get_param('orderby') ?: 'date',
		// Place-holders for the dynamic parts we will build below
		'meta_query' => [],
		'tax_query' => [],
	];

	// Make sure multiple meta / tax conditions get an AND relation by default
	$args['meta_query']['relation'] = 'AND';
	$args['tax_query']['relation'] = 'AND';

	// ---------------------------------------------------------------------
	// 2. Map our "friendly" operators to WP_Query / SQL
	// ---------------------------------------------------------------------
	$operator_map = [
		'eq' => '=',
		'neq' => '!=',
		'lt' => '<',
		'lte' => '<=',
		'gt' => '>',
		'gte' => '>=',
		'like' => 'LIKE',
		'not_like' => 'NOT LIKE',
		'contains' => 'LIKE',
		'in' => 'IN',
		'nin' => 'NOT IN',
		'between' => 'BETWEEN',
		'exists' => 'EXISTS',
		'not_exists' => 'NOT EXISTS',
	];

	// Will hold custom SQL WHERE snippets when WP_Query has no parameter
	$title_where_snippets = [];
	$slug_where_snippets = [];

	$filters = $request->get_param('filter');

	if ($filters && is_array($filters)) {
		foreach ($filters as $field => $conditions) {
			if (!is_array($conditions)) {
				continue;
			}

			// ----------------------------------------------------------
			// 2.a  Handle "meta_" prefixed keys  -----------------------
			// ----------------------------------------------------------
			if (str_starts_with($field, 'meta_')) {
				$meta_key = substr($field, 5);

				foreach ($conditions as $user_operator => $raw_value) {
					if (!isset($operator_map[$user_operator])) {
						continue;
					}

					$compare = $operator_map[$user_operator];
					$value = sanitize_text_field($raw_value);

					// Convert list strings ("1,2,3") to arrays for IN / NOT IN
					if (in_array($user_operator, ['in', 'nin'], true)) {
						$value = array_map('trim', explode(',', $value));
					}

					// BETWEEN requires an array( min, max )
					if ('between' === $user_operator) {
						$tmp = array_map('trim', explode(',', $value));
						$value = [$tmp[0] ?? '', $tmp[1] ?? ''];
					}

					// For a "contains" request use LIKE and wrap the wildcards
					if ('contains' === $user_operator) {
						$value = '%' . esc_sql($value) . '%';
					}

					$args['meta_query'][] = [
						'key' => $meta_key,
						'value' => $value,
						'compare' => $compare,
						'type' => is_numeric($value) ? 'NUMERIC' : 'CHAR',
					];
				}

				continue; // done with this field
			}

			// ----------------------------------------------------------
			// 2.b  Taxonomies – "tax_" prefix OR well-known shortcuts
			//      tax_category, tax_post_tag, tax_my_custom_tax
			// ----------------------------------------------------------
			if (
				str_starts_with($field, 'tax_') ||
				in_array($field, ['categories', 'tags'], true)
			) {
				// Map shortcut names
				if ('categories' === $field) {
					$taxonomy = 'category';
				} elseif ('tags' === $field) {
					$taxonomy = 'post_tag';
				} else {
					$taxonomy = substr($field, 4);
				}

				foreach ($conditions as $user_operator => $raw_value) {
					if (!isset($operator_map[$user_operator])) {
						continue;
					}

					$operator = $operator_map[$user_operator];
					$terms = array_map('intval', explode(',', $raw_value));

					$args['tax_query'][] = [
						'taxonomy' => $taxonomy,
						'field' => 'term_id',
						'terms' => $terms,
						'operator' => $operator, // IN, NOT IN, etc.
					];
				}

				continue;
			}

			// ----------------------------------------------------------
			// 2.c  Core WP_Query params (author, status, date, …)
			// ----------------------------------------------------------
			switch ($field) {
				case 'author':
					foreach ($conditions as $user_operator => $raw_value) {
						$authors = array_map(
							'intval',
							explode(',', $raw_value)
						);
						if (
							'eq' === $user_operator ||
							'in' === $user_operator
						) {
							$args['author__in'] = $authors;
						} elseif (
							'neq' === $user_operator ||
							'nin' === $user_operator
						) {
							$args['author__not_in'] = $authors;
						}
					}
					break;

				case 'status':
					foreach ($conditions as $user_operator => $status) {
						if ('eq' === $user_operator) {
							$args['post_status'] = sanitize_key($status);
						}
					}
					break;

				case 'date':
					foreach ($conditions as $user_operator => $raw_value) {
						$compare = $operator_map[$user_operator] ?? '=';
						$args['date_query'][] = [
							'column' => 'post_date',
							'compare' => $compare,
							'value' => sanitize_text_field($raw_value),
						];
					}
					break;

				// --------------------------------------------------
				// 2.d  TITLE & SLUG  (no native WP_Query support
				//      for >, <, LIKE, …) – build WHERE snippets
				// --------------------------------------------------
				case 'title':
					foreach ($conditions as $user_operator => $raw_value) {
						if (!isset($operator_map[$user_operator])) {
							continue;
						}
						$value = esc_sql($raw_value);

						if (
							'contains' === $user_operator ||
							'like' === $user_operator
						) {
							$title_where_snippets[] = $GLOBALS['wpdb']->prepare(
								"{$GLOBALS['wpdb']->posts}.post_title LIKE %s",
								'%' . $GLOBALS['wpdb']->esc_like($value) . '%'
							);
						} elseif (
							'in' === $user_operator ||
							'nin' === $user_operator
						) {
							$list = array_map(
								[$GLOBALS['wpdb'], 'prepare'],
								array_fill(0, count(explode(',', $value)), '%s')
							);
							$list = implode(',', $list);
							$op = 'in' === $user_operator ? 'IN' : 'NOT IN';
							$title_where_snippets[] = "{$GLOBALS['wpdb']->posts}.post_title {$op} ( {$list} )";
						} else {
							$compare = $operator_map[$user_operator];
							$title_where_snippets[] = $GLOBALS['wpdb']->prepare(
								"{$GLOBALS['wpdb']->posts}.post_title {$compare} %s",
								$value
							);
						}
					}
					break;

				case 'slug':
					foreach ($conditions as $user_operator => $raw_value) {
						$escaped = esc_sql($raw_value);

						if ('eq' === $user_operator) {
							$args['name'] = $escaped;
						} elseif (
							'in' === $user_operator ||
							'nin' === $user_operator
						) {
							$list = array_map(
								'sanitize_title',
								explode(',', $escaped)
							);
							if ('in' === $user_operator) {
								$args['post_name__in'] = $list;
							} else {
								$placeholders = implode(
									',',
									array_fill(0, count($list), '%s')
								);
								$slug_where_snippets[] = $GLOBALS[
									'wpdb'
								]->prepare(
									"{$GLOBALS['wpdb']->posts}.post_name NOT IN ( {$placeholders} )",
									$list
								);
							}
						} elseif (
							'contains' === $user_operator ||
							'like' === $user_operator
						) {
							$slug_where_snippets[] = $GLOBALS['wpdb']->prepare(
								"{$GLOBALS['wpdb']->posts}.post_name LIKE %s",
								'%' . $GLOBALS['wpdb']->esc_like($escaped) . '%'
							);
						}
					}
					break;

				default:
					/*  Unknown field – throw an error here. */
					return new \WP_Error(
						'invalid_field',
						'Invalid field: ' . $field,
						['status' => 400]
					);
					break;
			}
		}
	}

	// ---------------------------------------------------------------------
	// 3. Inject the extra where clauses (title / slug) if we have any
	// ---------------------------------------------------------------------
	if ($title_where_snippets || $slug_where_snippets) {
		add_filter(
			'posts_where',
			$dynamic_where = function ($where) use (
				$title_where_snippets,
				$slug_where_snippets
			) {
				if ($title_where_snippets) {
					$where .=
						' AND ( ' .
						implode(' AND ', $title_where_snippets) .
						' ) ';
				}
				if ($slug_where_snippets) {
					$where .=
						' AND ( ' .
						implode(' AND ', $slug_where_snippets) .
						' ) ';
				}
				return $where;
			},
			10,
			1
		);
	}

	// ---------------------------------------------------------------------
	// 4. Run the query
	// ---------------------------------------------------------------------
	$query = new \WP_Query($args);

	// Remove the dynamic where filter immediately to avoid side effects
	if (isset($dynamic_where)) {
		remove_filter('posts_where', $dynamic_where);
	}

	// ---------------------------------------------------------------------
	// 5. Build the REST response
	// ---------------------------------------------------------------------
	$data = [];

	// Attachments need their own controller
	if ('attachment' === $post_type) {
		$controller = new \WP_REST_Attachments_Controller('attachment');
	} else {
		$controller = new \WP_REST_Posts_Controller($post_type);
	}

	foreach ($query->posts as $post) {
		$response = $controller->prepare_item_for_response($post, $request);
		$response_data = $controller->prepare_response_for_collection(
			$response
		);

		$data[] = prepare_post_for_rest($post->ID, $response_data);
	}

	$response = [
		'posts' => $data,
		'total_count' => (int) $query->found_posts,
		'total_pages' => (int) $query->max_num_pages,
	];

	return rest_ensure_response($response);
}

function rest_get_post(\WP_REST_Request $request)
{
	// ---------------------------------------------------------------------
	// 1. Read & sanitise input
	// ---------------------------------------------------------------------
	$id = absint($request->get_param('id'));
	$slug = sanitize_title($request->get_param('slug'));

	if (!$id && !$slug) {
		return new \WP_Error(
			'rest_missing_id_or_slug',
			__('You must specify either “id” or “slug”.', 'textdomain'),
			['status' => 400]
		);
	}

	// ---------------------------------------------------------------------
	// 2. Build a query to fetch the post
	// ---------------------------------------------------------------------
	$args = [
		'post_type' => 'any',
		'posts_per_page' => 1,
		'post_status' => ['inherit', 'publish'],
	];

	if ($id) {
		$args['p'] = $id;
	} else {
		$args['name'] = $slug;
	}

	$q = new \WP_Query($args);

	if (!$q->have_posts()) {
		return new \WP_Error(
			'rest_post_not_found',
			__('Post not found.', 'textdomain'),
			['status' => 404]
		);
	}

	$post = $q->posts[0];

	// ---------------------------------------------------------------------
	// 3. Let the built-in controller create the response
	// ---------------------------------------------------------------------
	$post_type = $post->post_type;

	// Attachments need their own controller
	if ('attachment' === $post_type) {
		$controller = new \WP_REST_Attachments_Controller('attachment');
	} else {
		$controller = new \WP_REST_Posts_Controller($post_type);
	}

	// Prepare the single item
	$response = $controller
		->prepare_item_for_response($post, $request)
		->get_data();
	$data = prepare_post_for_rest($post->ID, $response);

	return rest_ensure_response($data);
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

	register_rest_route('clutch/v1', '/clear-cache', [
		'methods' => 'POST',
		'callback' => __NAMESPACE__ . '\\rest_clear_cache',
	]);

	register_rest_route('clutch/v1', '/preview/(?P<slug>[a-zA-Z0-9-_]+)', [
		'methods' => 'GET',
		'callback' => __NAMESPACE__ . '\\rest_get_post_preview_data',
		'permission_callback' => function () {
			return current_user_can('read_private_posts');
		},
	]);

	register_rest_route('clutch/v1', '/posts', [
		'methods' => 'GET',
		'callback' => __NAMESPACE__ . '\\rest_get_posts',
		'args' => [
			'post_type' => [
				'description' => 'Filter by post type',
				'type' => 'string',
			],
			'per_page' => [
				'description' => 'Number of posts per page',
				'type' => 'integer',
			],
			'page' => [
				'description' => 'Current page of the collection',
				'type' => 'integer',
			],
			'filter' => [
				'description' => 'Filters to apply to the query',
				'type' => 'object',
				'additionalProperties' => [
					'type' => 'object',
					'additionalProperties' => [
						'type' => ['string', 'number', 'boolean'],
					],
				],
			],
			'order' => [
				'description' => 'Order of the posts (asc or desc)',
				'type' => 'string',
				'enum' => ['asc', 'desc'],
			],
			'orderby' => [
				'description' => 'Field to order posts by',
				'type' => 'string',
			],
		],
	]);

	register_rest_route('clutch/v1', '/post', [
		'methods' => 'GET',
		'callback' => __NAMESPACE__ . '\\rest_get_post',
		'args' => [
			'id' => [
				'description' => 'The ID of the post',
				'type' => 'integer',
				'required' => false,
			],
			'slug' => [
				'description' => 'The slug of the post',
				'type' => 'string',
				'required' => false,
			],
		],
	]);
});
