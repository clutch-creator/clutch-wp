<?php
/**
 * This file defines seo field on different objects for the REST API.
 */

namespace Clutch\WP\Rest;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Get SEO data for a specific post
 *
 * @param int|\WP_Post|null $post Post ID or WP_Post object. Null for current post.
 * @return array Standardized SEO data
 */
function get_post_seo_data($post = null)
{
	$post = get_post($post);

	if (!$post) {
		return get_default_seo_data();
	}

	// Start with default values based on the post
	$permalink = get_permalink($post->ID);
	$image = get_post_thumbnail_id($post->ID)
		? wp_get_attachment_image_url(get_post_thumbnail_id($post->ID), 'large')
		: '';

	$seo_data = [
		'title' => $post->post_title,
		'description' => wp_trim_words(
			wp_strip_all_tags($post->post_content),
			30,
			'...'
		),
		'canonical' => $permalink,
		'og' => [
			'title' => $post->post_title,
			'description' => wp_trim_words(
				wp_strip_all_tags($post->post_content),
				30,
				'...'
			),
			'image' => $image,
		],
		'twitter' => [
			'title' => $post->post_title,
			'description' => wp_trim_words(
				wp_strip_all_tags($post->post_content),
				30,
				'...'
			),
			'image' => $image,
		],
		'robots' => [
			'index' => 'index',
			'follow' => 'follow',
			'advanced' => [],
		],
		'breadcrumbs' => [
			[
				'url' => home_url(),
				'text' => get_bloginfo('name'),
			],
			[
				'url' => $permalink,
				'text' => $post->post_title,
			],
		],
		'schema' => generate_default_jsonld($post),
	];

	// Allow plugins to modify SEO data through filter
	return apply_filters('clutch/prepare_post_seo', $seo_data, $post);
}

/**
 * Get SEO data for a post type archive
 *
 * @param string $post_type Post type name
 * @return array Standardized SEO data
 */
function get_post_type_seo_data($post_type)
{
	$post_type_obj = get_post_type_object($post_type);

	if (!$post_type_obj) {
		return get_default_seo_data();
	}

	$archive_url = get_post_type_archive_link($post_type);
	$archive_title = $post_type_obj->label;

	$seo_data = [
		'title' => $archive_title,
		'description' => $post_type_obj->description
			? $post_type_obj->description
			: get_bloginfo('description'),
		'canonical' => $archive_url,
		'og' => [
			'title' => $archive_title,
			'description' => $post_type_obj->description
				? $post_type_obj->description
				: get_bloginfo('description'),
			'image' => '',
		],
		'twitter' => [
			'title' => $archive_title,
			'description' => $post_type_obj->description
				? $post_type_obj->description
				: get_bloginfo('description'),
			'image' => '',
		],
		'robots' => [
			'index' => 'index',
			'follow' => 'follow',
		],
		'breadcrumbs' => [
			[
				'url' => home_url(),
				'text' => get_bloginfo('name'),
			],
			[
				'url' => $archive_url,
				'text' => $archive_title,
			],
		],
		'schema' => [
			'@context' => 'https://schema.org',
			'@type' => 'CollectionPage',
			'name' => $archive_title,
			'description' => $post_type_obj->description
				? $post_type_obj->description
				: get_bloginfo('description'),
			'url' => $archive_url,
		],
	];

	// Allow plugins to modify SEO data through filter
	return apply_filters(
		'clutch/prepare_post_type_seo',
		$seo_data,
		$post_type,
		$post_type_obj
	);
}

/**
 * Get default SEO data (fallback)
 *
 * @return array Standardized SEO data
 */
function get_default_seo_data()
{
	$site_name = get_bloginfo('name');
	$site_description = get_bloginfo('description');

	return [
		'title' => $site_name,
		'description' => $site_description,
		'canonical' => home_url(),
		'og' => [
			'title' => $site_name,
			'description' => $site_description,
			'image' => '',
		],
		'twitter' => [
			'title' => $site_name,
			'description' => $site_description,
			'image' => '',
		],
		'robots' => [
			'index' => 'index',
			'follow' => 'follow',
			'advanced' => [],
		],
		'breadcrumbs' => [
			[
				'url' => home_url(),
				'text' => $site_name,
			],
		],
		'schema' => [
			[
				'@context' => 'https://schema.org',
				'@type' => 'WebSite',
				'name' => $site_name,
				'description' => $site_description,
				'url' => home_url(),
			],
		],
	];
}

/**
 * Generate default JSON-LD for a post
 *
 * @param \WP_Post $post Post object
 * @return array JSON-LD data
 */
function generate_default_jsonld($post)
{
	$permalink = get_permalink($post->ID);
	$image = get_post_thumbnail_id($post->ID)
		? wp_get_attachment_image_url(get_post_thumbnail_id($post->ID), 'large')
		: '';

	$schema = [
		'@context' => 'https://schema.org',
		'@type' => $post->post_type === 'page' ? 'WebPage' : 'Article',
		'headline' => $post->post_title,
		'url' => $permalink,
		'datePublished' => get_the_date('c', $post),
		'dateModified' => get_the_modified_date('c', $post),
	];

	// Add image if available
	if ($image) {
		$schema[0]['image'] = $image;
	}

	// Add author information
	$author = get_the_author_meta('display_name', $post->post_author);
	if ($author) {
		$schema[0]['author'] = [
			'@type' => 'Person',
			'name' => $author,
		];
	}

	return $schema;
}
