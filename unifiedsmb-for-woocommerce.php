<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Defined hooks
register_activation_hook( __FILE__, 'unifiedsmb_plugin_activation_function' );
add_action( 'admin_notices', 'unifiedsmb_show_woocommerce_required_notice' );

/**
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 * 
 * @see              https://techdog.nl
 * 
 * Plugin Name: UnifiedSMB for WooCommerce
 * Description: Connecting UnifiedSMB to WooCommerce to sync all your products, orders and payments.
 * Version: 1.0.1
 * Requires at least: 6.3
 * Requires PHP: 8.1
 * Author: TDS
 * Author URI: https://techdog.nl
 * Text Domain: unifiedsmb-for-woocommerce
 * License: GNU General Public License v2.0
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

/**
 * For the first time when the plugin is activated it creates the following things:
 * 
 * Database tables for creating a connection between the backoffice and the webshop.
 * A user so that you can login from the backoffice to the webshop.
 * 
 * @wp-hook __FILE__
 * @return  void
 */
function unifiedsmb_plugin_activation_function() {
  global $wpdb;
  $charset_collate = $wpdb->get_charset_collate();
  $table_name = $wpdb->prefix . 'unifiedsmb_backoffice_connection';

  // Creats the required tables in the database if they don't exist yet.
  if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
    $sql = "CREATE TABLE $table_name (
      `id` int NOT NULL AUTO_INCREMENT,
      `backoffice_url` varchar(255) NOT NULL,
      `key` int NOT NULL,
      PRIMARY KEY (`id`)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta( $sql );
  }
}

/**
 * Shows a notice that tells that the woocommerce plugin needs to be installed for this plugin to work.
 * 
 * @wp-hook admin_notices
 * @return  void
 */
// 
function unifiedsmb_show_woocommerce_required_notice() {
  // Only show this notice on the plugins page.
  $screen = get_current_screen();
  if ( $screen->base !== 'plugins' && $screen->base !== 'plugin-install' ) {
    return;
  }
  // Checks if the woocommerce plugin is active.
  if (is_plugin_active( 'woocommerce/woocommerce.php' )) {
    return;
  }

  // The url to the woocommerce plugin for instalation.
  $woocommerce_url = admin_url( 'plugin-install.php?s=woocommerce&tab=search&type=term' );

  // Prepare the warning notice.
  $html_content = "
    <div class='notice notice-warning is-dismissible'>
      <h3>UnifiedSMB-plugin</h3>
      <p>
        Voordat u de UnifiedSMB-plugin kunt gebruiken moet u eerst de <a href='" . esc_url( $woocommerce_url ) . "'>WooCommerce</a> plugin installeren.
        <a href='" . esc_url( $woocommerce_url ) . "'>Klik hier</a> om naar de download te gaan.
      </p>
    </div>
  ";

  // Filter out any unwanted html tags.
  echo wp_kses(
    $html_content,
    array(
      'div' => array(
        'class' => array(),
      ),
      'h3'  => array(),
      'p'   => array(),
      'a'   => array(
        'href'  => array(),
      ),
    )
  );
}

// Initializes all the files for the plugin.
require_once 'src/js/initialize-js-scripts.php';
require_once 'src/api/delete-transients.php';
require_once 'src/functions/initialize-php-scripts.php';
require_once 'src/pages/load-pages.php';