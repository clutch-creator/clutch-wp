<?php
/**
 * This file defines custom REST API endpoints for Clutch.
 * It includes endpoints for retrieving plugin info, post types, taxonomies, and clearing cache.
 */
namespace Clutch\WP\Rest;

add_action('rest_api_init', function () {
	register_rest_route('clutch/v1', '/post-types', [
		'methods' => 'GET',
		'callback' => __NAMESPACE__ . '\\rest_get_post_types',
		'permission_callback' => function () {
			return current_user_can('read_private_posts');
		},
	]);

	register_rest_route('clutch/v1', '/post-type/(?P<name>[a-zA-Z0-9_-]+)', [
		'methods' => 'GET',
		'callback' => __NAMESPACE__ . '\\rest_get_post_type',
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
			'order_by' => [
				'description' => 'Field to order posts by',
				'type' => 'string',
			],
			'seo' => [
				'description' => 'Include SEO data for posts',
				'type' => 'boolean',
			],
		],
		'permission_callback' => function () {
			return current_user_can('read_private_posts');
		},
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
			'seo' => [
				'description' => 'Include SEO data for the post',
				'type' => 'boolean',
			],
		],
		'permission_callback' => function () {
			return current_user_can('read_private_posts');
		},
	]);

	register_rest_route('clutch/v1', '/post', [
		'methods' => 'POST',
		'callback' => __NAMESPACE__ . '\\rest_create_post',
		'args' => [
			'post_type' => [
				'description' => 'The post type',
				'type' => 'string',
				'default' => 'post',
				'required' => false,
			],
			'title' => [
				'description' => 'The post title',
				'type' => 'string',
				'required' => true,
				'validate_callback' => function ($param, $request, $key) {
					return !empty(trim($param));
				},
			],
			'content' => [
				'description' => 'The post content',
				'type' => 'string',
				'default' => '',
			],
			'excerpt' => [
				'description' => 'The post excerpt',
				'type' => 'string',
				'default' => '',
			],
			'status' => [
				'description' => 'The post status',
				'type' => 'string',
				'enum' => ['publish', 'draft', 'pending', 'private'],
				'default' => 'draft',
			],
			'author' => [
				'description' => 'The post author ID',
				'type' => 'integer',
				'default' => 0,
			],
			'featured_media' => [
				'description' => 'The featured media attachment ID',
				'type' => 'integer',
				'default' => 0,
			],
			'slug' => [
				'description' => 'The post slug',
				'type' => 'string',
				'default' => '',
			],
			'meta' => [
				'description' => 'Meta fields',
				'type' => 'object',
				'default' => [],
			],
			'acf' => [
				'description' => 'ACF fields',
				'type' => 'object',
				'default' => [],
			],
			'meta_box' => [
				'description' => 'Meta Box fields',
				'type' => 'object',
				'default' => [],
			],
			'jetengine' => [
				'description' => 'JetEngine fields',
				'type' => 'object',
				'default' => [],
			],
			'taxonomies' => [
				'description' => 'Taxonomy terms',
				'type' => 'object',
				'default' => [],
			],
		],
		'permission_callback' => function () {
			return current_user_can('edit_posts');
		},
	]);
});

/**
 * Retrieves a list of public post types that are exposed in the REST API.
 *
 * @return \WP_REST_Response A REST response containing post type data.
 */
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

		$post_type_data = [
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

		$response[] = $post_type_data;
	}

	return new \WP_REST_Response($response);
}

/**
 * Retrieves information about a specific post type.
 *
 * @param \WP_REST_Request $request The REST API request object.
 * @return \WP_REST_Response|\WP_Error A REST response containing post type data or an error.
 */
