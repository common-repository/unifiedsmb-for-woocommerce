<?php

namespace UnifiedSMB;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Connection
{
  private $wpdb;
  private $backoffice_connection_table;
  private $api_keys_table;
  private $api_key;

  /**
   * Sets the private fields
   */
  public function __construct() {
    global $wpdb;
    $this->wpdb = $wpdb;

    // Initialize full table names with prefixes
    $this->backoffice_connection_table = $wpdb->prefix . 'unifiedsmb_backoffice_connection';
    $this->api_keys_table = $wpdb->prefix . 'woocommerce_api_keys';

    // Get the connection data from the database
    $results = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT * FROM %i b INNER JOIN %i k ON b.`key` = k.`key_id`",
        $this->backoffice_connection_table,
        $this->api_keys_table
      )
    );

    if (empty($results)) {
      $this->invalid();
    } else {
      $this->api_key = $results[0]->consumer_secret;
    }
  }

  
  /**
   * When called makes a api call to the backoffice
   * 
   * @param string $method The method: GET, PUT, POST or DELETE
   * @param string $endpoint The endpoint that needs to be called
   * @param array $data The data that needs to be send to the backoffice
   * @param bool $check_invalid Checks if the call was unauthorized (default true)
   * 
   * @return array $response_body Returns the body from the response
   */
  public function api(string $method, string $endpoint, array $data, bool $check_invalid = true) {
    // Gets the api_key and the backoffice_url out of the database
    $wpdb = $this->wpdb;
    $results = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT * FROM %i",
        $this->backoffice_connection_table
      )
    );
    
    if (empty($results)) {
      if ($check_invalid) {
        $this->invalid();
      }
      return ['errorMsg' => 'auth_failed', 'code' => 401];
    }

    // Sets up the $url
    $url = 'https://api-saleschannels.' . $results[0]->backoffice_url . ".techdogcloud.com/$endpoint";

    // Makes a request
    $response = wp_remote_request($url, array(
      'method'  => $method,
      'headers' => array(
        'Content-Type' => 'application/x-www-form-urlencoded',
        'key'     => $this->api_key
      ),
      'body'      => $data,
      'sslverify' => false,
      'blocking'  => true,
      'timeout' => 3600,
    ));

    $status_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);

    if ($status_code != 200 && $status_code != 201) {
      if (!$response_body) {
        return json_encode([
          'errorMsg' => 'Request timed out after 1 hour',
          'code'     => 401,
        ]);
      }

      switch ($status_code) {
        case 401:
          if ($check_invalid) {
            $this->invalid();
          }
          return json_encode([
            'errorMsg' => 'Authentication failed',
            'code' => 401
          ]);
        case 404:
          return json_encode([
            'errorMsg' => 'This route was not found',
            'code' => 404
          ]);
        case 500:
          return json_encode([
            'errorMsg' => $response_body,
            'code' => 500,
          ]);
        case 502:
          if ($check_invalid) {
            $this->invalid();
          }
          return json_encode([
            'errorMsg' => 'Connection with the backoffice failed and has been removed. Please connect the backoffice again.',
            'code' => 502
          ]);
        default:
          $error = error_get_last();
          return json_encode([
            'errorMsg' => $error['message'] ?? 'Unknown error',
            'code' => 500
          ]);
      }
    }

    return json_decode($response_body);
  }

  /**
   * Checks if the connection with the backoffice still exists
   */
  public function check() {
    $check_connection = $this->api('GET', 'woocommerce/checkConnection', []);
    if (is_array($check_connection) && $check_connection['errorMsg']) {
      return false;
    }
    return true;
  }

  /**
   * When this function is called the invalid connection will be deleted
   */
  public function invalid() {
    $wpdb = $this->wpdb;

    $results = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT * FROM %i",
        $this->backoffice_connection_table
      )
    );
    
    if (count($results) > 0) {
      foreach ($results as $result) {
        // Prepare statement for deleting from api_keys_table
        $delete_query = $wpdb->prepare(
          "DELETE FROM %i WHERE key_id = %d",
          $this->api_keys_table,
          $result->key
        );
        $wpdb->query( $delete_query );

        // Prepare statement for deleting from backoffice_connection_table
        $delete_query = $wpdb->prepare(
          "DELETE FROM %i WHERE id = %d",
          $this->backoffice_connection_table,
          $result->id
        );
        $wpdb->query( $delete_query );
      }
    }
    delete_transient('unifiedsmb_backoffice_connection_check');
  }
}