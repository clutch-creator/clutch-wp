<?php
/**
 * This file defines REST API routes for taxonomies and terms in Clutch.
 *
 * @package Clutch\WP\Rest
 */

namespace Clutch\WP\Rest;

add_action('rest_api_init', function () {
	/* ----------  /taxonomies  ------------------------------------------ */
	register_rest_route('clutch/v1', '/taxonomies', [
		'methods' => 'GET',
		'callback' => __NAMESPACE__ . '\\rest_get_taxonomies',
	]);

	/* ----------  /terms  ------------------------------------------ */
	register_rest_route('clutch/v1', '/terms', [
		'methods' => 'GET',
		'callback' => __NAMESPACE__ . '\\rest_get_terms',
		'permission_callback' => function () {
			return true;
			// return current_user_can('read_private_posts');
		},
		'args' => [
			'taxonomy' => [
				'description' => 'Taxonomy slug',
				'type' => 'string',
			],
			'per_page' => [
				'description' => 'Items per page',
				'type' => 'integer',
			],
			'page' => [
				'description' => 'Page of the collection',
				'type' => 'integer',
			],
			'hide_empty' => [
				'description' => 'Hide empty terms',
				'type' => 'boolean',
			],
			'filter' => [
				'description' => 'Filtering object',
				'type' => 'object',
			],
			'order' => [
				'description' => 'asc | desc',
				'type' => 'string',
				'enum' => ['asc', 'desc'],
			],
			'order_by' => [
				'description' => 'Field to order by',
				'type' => 'string',
			],
		],
	]);

	/* ----------  /term  ------------------------------------------- */
	register_rest_route('clutch/v1', '/term', [
		'methods' => 'GET',
		'callback' => __NAMESPACE__ . '\\rest_get_term',
		'permission_callback' => function () {
			return current_user_can('read_private_posts');
		},
		'args' => [
			'taxonomy' => [
				'description' => 'Taxonomy slug',
				'type' => 'string',
				'required' => false,
			],
			'id' => [
				'description' => 'Term ID',
				'type' => 'integer',
				'required' => false,
			],
			'slug' => [
				'description' => 'Term slug',
				'type' => 'string',
				'required' => false,
			],
		],
	]);
});

/**
 * Retrieves a list of public taxonomies that are exposed in the REST API.
 *
 * @return \WP_REST_Response A REST response containing taxonomy data.
 */
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

/**
 * Retrieves a paginated list of terms for a given taxonomy.
 *
 * @param \WP_REST_Request $request The REST API request object.
 * @return \WP_REST_Response|\WP_Error A REST response containing term data or an error.
 */