function rest_get_post_type(\WP_REST_Request $request)
{
	$post_type_name = $request->get_param('name');

	if (!post_type_exists($post_type_name)) {
		return new \WP_Error(
			'invalid_post_type',
			__('Invalid post type.', 'textdomain'),
			['status' => 404]
		);
	}

	$post_type = get_post_type_object($post_type_name);

	$posts = get_posts([
		'post_type' => $post_type->name,
		'posts_per_page' => 1,
		'post_status' => 'publish',
	]);

	$response = [
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

	return new \WP_REST_Response($response);
}

/**
 * Retrieves a paginated list of posts for a given post type.
 *
 * @param \WP_REST_Request $request The REST API request object.
 * @return \WP_REST_Response|\WP_Error A REST response containing post data or an error.
 */
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

	$include_seo = filter_var(
		$request->get_param('seo'),
		FILTER_VALIDATE_BOOLEAN
	);

	$default_status = ['inherit', 'publish'];
	$order_by = $request->get_param('order_by') ?: 'date';

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
		'orderby' => $order_by,
		// Place-holders for the dynamic parts we will build below
		'meta_query' => [],
		'tax_query' => [],
	];

	if (str_starts_with($order_by, 'meta_')) {
		$meta_key = substr($order_by, 5);
		$args['orderby'] = 'meta_value_num';
		$args['meta_key'] = $meta_key;

		// calculate the meta type
		$registered = get_post_type_meta_fields_types($post_type);

		$meta_type = 'string';
		if (isset($registered[$meta_key])) {
			$meta_type = $registered[$meta_key] ?: 'string';
		}

		if ($meta_type === 'integer' || $meta_type === 'number') {
			$args['meta_type'] = 'NUMERIC';
		}
	}

	// Check if drafts should be included
	$include_drafts = $request->get_header('X-Draft-Mode');
	if ($include_drafts && 'true' === strtolower($include_drafts)) {
		$args['post_status'][] = 'draft';
	}

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
						$value = esc_sql($value);
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
			// ----------------------------------------------------------
			if (str_starts_with($field, 'tax_')) {
				$taxonomy = substr($field, 4);

				foreach ($conditions as $user_operator => $raw_value) {
					if (!isset($operator_map[$user_operator])) {
						continue;
					}

					$operator = $operator_map[$user_operator];
					$terms = array_map('trim', explode(',', $raw_value));

					// Determine if terms are slugs or IDs
					$field_type = array_reduce(
						$terms,
						function ($carry, $term) {
							return $carry && is_numeric($term);
						},
						true
					)
						? 'term_id'
						: 'slug';

					$args['tax_query'][] = [
						'taxonomy' => $taxonomy,
						'field' => $field_type,
						'terms' =>
							$field_type === 'term_id'
								? array_map('intval', $terms)
								: $terms,
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

				case 'modified':
					foreach ($conditions as $user_operator => $raw_value) {
						$compare = $operator_map[$user_operator] ?? '=';
						$args['date_query'][] = [
							'column' => 'post_modified',
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

				case 'id':
					foreach ($conditions as $user_operator => $raw_value) {
						$ids = array_map('intval', explode(',', $raw_value));
						switch ($user_operator) {
							case 'eq':
								$args['post__in'] = $ids;
								break;
							case 'in':
								$args['post__in'] = $ids;
								break;
							case 'neq':
								$args['post__not_in'] = $ids;
								break;
							case 'nin':
								$args['post__not_in'] = $ids;
								break;
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

		$post_data = prepare_post_for_rest($post->ID, $response_data);
		$data[] = $post_data;
	}

	$response = [
		'posts' => $data,
		'total_count' => (int) $query->found_posts,
		'total_pages' => (int) $query->max_num_pages,
	];

	if ($include_seo) {
		$response['seo'] = get_post_type_seo_data($post_type);
	}

	return rest_ensure_response($response);
}

/**
 * Retrieves a single post by ID or slug.
 *
 * @param \WP_REST_Request $request The REST API request object.
 * @return \WP_REST_Response|\WP_Error A REST response containing post data or an error.
 */
function rest_get_post(\WP_REST_Request $request)
{
	// ---------------------------------------------------------------------
	// 1. Read & sanitise input
	// ---------------------------------------------------------------------
	$id = absint($request->get_param('id'));
	$slug = sanitize_title($request->get_param('slug'));
	$include_seo = filter_var(
		$request->get_param('seo'),
		FILTER_VALIDATE_BOOLEAN
	);

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

	// Check if drafts should be included
	$draft_mode = $request->get_header('X-Draft-Mode');
	if ($draft_mode && 'true' === strtolower($draft_mode)) {
		$args['post_status'][] = 'draft';
	}

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

	/* ---------------------------------------------------------------
	 * If “draft-mode”: swap in the most recent revision
	 * ------------------------------------------------------------- */
	if ($draft_mode && current_user_can('edit_post', $post->ID)) {
		$revisions = wp_get_post_revisions($post->ID, [
			'orderby' => 'ID',
			'order' => 'DESC',
			'posts_per_page' => 1,
		]);

		// get newest revision
		if (!empty($revisions)) {
			$post = array_shift($revisions);
		}
	}

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

	// Add SEO data if requested
	if ($include_seo) {
		$data['seo'] = get_post_seo_data($post);
	}

	return rest_ensure_response($data);
}

/**
 * Creates a new post.
 *
 * @param \WP_REST_Request $request The REST API request object.
 * @return \WP_REST_Response|\WP_Error A REST response containing the created post data or an error.
 */
function rest_create_post(\WP_REST_Request $request)
{
	// ---------------------------------------------------------------------
	// 1. Validate and sanitize input
	// ---------------------------------------------------------------------
	$post_type = sanitize_key($request->get_param('post_type') ?: 'post');
	$title = sanitize_text_field($request->get_param('title'));
	$content = $request->get_param('content');
	$excerpt = $request->get_param('excerpt');
	$status = sanitize_key($request->get_param('status') ?: 'draft');
	$author_id = absint($request->get_param('author'));
	$featured_media = absint($request->get_param('featured_media'));
	$slug = sanitize_title($request->get_param('slug'));

	// Validate required fields
	if (empty($title)) {
		return new \WP_Error(
			'rest_missing_title',
			__('The post title is required.', 'textdomain'),
			['status' => 400]
		);
	}

	// Validate post type
	if (!post_type_exists($post_type)) {
		return new \WP_Error(
			'invalid_post_type',
			__('Invalid post type.', 'textdomain'),
			['status' => 400]
		);
	}

	// ---------------------------------------------------------------------
	// 2. Prepare the post data
	// ---------------------------------------------------------------------
	$post_data = [
		'post_type' => $post_type,
		'post_title' => $title,
		'post_content' => $content,
		'post_excerpt' => $excerpt,
		'post_status' => $status,
		'post_author' => $author_id,
		'comment_status' => 'closed', // Disable comments by default
		'ping_status' => 'closed', // Disable pings by default
		'post_name' => $slug,
		// 'menu_order' => 0, // Default is 0
		// 'post_parent' => 0, // Default is 0
	];

	// ---------------------------------------------------------------------
	// 3. Insert the post into the database
	// ---------------------------------------------------------------------
	$post_id = wp_insert_post($post_data);

	if (is_wp_error($post_id)) {
		return $post_id; // Return the error
	}

	// ---------------------------------------------------------------------
	// 4. Set featured media if provided
	// ---------------------------------------------------------------------
	if ($featured_media) {
		set_post_thumbnail($post_id, $featured_media);
	}

	// ---------------------------------------------------------------------
	// 5. Handle custom fields with integration-specific validation and processing
	// ---------------------------------------------------------------------

	// Process each integration's fields (includes validation and processing)
	$processed_fields = apply_filters(
		'clutch/process_post_fields_before_save',
		[],
		$request,
		$post_id
	);

	// Check if any integration reported validation errors
	if (!empty($processed_fields['_errors'])) {
		// If there are validation errors, delete the post and return errors
		wp_delete_post($post_id, true);
		return new \WP_Error(
			'rest_field_validation_failed',
			__('Field validation failed.', 'textdomain'),
			['status' => 400, 'errors' => $processed_fields['_errors']]
		);
	}

	// Handle standard meta fields
	$meta_fields = $request->get_param('meta');
	if (!empty($meta_fields) && is_array($meta_fields)) {
		foreach ($meta_fields as $meta_key => $meta_value) {
			update_post_meta($post_id, $meta_key, $meta_value);
		}
	}

	// Handle taxonomies
	$taxonomies = $request->get_param('taxonomies');
	if (!empty($taxonomies) && is_array($taxonomies)) {
		foreach ($taxonomies as $taxonomy => $terms) {
			if (!taxonomy_exists($taxonomy)) {
				continue;
			}

			// Handle both array and string values
			if (is_array($terms)) {
				$term_list = array_map('trim', $terms);
			} else {
				$term_list = array_map('trim', explode(',', $terms));
			}

			// Determine if terms are slugs or IDs
			$is_numeric = array_reduce(
				$term_list,
				function ($carry, $term) {
					return $carry && is_numeric($term);
				},
				true
			);

			// Set terms for the post
			wp_set_object_terms(
				$post_id,
				$is_numeric ? array_map('intval', $term_list) : $term_list,
				$taxonomy,
				false // Don't append, set terms exclusively
			);
		}
	}

	// Allow integrations to perform additional processing after fields are saved
	do_action('clutch/post_fields_saved', $post_id, $request);

	// ---------------------------------------------------------------------
	// 6. Prepare and return the response
	// ---------------------------------------------------------------------
	$response = rest_get_post(
		new \WP_REST_Request('GET', '/clutch/v1/post', ['id' => $post_id])
	);

	return rest_ensure_response($response);
}
