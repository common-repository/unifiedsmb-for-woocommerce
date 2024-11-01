<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function unifiedsmb_check_for_woocommerce() {
  $active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
  if (!in_array('woocommerce/woocommerce.php', $active_plugins)) {
    return false;
  }
  return true;
}

function unifiedsmb_add_menu_page_function($page) {
  add_menu_page(
    'UnifiedSMB', // Custom name for the menu item
    'UnifiedSMB', // Menu text
    'manage_options',
    'unified-smb', // Unique slug for the submenu item
    $page, // Callback function to show the page
    plugin_dir_url(__FILE__) . 'UnifiedIcon.svg', // Icon
    25 // Position of the menu item
  );
}

function unifiedsmb_add_submenu_page_function($mainSlug, $title, $name, $slug, $page) {
  add_submenu_page(
    $mainSlug,
    $title,
    $name,
    'manage_options',
    $slug,
    $page,
  );
}

function unifiedsmb_add_menu_items() {
  if (unifiedsmb_check_for_woocommerce()) {
    if (unifiedsmb_check_backoffice_connection()) {
      unifiedsmb_add_menu_page_function(
        'unifiedsmb_render_home_page',
      );

      unifiedsmb_add_submenu_page_function(
        'unified-smb',
        'Unified SMB Home',
        'Home',
        'unified-smb',
        'unifiedsmb_render_home_page',
      );

      unifiedsmb_add_submenu_page_function(
        'unified-smb',
        'Unified SMB Export',
        'Export',
        'unified-smb-export',
        'unifiedsmb_render_export_page',
      );

      unifiedsmb_add_submenu_page_function(
        'unified-smb',
        'Unified SMB Settings',
        'Settings',
        'unified-smb-settings',
        'unifiedsmb_render_settings_page',
      );
    } else {
      unifiedsmb_add_menu_page_function(
        'unifiedsmb_render_connect_page',
      );
    }
  } else {
    unifiedsmb_add_menu_page_function(
      'unifiedsmb_render_check_woocommerce_page',
    );
  }
}
add_action('admin_menu', 'unifiedsmb_add_menu_items');

function unifiedsmb_render_home_page() {
  require_once 'home-page.php';
}

function unifiedsmb_render_export_page() {
  require_once 'export-page.php';
}

function unifiedsmb_render_settings_page() {
  require_once 'settings-page.php';
}

function unifiedsmb_render_check_woocommerce_page() {
  require_once 'check-woocommerce-page.php';
}

function unifiedsmb_render_connect_page() {
  require_once 'connect-backoffice-page.php';
}

function unifiedsmb_check_backoffice_connection() {
  $connection = get_transient('unifiedsmb_backoffice_connection_check');
  if (!$connection) {
    // Check if there is a connection
    $connection = new UnifiedSMB\Connection;
    $connection = $connection->check();

    // Sets the connection if one exists
    if ($connection) {
      // Sets the connection status
      set_transient('unifiedsmb_backoffice_connection_check', $connection, 10 * MINUTE_IN_SECONDS);
    }
  }
  return $connection;
}