<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function unifiedsmb_get_current_host() {
  return str_replace(['https://', 'http://'], '', get_site_url());
}

function unifiedsmb_make_new_api_key() {
  global $wpdb;

  $description = 'Backoffice api connection';
  $user_id     = get_current_user_id();
  
  $permissions     = sanitize_text_field( 'read_write' );
  $consumer_key    = 'ck_' . wc_rand_hash();
  $consumer_secret = 'cs_' . wc_rand_hash();
  
  $wpdb->insert(
    $wpdb->prefix . 'woocommerce_api_keys',
    array(
      'user_id'         => $user_id,
      'description'     => $description,
      'permissions'     => $permissions,
      'consumer_key'    => wc_api_hash( $consumer_key ),
      'consumer_secret' => $consumer_secret,
      'truncated_key'   => substr( $consumer_key, -7 ),
    ),
    array(
      '%d',
      '%s',
      '%s',
      '%s',
      '%s',
      '%s',
    )
  );
  
  return array(
    'key_id'          => $wpdb->insert_id,
    'user_id'         => $user_id,
    'consumer_key'    => $consumer_key,
    'consumer_secret' => $consumer_secret,
    'key_permissions' => $permissions,
  );
}

function unifiedsmb_make_connection_with_backoffice() {
  // Verify nonce
  if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash ( $_POST['nonce'] ) ) , 'unifiedsmb_connection_nonce' ) || ! current_user_can('administrator') ) {
    wp_send_json_error('Security check failed', 403); // Send a JSON error response
  }

  // Checks if a url has been provided
  if ( ! isset( $_POST['url'] ) || empty( $_POST['url'] ) ) {
    wp_send_json_error('Url is required', 400);
  }

  $host = unifiedsmb_get_current_host();
  $new = unifiedsmb_make_new_api_key();
  $url = sanitize_url( $_POST['url'] );

  global $wpdb;
  $table = $wpdb->prefix . 'unifiedsmb_backoffice_connection';
  $wpdb->insert( // Inserts the generated api_key from the backoffcie in the database
    $table,
    array(
      'backoffice_url' => $url,
      'key'            => $new['key_id'],
    )
  );
  
  // The url the user will be redirected to
  $redirectUrl = "https://unifiedsmb.$url.techdogcloud.com/?type=wc&url=$host&ck=$new[consumer_key]&cs=$new[consumer_secret]";
  wp_send_json_success($redirectUrl, 200);
}
add_action( 'wp_ajax_unifiedsmb_make_connection_with_backoffice', 'unifiedsmb_make_connection_with_backoffice' );