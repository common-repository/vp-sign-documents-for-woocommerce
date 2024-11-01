<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="wc-vp-sign-documents-section-pro <?php if(get_option('_wc_vp_sign_documents_pro_enabled')): ?>wc-vp-sign-documents-section-pro-active<?php endif; ?>">
  <div class="notice notice-error inline" style="display:none;"><p></p></div>
  <div class="wc-vp-sign-documents-section-pro-flex">
    <?php if(get_option('_wc_vp_sign_documents_pro_enabled')): ?>
      <div class="wc-vp-sign-documents-section-pro-activated">
        <strong>Pro version is active</strong>
        <small><?php echo esc_html(get_option('_wc_vp_sign_documents_pro_key')); ?> / <?php echo esc_html(get_option('_wc_vp_sign_documents_pro_email')); ?></small>
      </div>
      <div class="wc-vp-sign-documents-section-pro-activated-buttons">
        <a href="https://visztpeter.me/documentation" target="_blank" class="button-primary"><?php _e('Support', 'wc-vp-sign-documents'); ?></a>
        <button class="button-secondary" type="button" name="wc-vp-sign-documents-field-pro-deactivate" id="wc-vp-sign-documents-field-pro-deactivate"><?php _e('Deactivate', 'wc-vp-sign-documents'); ?></button>
      </div>
    <?php else: ?>
      <div class="wc-vp-sign-documents-section-pro-form">
        <h3>Sign Documents PRO version</h3>
        <p>If already purchased, simply enter your license key and email address:</p>
        <fieldset>
          <input class="input-text regular-input" type="text" name="wc_vp_sign_documents_pro_key" id="wc_vp_sign_documents_pro_key" value="" placeholder="License key"><br>
          <input class="input-text regular-input" type="text" name="wc_vp_sign_documents_pro_email" id="wc_vp_sign_documents_pro_email" value="" placeholder="E-mail address of your purchase">
          <p><button class="button-primary" type="button" name="wc-vp-sign-documents-field-pro-submit" id="wc-vp-sign-documents-field-pro-submit"><?php _e('Activate', 'wc-vp-sign-documents'); ?></button></p>
        </fieldset>
      </div>
      <div class="wc-vp-sign-documents-section-pro-cta">
        <h4>Why should i buy it?</h4>
        <ul>
          <li>Create multiple documents</li>
          <li>Attach documents to WooCommerce emails</li>
          <li>Setup conditional logic based on payment and shipping methods</li>
          <li>Change order status after document is signed</li>
          <li>Upload documents to cloud storage automatically</li>
        </ul>
        <div class="wc-vp-sign-documents-section-pro-cta-button">
          <a href="https://visztpeter.me">Buy the PRO version</a>
          <span>
            <strong>$59</strong>
          </span>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
