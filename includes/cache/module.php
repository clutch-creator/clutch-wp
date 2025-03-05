<?php
/**
 * Adds functionality/handling for clutch support of ACF
 */
namespace Clutch\WP\Cache;

if (!defined('ABSPATH')) {
	exit();
}

function get_cache_invalidation_endpoints()
{
	$endpoints = get_option('clutch_cache_invalidation_endpoints', []);

	return $endpoints;
}

function register_cache_invalidation_endpoint($request)
{
	$params = json_decode($request->get_body(), true);
	$new_endpoint = isset($params['endpoint'])
		? esc_url_raw($params['endpoint'])
		: '';

	if (empty($new_endpoint)) {
		return new \WP_Error('invalid_endpoint', 'Invalid endpoint', [
			'status' => 400,
		]);
	}

	$endpoints = get_option('clutch_cache_invalidation_endpoints', []);
	if (!in_array($new_endpoint, $endpoints)) {
		$endpoints[] = $new_endpoint;
		update_option('clutch_cache_invalidation_endpoints', $endpoints);
	}

	return rest_ensure_response($endpoints);
}

function register_cache_invalidation_routes()
{
	register_rest_route('clutch/v1', '/cache/invalidation-endpoint', [
		'methods' => 'POST',
		'callback' => __NAMESPACE__ . '\register_cache_invalidation_endpoint',
		// 'permission_callback' => function () {
		// 	return current_user_can('manage_options');
		// },
	]);
}

add_action(
	'rest_api_init',
	__NAMESPACE__ . '\register_cache_invalidation_routes'
);

function trigger_revalidation($tags)
{
	$endpoints = get_cache_invalidation_endpoints();
	$tags_param = implode(',', array_map('urlencode', $tags));

	foreach ($endpoints as $endpoint) {
		$url = $endpoint . '?tags=' . $tags_param;
		$response = wp_remote_get($url);
		if (is_wp_error($response)) {
			continue; // Ignore errors
		}
	}
}

