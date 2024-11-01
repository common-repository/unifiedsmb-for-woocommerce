async function unifiedsmb_make_connection_with_backoffice() {
  await jQuery.ajax({
    type: 'POST',
    url: connection_script_params.ajaxurl, // Use the localized 'ajax_url' from the corrected PHP code
    data: {
      action: 'unifiedsmb_make_connection_with_backoffice',
      url: jQuery('input[name="url"]').val(),
      nonce: connection_script_params.nonce, // Use the nonce passed from PHP
    },
    success: function (response) {
      window.location.replace(response.data);
    },
    error: function (XMLHttpRequest, textStatus, errorThrown) {
      alert(errorThrown);
    }
  });
}