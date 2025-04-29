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
		],
		'permission_callback' => function () {
			return current_user_can('read_private_posts');
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

	$default_status = ['inherit', 'publish'];

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
		'order_by' => $request->get_param('order_by') ?: 'date',
		// Place-holders for the dynamic parts we will build below
		'meta_query' => [],
		'tax_query' => [],
	];

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

	return rest_ensure_response($data);
}