function rest_get_terms(\WP_REST_Request $request)
{
	/* --------------------------------------------------------------- */
	/* 1. Validate & read the basic query args                         */
	/* --------------------------------------------------------------- */

	$taxonomy = $request->get_param('taxonomy') ?: 'category';

	if (!taxonomy_exists($taxonomy)) {
		return new \WP_Error(
			'invalid_taxonomy',
			__('Invalid taxonomy.', 'textdomain'),
			['status' => 400]
		);
	}

	$per_page = absint($request->get_param('per_page') ?: 10);
	$page = absint($request->get_param('page') ?: 1);
	$offset = ($page - 1) * $per_page;

	$args = [
		'taxonomy' => $taxonomy,
		'hide_empty' => filter_var(
			$request->get_param('hide_empty'),
			FILTER_VALIDATE_BOOLEAN
		),
		'orderby' => $request->get_param('order_by') ?: 'name',
		'order' => strtoupper($request->get_param('order') ?: 'ASC'),
		'number' => $per_page,
		'offset' => $offset,
		'meta_query' => ['relation' => 'AND'],
	];

	/* --------------------------------------------------------------- */
	/* 2. Map “friendly” operators to SQL                              */
	/* --------------------------------------------------------------- */
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

	/* --------------------------------------------------------------- */
	/* 3. Filters                                                      */
	/* --------------------------------------------------------------- */
	$filters = $request->get_param('filter');

	if ($filters && is_array($filters)) {
		foreach ($filters as $field => $conditions) {
			if (!is_array($conditions)) {
				continue;
			}

			/* 3.a  Term meta  --------------------------------------- */
			if (str_starts_with($field, 'meta_')) {
				$meta_key = substr($field, 5);

				foreach ($conditions as $user_operator => $raw_value) {
					if (!isset($operator_map[$user_operator])) {
						continue;
					}

					$compare = $operator_map[$user_operator];
					$value = sanitize_text_field($raw_value);

					// convert "1,2,3" to [1,2,3]
					if (in_array($user_operator, ['in', 'nin'], true)) {
						$value = array_map('trim', explode(',', $value));
					}

					if ('between' === $user_operator) {
						$tmp = array_map('trim', explode(',', $value));
						$value = [$tmp[0] ?? '', $tmp[1] ?? ''];
					}

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
				continue;
			}

			/* 3.b  Core term fields --------------------------------- */
			switch ($field) {
				case 'parent':
					foreach ($conditions as $user_operator => $value) {
						$int = absint($value);
						if ('eq' === $user_operator) {
							$args['parent'] = $int;
						} elseif ('neq' === $user_operator) {
							$args['parent__not_in'] = [$int];
						}
					}
					break;

				case 'slug':
					foreach ($conditions as $user_operator => $raw_value) {
						$slugs = array_map(
							'sanitize_title',
							explode(',', $raw_value)
						);
						if (
							'eq' === $user_operator ||
							'in' === $user_operator
						) {
							$args['slug'] = $slugs;
						} elseif (
							'neq' === $user_operator ||
							'nin' === $user_operator
						) {
							$args['slug__not_in'] = $slugs;
						} elseif ('contains' === $user_operator) {
							$args['search'] = $raw_value;
						}
					}
					break;

				case 'name':
					foreach ($conditions as $user_operator => $raw_value) {
						if (
							'contains' === $user_operator ||
							'like' === $user_operator
						) {
							$args['search'] = $raw_value;
						} elseif ('eq' === $user_operator) {
							$args['name'] = $raw_value;
						}
					}
					break;

				default:
					return new \WP_Error(
						'invalid_field',
						'Invalid field: ' . $field,
						['status' => 400]
					);
			}
		}
	}

	/* --------------------------------------------------------------- */
	/* 4. Execute the query                                            */
	/* --------------------------------------------------------------- */
	$term_query = new \WP_Term_Query($args);
	$terms = $term_query->get_terms();

	/* --------------------------------------------------------------- */
	/* 5. Build REST response                                          */
	/* --------------------------------------------------------------- */
	$controller = new \WP_REST_Terms_Controller($taxonomy);
	$data = [];

	foreach ($terms as $term) {
		$response = $controller->prepare_item_for_response($term, $request);
		$response_data = $controller->prepare_response_for_collection(
			$response
		);
		$data[] = prepare_term_for_rest($term->term_id, $response_data);
	}

	/* ------------------------------------------------------------------
	 * Get the total number of matching terms – without pagination
	 * ---------------------------------------------------------------- */
	$count_args = $args;

	// Drop pagination args
	unset($count_args['number'], $count_args['offset']);

	// Ask WP_Term_Query to only return the count
	$count_args['fields'] = 'count';

	$total_items = (int) (new \WP_Term_Query($count_args))->get_terms();
	$total_pages = $per_page ? (int) ceil($total_items / $per_page) : 1;

	return rest_ensure_response([
		'terms' => $data,
		'total_count' => (int) $total_items,
		'total_pages' => (int) $total_pages,
	]);
}

/**
 * Retrieves a single term by ID or slug.
 *
 * @param \WP_REST_Request $request The REST API request object.
 * @return \WP_REST_Response|\WP_Error A REST response containing term data or an error.
 */
function rest_get_term(\WP_REST_Request $request)
{
	$taxonomy = $request->get_param('taxonomy') ?: 'category';
	$id = absint($request->get_param('id'));
	$slug = sanitize_title($request->get_param('slug'));

	if (!taxonomy_exists($taxonomy)) {
		return new \WP_Error(
			'invalid_taxonomy',
			__('Invalid taxonomy.', 'textdomain'),
			['status' => 400]
		);
	}

	if (!$id && !$slug) {
		return new \WP_Error(
			'missing_id_or_slug',
			__('You must specify either “id” or “slug”.', 'textdomain'),
			['status' => 400]
		);
	}

	$term = $id
		? get_term_by('id', $id, $taxonomy)
		: get_term_by('slug', $slug, $taxonomy);

	if (!$term || is_wp_error($term)) {
		return new \WP_Error(
			'term_not_found',
			__('Term not found.', 'textdomain'),
			['status' => 404]
		);
	}

	$controller = new \WP_REST_Terms_Controller($taxonomy);
	$response = $controller->prepare_item_for_response($term, $request);
	$responseData = $response->get_data();

	return rest_ensure_response(
		prepare_term_for_rest($term->term_id, $responseData)
	);
}
