<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$woocommerceUrl = admin_url('plugin-install.php?s=woocommerce&tab=search&type=term');
?>
<div class='wrap'>
  <div class="notice notice-warning is-dismissible">
    <h3>UnifiedSMB-plugin</h3>
    <p>
      Voordat u de UnifiedSMB-plugin kunt gebruiken moet u eerst de <a href="<?php echo esc_url( $woocommerceUrl );?>">WooCommerce</a> plugin installeren.
      <a href="<?php echo esc_url( $woocommerceUrl );?>">Klik hier</a> om naar de download te gaan.
    </p>
  </div>
</div>

