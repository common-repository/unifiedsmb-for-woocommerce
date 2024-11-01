<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Define and fill the $unifiedsmb_setting_types
global $unifiedsmb_setting_types;

$attributes = wc_get_attribute_taxonomies();
$attribute_names = [];

foreach ($attributes as $attribute) {
  $attribute_names[$attribute->attribute_id] = $attribute->attribute_label;
}

$unifiedsmb_setting_types = [
  'barcodes' => [
    'displayText'     => 'Barcodes',
    'dropdownText'    => 'Selecteer een attribute',
    'dropdownOptions' => $attribute_names,
  ],
  'brands' => [
    'displayText'     => 'Merken',
    'dropdownText'    => 'Selecteer een attribute',
    'dropdownOptions' => $attribute_names,
  ],
  'sizes' => [
    'displayText'     => 'Maten',
    'dropdownText'    => 'Selecteer een attribute',
    'dropdownOptions' => $attribute_names,
  ],
  'colors' => [
    'displayText'     => 'Kleuren',
    'dropdownText'    => 'Selecteer een attribute',
    'dropdownOptions' => $attribute_names,
  ],
  'seasons' => [
    'displayText'     => 'Seizoenen',
    'dropdownText'    => 'Selecteer een attribute',
    'dropdownOptions' => $attribute_names,
  ],
  'tax' => [
    'displayText'     => 'Prijs inc/exlc btw berekenen',
    'dropdownText'    => 'Selecteer een optie',
    'dropdownOptions' => [
      'incl' => 'Inclusief',
      'excl' => 'Exclusief',
    ],
  ],
];

function unifiedsmb_get_current_tab($default = 'export') {
  return isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : $default;
}

function unifiedsmb_render_tabs($tabs, $current_tab) {
  echo '<nav class="nav-tab-wrapper mb-3">';
  foreach ( $tabs as $tab_key => $tab_caption ) {
    $active_class = $tab_key === $current_tab ? 'nav-tab-active' : '';
    $url = "?page=unified-smb-export&tab=$tab_key";
    echo '<a href="', esc_url( $url ) ,'" class="nav-tab ', esc_attr( $active_class ) ,'">', esc_html( $tab_caption ) ,'</a>';
  }
  echo '</nav>';
}

function unifiedsmb_render_tab_content($current_tab) {
  echo '<div class="tab-content">';
  switch ($current_tab) {
    case 'settings':
      unifiedsmb_settings_page();
      break;
    default:
      unifiedsmb_export_page();
      break;
  }
  echo '</div>';
}

