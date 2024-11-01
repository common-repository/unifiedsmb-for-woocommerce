<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function unifiedsmb_syncs_script() {
  wp_enqueue_script( 'syncs-script', plugin_dir_url(__FILE__) . 'syncs-script.js' );
  wp_localize_script( 'syncs-script', 'syncs_script_params', array(
      'ajaxurl' => admin_url( 'admin-ajax.php' ),
      'fail_message' => __('Connection to server failed. Check the mail credentials.', 'unifiedsmb-for-woocommerce'),
      'success_message' => __('Connection successful. ', 'unifiedsmb-for-woocommerce')
    )
  );
}
add_action( 'admin_enqueue_scripts', 'unifiedsmb_syncs_script' );

function unifiedsmb_make_connection_with_backoffice_script() {
  wp_enqueue_script( 'make-connection-script', plugin_dir_url(__FILE__) . 'make-connection-with-backoffice.js' );
  wp_localize_script( 'make-connection-script', 'connection_script_params', array(
      'ajaxurl' => admin_url( 'admin-ajax.php' ),
      'nonce' => wp_create_nonce( 'unifiedsmb_connection_nonce' ),
      'fail_message' => __('Connection to server failed. Check the mail credentials.', 'unifiedsmb-for-woocommerce'),
      'success_message' => __('Connection successful. ', 'unifiedsmb-for-woocommerce')
    )
  );
}
add_action( 'admin_enqueue_scripts', 'unifiedsmb_make_connection_with_backoffice_script' );