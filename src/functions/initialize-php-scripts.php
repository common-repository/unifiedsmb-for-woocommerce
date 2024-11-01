<?php
use UnifiedSMB\Connection;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once plugin_dir_path(__FILE__) . '../backoffice/backoffice-connection.php';

require_once plugin_dir_path(__FILE__) . './unifiedsmb-variations-fields.php';

require_once plugin_dir_path(__FILE__) . '../backoffice/make-connection.php';

require_once plugin_dir_path(__FILE__) . '../payment-gateway/initialize-payment-gateways.php';

require_once plugin_dir_path(__FILE__) . './sync-functions.php';