<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Base Payment Gateway for UnifiedSMB
 */
class UnifiedSMBBaseGateway extends \WC_Payment_Gateway {
    private $provider;
    private $provider_method_id;
    private $connection;

    /**
     * Class constructor
     *
     * @param string $id                Gateway ID.
     * @param string $title             Gateway title.
     * @param int    $provider_method_id Provider method ID.
     * @param int    $provider           Provider.
     */
    public function __construct( $id, $title, $provider_method_id, $provider ) {
        $this->id                 = $id;
        $this->provider_method_id = $provider_method_id;
        $this->title              = $title;
        $this->provider           = $provider;
        $this->connection         = new UnifiedSMB\Connection;

        $this->has_fields         = true;
        $this->description        = null;
        $this->method_title       = 'UnifiedSMB - '.$title;

        $this->init_form_fields();
        $this->init_settings();
    }

    /**
     * Get the gateway ID.
     *
     * @return string ID of the gateway.
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Process the payment.
     *
     * @param int $order_id Order ID.
     *
     * @return array Transaction data.
     */
    public function process_payment($order_id) {
        $order = wc_get_order( $order_id );

        // Starts a transaction
        $start_transaction_data = $this->start_transaction( $order );

        // Retrieve all bought products from the order.
        $wc_products = $order->get_items();

        $data = array();

        // Add the billing address to data.
        $data['customer_details'] = $order->get_address( 'billing' );
        $data['customer_details']['note'] = $order->get_customer_note();

        // Retrieve the date the order was created.
        $order_date = $order->get_date_created();
        $sold_date  = $order_date->date( 'Y-m-d H:i:s' );

        // Loop through all bought products.
        foreach ( $wc_products as $wc_product ) {
            $product_id = $wc_product->get_variation_id() ? $wc_product->get_variation_id() : $wc_product->get_product_id();
            $product    = wc_get_product( $product_id );

            $total_tax  = $wc_product->get_total_tax();
            $subtotal   = $wc_product->get_subtotal();

            // Calculate the tax rate percentage.
            $tax_rate_percentage = ( $subtotal > 0 ) ? ( ( $total_tax / $subtotal ) * 100 ) : 0;

            // Initialize variables.
            $attribute_terms = array();
            $attributes      = null;
            $categories      = null;

            // Checks if the product is a variation
            if ( $wcProduct->get_variation_id() ) {
                $parent_id = $product->get_parent_id();
                $terms     = get_the_terms( $parent_id, 'product_cat' );

                if ( $terms && ! is_wp_error( $terms ) ) {
                    foreach ( $terms as $term ) {
                        $categories[] = $term->name;
                    }
                }

                // Get the attributes specific to this variation
                $attributes = $product->get_attributes();

                foreach ( $attributes as $attribute_name => $term_slug ) {
                    // Get the attribute taxonomy ID by the taxonomy name
                    $attribute_id = wc_attribute_taxonomy_id_by_name( $attribute_name );

                    // Get the term by its slug and taxonomy
                    $term = get_term_by( 'slug', $term_slug, $attribute_name );
                    if ( $term && ! is_wp_error( $term ) ) {

                        // Get term ID and name
                        $term_id   = $term->term_id;
                        $term_name = $term->name;

                        // Add this to the output array
                        if ( ! isset( $attribute_terms[ $attribute_id] ) ) {
                        $attribute_terms[ $attribute_id ] = array();
                        }
                        $attribute_terms[ $attribute_id ][ $term_id ] = $term_name;
                    }
                }
            }
            
            // Add the sold products to data.
            $data['sold_products'][] = array(
                'backoffice_product_id' => $product->get_meta( 'backoffice_product_id' ),
                'name'                  => $product->get_name(),
                'amount'                => $wc_product->get_quantity(),
                'total_paid_incl_tax'   => $subtotal + $total_tax,
                'total_paid_excl_tax'   => $subtotal,
                'tax_rate'              => $tax_rate_percentage,
                'description'           => $product->get_description(),
                'sold_at'               => $sold_date,
                'attributes'            => $attribute_terms,
                'categories'            => $categories,
            );
        }

        // Sends the order to the backoffice
        $data = $this->connection->api( 'POST', "woocommerce/syncOrders", $data );

        // Checks if somthing whent wrong
        if (gettype($data) != 'object') {
            print_r($data);
            exit;
        }

        // Adds the orderId and the transactionId to the order
        $order->update_meta_data( 'unifiedsmb_transaction_id', $start_transaction_data->transactionId );
        $order->update_meta_data( 'unifiedsmb_order_id', $data->unifiedsmb_order_id );
        $order->save();

        // Redirects the user to the return url
        return array(
            'result'    => 'success',
            'redirect'  => $start_transaction_data->returnUrl,
        );
    }

    /**
     * Starts a transaction
     * 
     * @param WC_Order $order The current order that needs to get paid
     * 
     * @return array A array containig the transactionId and the returnUrl
     */
    private function start_transaction( WC_Order $order)
    {
        $data = $this->connection->api( 'POST', 'woocommerce/transaction/start', [
        'amount'          => $order->total,
        'paymentMethodId' => $this->provider_method_id,
        'provider'        => $this->provider,
        'orderId'         => $order->id,
        ] );

        return $data;
    }

    /**
     * Handles the return from the transaction
     * 
     * @param string $transactionId The transactionId of the payment
     * 
     * @return array You can be redirected when the transaction is paid or it will stay on the page with a error message
     */
    public function handle_return(string $transactionId)
    {
        $data = $this->connection->api( 'GET', "woocommerce/transaction/info?transactionId={$transactionId}", [] );
        if ( ! $data ) {
            wc_add_notice( __( 'Invalid orderId', 'unifiedsmb-for-woocommerce' ), 'error' );
            return;
        }

        // Get the order
        $orderId = $data->orderId;
        $order   = wc_get_order( $orderId );
        if ( ! $order ) {
            wc_add_notice( __( 'Order was not found', 'unifiedsmb-for-woocommerce' ), 'error' );
            return;
        }

        // If the return has already handled this once it can't handle it again
        if ( $order->get_status() != 'pending' ) {
            wc_add_notice( __( 'Payment has already been processed', 'unifiedsmb-for-woocommerce' ), 'error' );
            return;
        }

        // Based on the status, either mark the order as processing or cancelled
        switch ( $data->state ) {
            case 100:
                // Marks the order as 
                $order->payment_complete();

                // Reduces the stock levels
                wc_reduce_stock_levels($orderId);

                // Empty the cart
                WC()->cart->empty_cart();

                // Redirect to the order-received page
                wp_safe_redirect($this->get_return_url($order));
                exit;
            default:
                // The order has been cancelld and show the error
                $order->update_status('cancelled');
                wc_add_notice( __( 'Payment has been cancelled', 'unifiedsmb-for-woocommerce' ), 'error' );
        }
    }
}