function unifiedsmb_export_page() {
  global $unifiedsmb_setting_types;

  $option_values = [];
  foreach ( $unifiedsmb_setting_types as $name => $setting ) {
    $option = get_option( "unifiedsmb_export_$name" );
    if ($option) {
      $option_values[ $name ] = $option;
    }
  }

  if ( empty( $option_values ) ) {
    // Generating the dynamic URL
    $settings_url = admin_url('admin.php?page=unified-smb-export&tab=settings');
    ?>
    <div class="notice notice-info is-dismissible">
      <h1>Instellingen Ontbreken</h1>
      <p>U heeft nog geen instellingen geconfigureerd. <a href="<?php echo esc_url( $settings_url ); ?>">Klik hier</a> om uw instellingen aan te passen en te configureren.</p>
    </div>
    <?php
  }

  if ( ! empty( $_POST ) ) {
    // Validate if the request has been send via the plugin
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash ( $_POST['_wpnonce'] ) ) , 'unifiedsmb-export-data' ) || ! current_user_can('administrator') ) {
      die( __( 'Security check failed', 'unifiedsmb-for-woocommerce' ) );
    }

    $data = [];
    // adds cattegories to the request
    if ( isset( $_POST['cattegories'] ) ) {
      $data['cattegories'] = true;
    }

    if ( isset( $_POST['attributes'] ) ) {
      $data['attributes'] = [
        'barcodes' => isset( $option_values['barcodes'] ) ? $option_values['barcodes'] : null,
        'brands'   => isset( $option_values['brands']   ) ? $option_values['brands']   : null,
        'colors'   => isset( $option_values['colors']   ) ? $option_values['colors']   : null,
        'sizes'    => isset( $option_values['sizes']    ) ? $option_values['sizes']    : null,
        'seasons'  => isset( $option_values['seasons']  ) ? $option_values['seasons']  : null,
      ];
    }
    // adds products to the request
    if ( isset( $_POST['products'] ) ) {
      $data['products'] = true;
    }

    echo wp_json_encode( $data );
    // exit;

    // $connection = new UnifiedSMB\Connection;
    // $response   = $connection->api('POST', 'woocommerce/export', $data);
    // print_r($response);
  }

  function unifiedsmb_get_all_wc_attributes() {
    $attributes = wc_get_attribute_taxonomies();

    $attribute_array = array();
    if ( $attributes ) {
      foreach ( $attributes as $attribute ) {
        $attribute_array[ $attribute->attribute_id ] = $attribute->attribute_label;
      }
    }
    return $attribute_array;
  }

  $all_attributes = unifiedsmb_get_all_wc_attributes();

  function unifiedsmb_get_woocommerce_tax_classes() {
    $all_tax_rates = [];
    $tax_classes = WC_Tax::get_tax_classes(); // Retrieve all tax classes.
    if ( !in_array( '', $tax_classes ) ) { // Make sure "Standard rate" (empty class name) is present.
      array_unshift( $tax_classes, 'standard' );
    }
    return $tax_classes;
  }

  $all_tax_classes = unifiedsmb_get_woocommerce_tax_classes();
  ?>

  <div class="wrap">
    <h1 class='mb-4'>Exporteer data naar de backoffice</h1>
    <p>Hier kunt u een paar dingen selecteren die dan worden geexporteerd naar de backoffice geen van deze velden is verplicht</p>
    <form method='post' class='mb-3'>

      <?php wp_nonce_field( 'unifiedsmb-export-data' ) ?>

      <!-- begin cattegories -->
      <div class='mb-3'>
        <div class='mb-3'>
          <label>
            <input name='cattegories' value='true' type='checkbox' id='checkbox-cattegories'>
            Categorieen
          </label>
        </div>
      </div>
      <!-- end cattegories -->

      <!-- begin attributes -->
      <div class='mb-3'>
        <div class='mb-3'>
          <label>
            <input name='attributes' value='true' type='checkbox' id='checkbox-attributes'>
            Attributen
          </label>
        </div>
      </div>
      <!-- end attributes -->

      <!-- begin taxes -->
      <div class='mb-3'>
        <div class='mb-3'>
          <label>
            <input name='taxes' value='true' type='checkbox' id='checkbox-taxes'>
            Btw
          </label>
        </div>

        <div id='dropdowns-taxes' class='ms-3' style='display:none;'>
          <div class='mb-3'>
            <label>High:</label>
            <select id='high-tax-select' name='high-tax'>
              <option value=''>- Selecteer welke tax class hierbij hoort -</option>
              <?php
                foreach ( $all_tax_classes as $tax_class ) {
                  echo '<option value="', esc_attr( $tax_class ) ,'">', esc_html( $tax_class ) ,'</option>';
                }
              ?>
            </select>
          </div>

          <div class='mb-3'>
            <label>Reduced:</label>
            <select id='reduced-tax-select' name='reduced-tax'>
              <option value=''>- Selecteer welke tax class hierbij hoort -</option>
              <?php
                foreach ( $all_tax_classes as $tax_class ) {
                  echo '<option value="', esc_attr( $tax_class ) ,'">', esc_html( $tax_class ) ,'</option>';
                }
              ?>
            </select>
          </div>

          <div class='mb-3'>
            <label>Reduced2:</label>
            <select id='reduced2-tax-select' name='reduced2-tax'>
              <option value=''>- Selecteer welke tax class hierbij hoort -</option>
              <?php
                foreach ( $all_tax_classes as $tax_class ) {
                  echo '<option value="', esc_attr( $tax_class ) ,'">', esc_html( $tax_class ) ,'</option>';
                }
              ?>
            </select>
          </div>

          <div class='mb-3'>
            <label>Super-reduced:</label>
            <select id='super-reduced-tax-select' name='super-reduced-tax'>
              <option value=''>- Selecteer welke tax class hierbij hoort -</option>
              <?php
                foreach ( $all_tax_classes as $tax_class ) {
                  echo '<option value="', esc_attr( $tax_class ) ,'">', esc_html( $tax_class ) ,'</option>';
                }
              ?>
            </select>
          </div>

          <div class='mb-3'>
            <label>Zero:</label>
            <select id='zero-tax-select' name='zero-tax'>
              <option value=''>- Selecteer welke tax class hierbij hoort -</option>
              <?php
                foreach ( $all_tax_classes as $tax_class ) {
                  echo '<option value="', esc_attr( $tax_class ) ,'">', esc_html( $tax_class ) ,'</option>';
                }
              ?>
            </select>
          </div>
        </div>
      </div>
      <!-- end taxes -->

      <!-- begin products -->  
      <div class='mb-3'>
        <div class='mb-3'>
          <label>
            <input name='products' value='true' type='checkbox' id='checkbox-products'>
            Producten
          </label>
        </div>
      </div>
      <!-- end products -->
      <button type='submit' class="btn btn-primary">Exporteer</button>
    </form>
  </div>

  <script>
    jQuery(document).ready(function($) {
      const attributesSelects = ['#brands-select', '#colors-select', '#sizes-select', '#seasons-select'];
      const taxesSelects      = ['#high-tax-select', '#reduced-tax-select', '#reduced2-tax-select', '#super-reduced-tax-select', '#zero-tax-select'];

      // Show/hide the attributes dropdowns based on checkbox state
      $('#checkbox-attributes').click(function() {
        const checkboxDiv = $('#dropdowns-attributes')[0];
        checkboxDiv.style.display = checkboxDiv.style.display === 'none' ? 'block' : 'none';
      });

      // Show/hide the taxes dropdowns based on checkbox state
      $('#checkbox-taxes').click(function() {
        const checkboxDiv = $('#dropdowns-taxes')[0];
        checkboxDiv.style.display = checkboxDiv.style.display === 'none' ? 'block' : 'none';
      });

      $(attributesSelects.join(', ')).change(function() {
        const selectedValues = attributesSelects.map(id => $(id).val());

        // Reset the disabled state of options
        attributesSelects.forEach(id => $(`${id} option`).prop('disabled', false));

        attributesSelects.forEach((id, idx1) => {
          console.log(idx1);
          selectedValues.forEach((value, idx2) => {
            if (idx1 !== idx2 && value) {
              $(`${id} option[value=${value}]`).prop('disabled', true);
            }
          });
        });
      });

      $(taxesSelects.join(', ')).change(function() {
        const selectedValues = taxesSelects.map(id => $(id).val());

        // Reset the disabled state of options
        taxesSelects.forEach(id => $(`${id} option`).prop('disabled', false));

        taxesSelects.forEach((id, idx1) => {
          console.log(idx1);
          selectedValues.forEach((value, idx2) => {
            if (idx1 !== idx2 && value) {
              $(`${id} option[value=${value}]`).prop('disabled', true);
            }
          });
        });
      });
    });
  </script>
  <?php
}

