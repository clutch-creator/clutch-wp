<?php
/**
 * This file manages cache invalidation for Clutch.
 * It includes hooks to trigger cache flushes on various WordPress events such as post updates, term changes, and user updates.
 */
namespace Clutch\WP\Cache;

if (!defined('ABSPATH')) {
	exit();
}

require_once __DIR__ . '/functions.php';
use function Clutch\WP\Websites\get_registered_websites;

function trigger_cache_invalidation($tags)
{
	$websites = get_registered_websites();

	foreach ($websites as $website) {
		$url = get_website_invalidation_url($website, $tags);
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

	trigger_cache_invalidation($tags);
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

	trigger_cache_invalidation($tags);
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

	trigger_cache_invalidation($tags);
}

function flush_cache_on_user($user_id)
{
	$user = get_userdata($user_id);
	if (!$user) {
		return;
	}

	$tags = ['users', 'users-' . $user->user_login, 'users-' . $user->ID];

	trigger_cache_invalidation($tags);
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

	trigger_cache_invalidation($tags);
}

function flush_cache_on_front_page_update($old_value, $new_value)
{
	$tags = ['front-page'];
	trigger_cache_invalidation($tags);
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
