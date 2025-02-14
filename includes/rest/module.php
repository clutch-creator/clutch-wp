<?php
/**
 * Adds custom REST API endpoints that clutch uses
 */
namespace Clutch\WP\Rest;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function rest_get_info() {
  if( ! function_exists('get_plugin_data') ){
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}

  // get clutch plugin info
  $plugin_data = get_plugin_data(CLUTCHWP_FILE);

  $response = array(
    'name' => $plugin_data['Name'],
    'version' => $plugin_data['Version'],
    'uri' => $plugin_data['PluginURI'],
  );

  return new \WP_REST_Response($response);
}

function rest_get_post_types() {
  $post_types = get_post_types(array('public' => true, 'show_in_rest' => true), 'objects');
  $response = array();

  foreach ($post_types as $post_type) {
    $posts = get_posts(array(
      'post_type' => $post_type->name,
      'posts_per_page' => 1,
      'post_status' => 'publish',
    ));
    
    $response[] = array(
      'name' => $post_type->name,
      'description' => $post_type->description,
      'label' => $post_type->label,
      'singular_label' => $post_type->labels->singular_name,
      'rewrite' => $post_type->rewrite,
      'menu_icon' => $post_type->menu_icon,
      'rest_base' => $post_type->rest_base ?: $post_type->name,
      'rest_namespace' => $post_type->rest_namespace ?: 'wp/v2',
      'first_post_slug' => !empty($posts) ? $posts[0]->post_name : null,
    );
  }

  return new \WP_REST_Response($response);
}

function rest_get_taxonomies() {
  $taxonomies = get_taxonomies(
    array(
      'public' => true, 
      'show_in_rest' => true
    ), 
    'objects'
  );
  $response = array();

  foreach ($taxonomies as $taxonomy) {
    $terms = get_terms(array(
      'taxonomy' => $taxonomy->name,
      'hide_empty' => false,
      'number' => 1,
    ));

    $response[] = array(
      'name' => $taxonomy->name,
      'description' => $taxonomy->description,
      'label' => $taxonomy->label,
      'singular_label' => $taxonomy->labels->singular_name,
      'rest_base' => $taxonomy->rest_base ?: $taxonomy->name,
      'rest_namespace' => $taxonomy->rest_namespace ?: 'wp/v2',
      'first_term_slug' => (!empty($terms) && !is_wp_error($terms)) ? $terms[0]->slug : null,
    );
  }

  return new \WP_REST_Response($response);
}

add_action('rest_api_init', function () {
  register_rest_route('clutch/v1', '/info', array(
    'methods' => 'GET',
    'callback' => __NAMESPACE__ . '\\rest_get_info',
  ));

  register_rest_route('clutch/v1', '/post-types', array(
    'methods' => 'GET',
    'callback' => __NAMESPACE__ . '\\rest_get_post_types',
  ));

  register_rest_route('clutch/v1', '/taxonomies', array(
    'methods' => 'GET',
    'callback' => __NAMESPACE__ . '\\rest_get_taxonomies',
  ));
});