function flush_cache_on_post($post_id)
{
	if (
		(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ||
		wp_is_post_revision($post_id)
	) {
		return;
	}
	$post = get_post($post_id);
	if (!$post) {
		return;
	}
	$ptype_obj = get_post_type_object($post->post_type);
	$rest_base = !empty($ptype_obj->rest_base)
		? $ptype_obj->rest_base
		: $ptype_obj->name;

	$tags = [
		$rest_base,
		$rest_base . '-' . $post->post_name,
		$rest_base . '-' . $post->ID,
	];

	trigger_revalidation($tags);
}

function flush_cache_on_meta_update(
	$meta_id,
	$object_id,
	$meta_key,
	$_meta_value
) {
	flush_cache_on_post($object_id);
}

function flush_cache_on_term($term_id, $tt_id, $taxonomy)
{
	$term = get_term($term_id, $taxonomy);
	if (!$term) {
		return;
	}

	$tags = [
		$taxonomy,
		$taxonomy . '-' . $term->slug,
		$taxonomy . '-' . $term->term_id,
	];

	trigger_revalidation($tags);
}

function flush_cache_on_term_meta_update(
	$meta_id,
	$object_id,
	$meta_key,
	$_meta_value
) {
	$term = get_term($object_id);
	if (!$term) {
		return;
	}

	$tags = [
		$term->taxonomy,
		$term->taxonomy . '-' . $term->slug,
		$term->taxonomy . '-' . $term->term_id,
	];

	trigger_revalidation($tags);
}

function flush_cache_on_user($user_id)
{
	$user = get_userdata($user_id);
	if (!$user) {
		return;
	}

	$tags = ['users', 'users-' . $user->user_login, 'users-' . $user->ID];

	trigger_revalidation($tags);
}

function flush_cache_on_user_meta_update(
	$meta_id,
	$object_id,
	$meta_key,
	$_meta_value
) {
	$user = get_userdata($object_id);
	if (!$user) {
		return;
	}

	$tags = ['users', 'users-' . $user->user_login, 'users-' . $user->ID];

	trigger_revalidation($tags);
}

function flush_cache_on_front_page_update($old_value, $new_value)
{
	$tags = ['front-page'];
	trigger_revalidation($tags);
}

// handle posts changes, regardless of type
add_action(
	'save_post',
	__NAMESPACE__ . '\flush_cache_on_post',
	CLUTCHWP_PRIORITY
);
add_action(
	'delete_post',
	__NAMESPACE__ . '\flush_cache_on_post',
	CLUTCHWP_PRIORITY
);
add_action(
	'trash_post',
	__NAMESPACE__ . '\flush_cache_on_post',
	CLUTCHWP_PRIORITY
);

// attachment changes, even though they are posts these require special handling
add_action(
	'add_attachment',
	__NAMESPACE__ . '\flush_cache_on_post',
	CLUTCHWP_PRIORITY
);
add_action(
	'attachment_updated',
	__NAMESPACE__ . '\flush_cache_on_post',
	CLUTCHWP_PRIORITY
);
add_action(
	'delete_attachment',
	__NAMESPACE__ . '\flush_cache_on_post',
	CLUTCHWP_PRIORITY
);

// handle meta changes
add_action(
	'updated_post_meta',
	__NAMESPACE__ . '\flush_cache_on_meta_update',
	CLUTCHWP_PRIORITY,
	4
);
add_action(
	'added_post_meta',
	__NAMESPACE__ . '\flush_cache_on_meta_update',
	CLUTCHWP_PRIORITY,
	4
);
add_action(
	'deleted_post_meta',
	__NAMESPACE__ . '\flush_cache_on_meta_update',
	CLUTCHWP_PRIORITY,
	4
);

// handle term changes
add_action(
	'created_term',
	__NAMESPACE__ . '\flush_cache_on_term',
	CLUTCHWP_PRIORITY,
	3
);
add_action(
	'edited_term',
	__NAMESPACE__ . '\flush_cache_on_term',
	CLUTCHWP_PRIORITY,
	3
);
add_action(
	'delete_term',
	__NAMESPACE__ . '\flush_cache_on_term',
	CLUTCHWP_PRIORITY,
	3
);

// handle term meta changes
add_action(
	'updated_term_meta',
	__NAMESPACE__ . '\flush_cache_on_term_meta_update',
	CLUTCHWP_PRIORITY,
	4
);
add_action(
	'added_term_meta',
	__NAMESPACE__ . '\flush_cache_on_term_meta_update',
	CLUTCHWP_PRIORITY,
	4
);
add_action(
	'deleted_term_meta',
	__NAMESPACE__ . '\flush_cache_on_term_meta_update',
	CLUTCHWP_PRIORITY,
	4
);

// handle user changes
add_action(
	'profile_update',
	__NAMESPACE__ . '\flush_cache_on_user',
	CLUTCHWP_PRIORITY
);
add_action(
	'user_register',
	__NAMESPACE__ . '\flush_cache_on_user',
	CLUTCHWP_PRIORITY
);
add_action(
	'delete_user',
	__NAMESPACE__ . '\flush_cache_on_user',
	CLUTCHWP_PRIORITY
);

// handle user meta changes
add_action(
	'updated_user_meta',
	__NAMESPACE__ . '\flush_cache_on_user_meta_update',
	CLUTCHWP_PRIORITY,
	4
);
add_action(
	'added_user_meta',
	__NAMESPACE__ . '\flush_cache_on_user_meta_update',
	CLUTCHWP_PRIORITY,
	4
);
add_action(
	'deleted_user_meta',
	__NAMESPACE__ . '\flush_cache_on_user_meta_update',
	CLUTCHWP_PRIORITY,
	4
);

// handle front page changes
add_action(
	'update_option_page_on_front',
	__NAMESPACE__ . '\flush_cache_on_front_page_update',
	CLUTCHWP_PRIORITY,
	2
);
