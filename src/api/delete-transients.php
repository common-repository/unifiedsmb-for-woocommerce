<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action( 'rest_api_init', function () {
  register_rest_route( 'unifiedsmb/v1', '/delete_transient_payment_gateways', array(
    'methods'  => 'GET',
    'callback' => 'unifiedsmb_delete_transient_payment_gateways',
    'permission_callback' => 'unifiedsmb_check_permission',
  ) );

  // These following routes needs better security cause ass of nou it has none
  register_rest_route( 'unifiedsmb/v1', '/get-token', array(
    'methods' => 'POST',
    'callback' => 'unifiedsmb_get_token',
    'permission_callback' => 'unifiedsmb_check_permission',
  ) );

  register_rest_route( 'unifiedsmb/v1', '/login', array(
    'methods'  => 'POST',
    'callback' => 'unifiedsmb_handle_login',
    'permission_callback' => '__return_true',
  ) );
} );

function unifiedsmb_check_permission(WP_REST_Request $request) {
  $api_key = $request->get_header('apiKey');
  
  if ( $api_key ) {

    global $wpdb;
    $backoffice_connection_table = $wpdb->prefix . 'unifiedsmb_backoffice_connection';
    $api_keys_table = $wpdb->prefix . 'woocommerce_api_keys';

    $results = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT * FROM %i b INNER JOIN %i k ON b.`key` = k.`key_id`",
        $backoffice_connection_table,
        $api_keys_table
      )
    );
    $key = $results[0]->consumer_secret;
    if ( $api_key == $key ) {
      return true;
    }
  } else {
    return false;
  }
}

function unifiedsmb_delete_transient_payment_gateways() {
  delete_transient( 'unifiedsmb_backoffice_payment_gateways' );
  return 'Succsefully deleted the unifiedsmb_backoffice_payment_gateways transient';
}

function unifiedsmb_get_token(WP_REST_Request $request) {
  $username = sanitize_user( $request->get_header( 'username' ) );
  if ( ! $username ) {
    return new WP_REST_Response( array( 'error' => 'Authentication failed' ), 401 );
  }

  $user = get_user_by( 'login', $username );

  if ( $user ) {
    $user_id = $user->ID;

    // Generate and stores a new password
    $new_password = wp_generate_password( 60, );
    wp_set_password( $new_password, $user_id );
  
    // Return the new password
    return new WP_REST_Response( array( 'token' => $new_password ), 200 );
  }

  return new WP_REST_Response( array( 'error' => 'Authentication failed' ), 401 );
}

function unifiedsmb_handle_login( WP_REST_Request $request ) {
  $username = sanitize_user( $request->get_param( 'username' ) );
  $password = $request->get_param( 'password' );

  if ( empty( $username ) || empty( $password ) ) {
    return new WP_REST_Response( array( 'error' => 'Authentication failed' ), 401 );
  }

  $user = wp_signon( array(
    'user_login'    => $username,
    'user_password' => $password,
  ) );

  if ( is_wp_error( $user ) ) {
    return new WP_REST_Response( array( 'error' => $user->get_error_message() ), 401 );
  } else {
    wp_redirect( get_site_url() . '/wp-admin/' );
  }
}