jQuery(document).ready(function($) {
  const makeAjaxCall = async (buttonStr, strForSyncronise, action) => {
    var button = $(`#${buttonStr}`);
    button.attr('disabled', true);
    
    AddNotice('info', buttonStr, strForSyncronise, 'aan het synchroniseren...', false);

    await jQuery.ajax({
      type: 'POST',
      url: syncs_script_params.ajaxurl,
      data: {
        action: action,
        fail_message: syncs_script_params.fail_message,
        success_message: syncs_script_params.success_message
      },
      success: function ( data, textStatus, XMLHttpRequest ) {
        data = JSON.parse(data);
        $(`.notice-for-${buttonStr}`).remove();
        
        if (data === null) {
          AddNotice('error', buttonStr, strForSyncronise, `onsuccesvol gesynchroniseerd. Error: An unkown error occurred`);
          return;
        }

        if (data['errorMsg']) {
          if (data['code'] === 401) {
            jQuery.ajax({
              type: 'POST',
              url: syncs_script_params.ajaxurl,
              data: {
                action: 'delete_backoffice_connection_check_transient'
              }
            });

            setTimeout(() => {
              window.location.href = '/wp-admin/admin.php?page=unified-smb';
            }, 5000);
            
            AddNotice('error', buttonStr, strForSyncronise, `onsuccesvol gesynchroniseerd. Error: Authentication failed and you will be redirected in 5 seconds`);
            return;
          }

          AddNotice('error', buttonStr, strForSyncronise, `onsuccesvol gesynchroniseerd. Error: ` + data['errorMsg']);
          return;
        }

        if ( data == '0' || data == '-1' ) {
          AddNotice('error', buttonStr, strForSyncronise, `onsuccesvol gesynchroniseerd. Error: An unkown error occurred`);
        } else {
          AddNotice('success', buttonStr, strForSyncronise, `succesvol gesynchroniseerd.`);
          button.html(`${strForSyncronise} succesvol gesynchroniseerd`);
        }
      },
      error: function ( XMLHttpRequest, textStatus, errorThrown ) {
        $(`.notice-for-${buttonStr}`).remove();
        console.error(errorThrown);
      }
    });

    button.removeAttr('disabled');
  }

  const AddNotice = (type, buttonStr, strForSyncronise, message, is_dismissible = true) => {
    let notice = $(`<div class="notice notice-${type} notice-for-${buttonStr} ${is_dismissible ? 'is-dismissible' : ''}"><p>${strForSyncronise} ${message}</p></div>`);
    
    $(".wrap").prepend(notice);
  
    if (is_dismissible) {
      let closeButton = $('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');
      notice.append(closeButton);
      
      // When you click on the button it fades out in 1 second and it has a animation of going up
      closeButton.on('click', function() {
        notice.fadeTo(100, 0, function() {
          notice.slideUp(100, function() {
            notice.remove();
          });
        });
      });
    }
  }

  $('#sync-categories').click(async function(){
    makeAjaxCall('sync-categories', 'Categorieën', 'unifiedsmb_sync_categories');
  });
  
  $('#sync-attributes').click(async function(){
    makeAjaxCall('sync-attributes', 'Attributen', 'unifiedsmb_sync_attributes');
  });
  
  $('#sync-tax-rates').click(async function(){
    makeAjaxCall('sync-tax-rates', 'Btw', 'unifiedsmb_sync_tax_rates');
  });
  
  $('#sync-payment-methods').click(async function(){
    makeAjaxCall('sync-payment-methods', 'Betaal methoden', 'unifiedsmb_sync_payment_methods');
  });

  $('#sync-orders').click(async function(){
    makeAjaxCall('sync-orders', 'Bestellingen', 'unifiedsmb_sync_orders');
  });
  
  $('#sync-products').click(async function(){
    makeAjaxCall('sync-products', 'Producten', 'unifiedsmb_sync_products');
  });

  $('#sync-all').click(async function(){
    makeAjaxCall('sync-all', 'Alles', 'unifiedsmb_sync_all');
  });
});