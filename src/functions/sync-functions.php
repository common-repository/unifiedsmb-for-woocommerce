<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$connection = new UnifiedSMB\Connection;

function unifiedsmb_sync_categories() {
  global $connection;
  $response = $connection->api('GET', 'woocommerce/syncCategories', []);
  echo wp_json_encode($response);
  wp_die();
}
add_action( 'wp_ajax_unifiedsmb_sync_categories', 'unifiedsmb_sync_categories' );

function unifiedsmb_sync_attributes() {
  global $connection;
  $response = $connection->api('GET', 'woocommerce/syncAttributes', []);
  echo wp_json_encode($response);
  wp_die();
}
add_action( 'wp_ajax_unifiedsmb_sync_attributes', 'unifiedsmb_sync_attributes' );

function unifiedsmb_sync_tax_rates() {
  global $connection;
  $response = $connection->api('GET', 'woocommerce/syncTax', []);
  echo wp_json_encode($response);
  wp_die();
}
add_action( 'wp_ajax_unifiedsmb_sync_tax_rates', 'unifiedsmb_sync_tax_rates' );

function unifiedsmb_sync_payment_methods() {
  global $connection;
  $response = $connection->api('GET', 'woocommerce/syncPayments', []);
  echo wp_json_encode($response);
  wp_die();
}
add_action( 'wp_ajax_unifiedsmb_sync_payment_methods', 'unifiedsmb_sync_payment_methods' );

function unifiedsmb_sync_orders() {
  global $connection;
  $response = $connection->api('POST', 'woocommerce/syncOrders', []);
  echo wp_json_encode($response);
  wp_die();
}
add_action( 'wp_ajax_unifiedsmb_sync_orders', 'unifiedsmb_sync_orders' );

function unifiedsmb_sync_products() {
  global $connection;
  $response = $connection->api('GET', 'woocommerce/syncProducts', []);
  echo wp_json_encode($response);
  wp_die();
}
add_action( 'wp_ajax_unifiedsmb_sync_products', 'unifiedsmb_sync_products' );

function unifiedsmb_sync_all() {
  global $connection;
  $response = $connection->api('GET', 'woocommerce/syncAll', []);
  echo wp_json_encode($response);
  wp_die();
}
add_action( 'wp_ajax_unifiedsmb_sync_all', 'unifiedsmb_sync_all' );