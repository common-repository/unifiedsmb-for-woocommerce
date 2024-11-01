<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_filter( 'woocommerce_payment_gateways', 'unifiedsmb_add_custom_gateway' );

function unifiedsmb_add_custom_gateway( $gateways ) {
    // Checks if the woocommerce plugin hase been activated
    $active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );
    if ( ! in_array( 'woocommerce/woocommerce.php', $active_plugins ) ) {
        return $gateways;
    }

    // Imports the UnifiedSMBBaseGateway class
    require_once plugin_dir_path(__FILE__) . './UnifiedSMBBaseGateway.php';

    $gatewaysData = get_transient('unifiedsmb_backoffice_payment_gateways');
    if (!$gatewaysData) {
        // If not, we make the API call
        $gatewaysData = unifiedsmb_get_woocommerce_gateway_data();
        if (is_array($gatewaysData) && !empty($gatewaysData)) {
            // Save the API response so we can use it later for 1 hour
            set_transient('unifiedsmb_backoffice_payment_gateways', $gatewaysData, 1 * HOUR_IN_SECONDS);
        } else {
            $gatewaysData = []; // Ensure this is always an array
        }
    }

    if (!empty($gatewaysData)) {
        foreach ($gatewaysData as $gatewayData) {
            $gateways[] = new UnifiedSMBBaseGateway(
                "sk_pay_id_{$gatewayData->id}",
                $gatewayData->title,
                (int) $gatewayData->id,
                $gatewayData->method_provider
            );
        }
    }

    return $gateways;
}

function unifiedsmb_get_woocommerce_gateway_data()
{
    // Gets all the payments from the backoffice
    $connection = new UnifiedSMB\Connection;
    $response = $connection->api( 'GET', 'woocommerce/syncPayments', [], false );

    return $response;
}