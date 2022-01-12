<div class="special approval-controls">
    <h3>Proof Correct?</h3>    
    <div>
        <img src="<?php echo ORDER_APPROVAL_EXTENSION_URI . 'images/proof-notice-2.png'; ?>" class="proof-notice" alt="" />
        <img src="<?php echo ORDER_APPROVAL_EXTENSION_URI . 'images/proof-notice-2-mobile.png'; ?>" class="proof-notice-mobile" alt="" />
                            
        <label for="approve_proof">
              <input type="checkbox" name="approve_proof" id="approve_proof" class="check_proof"> I approve this proof for production and accept the <a href="<?php echo esc_url(home_url('terms-of-service')); ?>">production terms and conditions</a>
        </label>
    </div>
    <div class="visible">
        <button type="button" disabled ajax-url = "<?php echo admin_url('admin-ajax.php'); ?>" class="btn btn-enable approval_button">I approve this proof for production</button>
        <span class="disclaimer">when this button is green click to continue</span>
    </div>                    
</div>
