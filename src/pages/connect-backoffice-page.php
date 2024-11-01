<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<div class='wrap'>
  <div class='mt-5 border border-danger border-2'>
    <form id='connect-form' onsubmit='event.preventDefault(); unifiedsmb_make_connection_with_backoffice();' class='m-3'>
      <h3 class='mb-3'>For this add-on to work you need to connect it to your backoffice</h3>
      
      <label for="basic-url" class="form-label">Fill in the prefix before techdogcloud.com example: test.techdogcloud.com you fill in test</label>
      <div class="input-group mb-3">
        <span class="input-group-text" id="basic-addon3">https://unifiedsmb.</span>
        <input type="text" class="form-control" name="url" id="basic-url" aria-describedby="basic-addon3" autocomplete='off'>
        <span class="input-group-text">.techdogcloud.com</span>
      </div>
      
      <div id='error-container' class='mb-3'></div>

      <button type='submit' class='btn btn-success'>Connect</button>
    </form>
  </div>
</div>