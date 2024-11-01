<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Adds a barcode field to the woocommerce variations wich is saved in the unifiedsmb_barcode meta data field
 */
function unifiedsmb_add_barcode_to_variations($loop, $variation_data, $variation) {
  woocommerce_wp_text_input(
    array(
      'id'          => 'unifiedsmb_barcode[' . $variation->ID . ']',
      'label'       => __( 'Barcode', 'unifiedsmb-for-woocommerce' ),
      'placeholder' => __( 'Enter barcode value', 'unifiedsmb-for-woocommerce' ),
      'value'       => get_post_meta( $variation->ID, 'unifiedsmb_barcode', true ),
      'desc_tip'    => 'true',
      'description' => __( 'Enter the barcode associated with this product variation.', 'unifiedsmb-for-woocommerce' ),
      'type'        => 'number',
    )
  );

  // Add a nonce field for security
  wp_nonce_field( 'unifiedsmb_save_barcode_' . $variation->ID );
}
add_action( 'woocommerce_variation_options_pricing', 'unifiedsmb_add_barcode_to_variations', 10, 3 );

/**
 * Saves the barcode data to the unifiedsmb_barcode meta data field
 */
function unifiedsmb_save_barcode_variation_data($variation_id) {
  // Verify nonce
  if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash ( $_POST['_wpnonce'] ) ) , 'unifiedsmb_save_barcode_' . $variation_id ) || ! current_user_can('administrator') ) {
    die( __( 'Security check failed', 'unifiedsmb-for-woocommerce' ) );
  }

  // Check if barcode is set in POST request
  if ( isset( $_POST['unifiedsmb_barcode'][ $variation_id ] ) ) {
    $barcode = sanitize_text_field( $_POST['unifiedsmb_barcode'][ $variation_id ] );
    update_post_meta( $variation_id, 'unifiedsmb_barcode', $barcode );
  }
}
add_action( 'woocommerce_save_product_variation', 'unifiedsmb_save_barcode_variation_data', 10, 1 );