function unifiedsmb_settings_page() {
  global $unifiedsmb_setting_types;

  if ( ! empty( $_POST ) ) {
    // Validate if the request has been send via the plugin
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash ( $_POST['_wpnonce'] ) ) , 'unifiedsmb-change-settings-post' ) || ! current_user_can('administrator') ) {
      die( __( 'Security check failed', 'unifiedsmb-for-woocommerce' ) );
    }

    foreach ($unifiedsmb_setting_types as $name => $setting) {
      if ( ! isset( $_POST[ $name ] ) || empty( $_POST[ $name ] ) ) {
        delete_option( "unifiedsmb_export_$name" );
        continue;
      }
      update_option( "unifiedsmb_export_$name", sanitize_text_field( $_POST[ $name ] ) );    
    }

    ?>
    <div class="notice notice-info is-dismissible">
      <h1>Instellingen succesvol opgeslagen</h1>
    </div>
    <?php
  }

  ?>
  <form method="post">
    <?php
    
      wp_nonce_field( 'unifiedsmb-change-settings-post' );
    
      foreach ( $unifiedsmb_setting_types as $name => $setting ) {
        unifiedsmb_render_dropdowns( $name, $setting['displayText'], $setting['dropdownText'], $setting['dropdownOptions'] );
      }
    ?>
    <?php submit_button(); ?>
  </form>
  <?php
}

function unifiedsmb_render_dropdowns(string $name, string $display_text, string $dropdown_text, array $dropdown_options) {
  $saved_setting = get_option( "unifiedsmb_export_$name", '' );

  ?>
  <div class="mb-3">
    <label for="<?php echo esc_attr( $name );?>"><?php echo esc_html( $display_text );?>:</label>
    <select id="<?php echo esc_attr( $name );?>" name="<?php echo esc_attr( $name );?>">
      <option value=""><?php echo esc_html( $dropdown_text );?></option>
      <?php foreach ($dropdown_options as $name => $displayName) : ?>
        <option value="<?php echo esc_attr( $name ); ?>" <?php selected($name, $saved_setting); ?>>
          <?php echo esc_html($displayName); ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <?php
}

$tabs = array(
  'export'   => 'Export',
  'settings' => 'Settings',
);
$current_tab = unifiedsmb_get_current_tab();

?>

<div class="wrap">
  <div class="notice notice-error">
    <h3>Deze functie is nog in development</h3>
    <p>Deze functies werken momenteel niet. Als je uw data wilt exporteren kunt u dit doen via woocommerce en dan dit importeren in de backoffice</p>
  </div>
  <h1><?php echo esc_html( ucfirst($current_tab) ); ?></h1>
  <?php unifiedsmb_render_tabs($tabs, $current_tab); ?>
  <?php unifiedsmb_render_tab_content($current_tab); ?>
</div>