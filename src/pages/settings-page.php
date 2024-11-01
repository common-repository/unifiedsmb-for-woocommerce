<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $wpdb;
$table = $wpdb->prefix . 'unifiedsmb_backoffice_connection';
$results = $wpdb->get_results(
  $wpdb->prepare(
    "SELECT * FROM %i",
    $table
  )
);

// Gets the prefix of the backoffice
$backofficePrefix = $results[0]->backoffice_url;
$username = sanitize_user( "UnifiedSMB-$backofficePrefix" );
// Gets the user
$user = get_user_by( 'login', $username );
if ( ! $user ) {
  // Checks if the user prefix has already been used
  $user = get_user_by( 'login', "UnifiedSMB-" );
  if ( ! $user ) {
    exit;
  }

  // Updates the user with the user prefix
  $wpdb->update(
    $wpdb->users,
    [
      'user_login'   => $username,
      'display_name' => $username,
    ],
    ['ID' => $user->data->ID]
  );
}

$userId = $user->data->ID;
$roles = $user->roles;

if ( ! empty( $_POST ) ) {
  // Validate if the request has been send via the plugin
  if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash ( $_POST['_wpnonce'] ) ) , 'unifiedsmb-change-wordpress-role-post' ) || ! current_user_can('administrator') ) {
    die( __( 'Security check failed', 'unifiedsmb-for-woocommerce' ) );
  }

  $currentRole = sanitize_text_field( $_POST['role'] );

  $user = new WP_User( $userId );
  $user->set_role( $currentRole );

  $message = '<h4>Succesvol opgeslagen</h4>';
} else {
  $currentRole = $roles[0];
}

?>

<div class='wrap'>
  <h1>Settings</h1>
  <form method="post">

    <?php wp_nonce_field( 'unifiedsmb-change-wordpress-role-post' ) ?>

    <div>
      <p>U kunt hier de autorisatie van de gebruiker instellen voor wanneer u wilt inloggen in wordpress via de backoffice</p>
      <p>Voor meer info over wordpress roles kunt u <a target='_blank' href='https://wordpress.com/support/user-roles/'>hier</a> lezen</p>
      <label>Wordpress role: </label>
      <select name='role'>
        <option value='administrator' <?php if ($currentRole === 'administrator') echo 'selected' ?>>Administrator</option>
        <option value='editor' <?php if ($currentRole === 'editor') echo 'selected' ?>>Editor</option>
        <option value='author' <?php if ($currentRole === 'author') echo 'selected' ?>>Author</option>
        <option value='contributor' <?php if ($currentRole === 'contributor') echo 'selected' ?>>Contributor</option>
        <option value='subscriber' <?php if ($currentRole === 'subscriber') echo 'selected' ?>>Subscriber</option>
        <option value='customer' <?php if ($currentRole === 'customer') echo 'selected' ?>>Customer</option>
        <option value='shop_manager' <?php if ($currentRole === 'shop_manager') echo 'selected' ?>>Shop_manager</option>
      </select>
    </div>
    <button type='submit'>Opslaan</button>
  </form>
  <?php if (isset($message)) {
    echo esc_html( $message );
  } ?>
</div>