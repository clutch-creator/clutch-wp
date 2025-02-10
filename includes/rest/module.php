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
  $post_types = get_post_types(array('public' => true), 'objects');
  $response = array();

  foreach ($post_types as $post_type) {
    $response[] = array(
      'name' => $post_type->name,
      'description' => $post_type->description,
      'label' => $post_type->label,
      'single_label' => $post_type->labels->singular_name,
      'rewrite' => $post_type->rewrite,
      'menu_icon' => $post_type->menu_icon,
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
});