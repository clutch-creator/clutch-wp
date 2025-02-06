<?php
/**
 * Makes changes to wordpress admin, disabling some features that are not necessary when using clutch
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove all wp theme related menu pages
 */
function menu_remove_theme_pages() {
  remove_menu_page( 'themes.php' );
  remove_menu_page( 'customize.php' );
  remove_menu_page( 'widgets.php' );
  remove_menu_page( 'theme-editor.php' );
  remove_menu_page( 'theme-install.php' );
}

/**
 * Remove all wp theme related submenu pages
 */
function menu_remove_theme_submenu_pages() {
  remove_submenu_page( 'themes.php', 'theme-editor.php' );
  remove_submenu_page( 'themes.php', 'site-editor.php' );
  remove_submenu_page( 'themes.php', 'customize.php' );
  remove_submenu_page( 'themes.php', 'widgets.php' );
  remove_submenu_page( 'themes.php', 'theme-install.php' );
}

/**
 * Remove permalink page
 */
function menu_remove_permalink() {
  remove_menu_page( 'options-permalink.php' );
}

/**
 * Cleanup admin menu from all non necessary pages
 */
function menu_remove() {
  menu_remove_theme_pages();
  menu_remove_theme_submenu_pages();
  menu_remove_permalink();
}

add_action( 'admin_menu', __NAMESPACE__ . '\\menu_remove', CLUTCHWP_PRIORITY );

/**
 * Remove all wp theme related admin bar items
 */
function admin_bar_remove_theme_items( $wp_admin_bar ) {
  global $wp_admin_bar;
  
  $wp_admin_bar->remove_node( 'customize' );
  $wp_admin_bar->remove_node( 'widgets' );
  $wp_admin_bar->remove_node( 'themes' );
  $wp_admin_bar->remove_node( 'site-editor' );
}

add_action( 'wp_before_admin_bar_render', __NAMESPACE__ . '\\admin_bar_remove_theme_items', CLUTCHWP_PRIORITY );

/**
 * Redirect to dashboard if user tries to access a removed page
 */
function redirect_to_dashboard() {
  $disallowed_pages = array(
    'themes.php',
    'customize.php',
    'widgets.php',
    'theme-editor.php',
    'theme-install.php',
    'site-editor.php',
    'options-permalink.php'
  );

  $current_screen = get_current_screen();

  if ( is_object( $current_screen ) && in_array( $current_screen->id, $disallowed_pages ) ) {
    wp_redirect( admin_url() );
    exit;
  }
}

add_action( 'current_screen', __NAMESPACE__ . '\\redirect_to_dashboard', CLUTCHWP_PRIORITY );
