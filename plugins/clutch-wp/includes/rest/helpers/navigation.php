<?php

/**
 * This file defines navigation extra fields for the REST API.
 */

namespace Clutch\WP\Rest;

if (!defined('ABSPATH')) {
  exit();
}

/**
 * Get navigation next/previous data for a specific post
 *
 * @param int|\WP_Post|null $post Post ID or WP_Post object. Null for current post.
 * @return array Standardized navigation data
 */
function get_post_navigation_data($post = null)
{
  setup_postdata($post);
  $next = get_adjacent_post(false, '', false);
  $previous = get_adjacent_post(false, '', true);

  return [
    'next' => $next ? [
      "id" => $next->ID,
      "slug" => $next->post_name,
      "title" => $next->post_title
    ] : null,
    'previous' => $previous ? [
      "id" => $previous->ID,
      "slug" => $previous->post_name,
      "title" => $previous->post_title
    ] : null,
  ];

  wp_reset_postdata();
}
