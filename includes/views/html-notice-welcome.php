<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="notice notice-info wc-vp-sign-documents-notice wc-vp-sign-documents-welcome">
	<div class="wc-vp-sign-documents-welcome-body">
    <button type="button" class="notice-dismiss wc-vp-sign-documents-hide-notice" data-nonce="<?php echo wp_create_nonce( 'wc-vp-sign-documents-hide-notice' )?>" data-notice="welcome"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss', 'woocommerce' ); ?></span></button>
		<h2><?php esc_html_e('VP Sign Documents PRO', 'wc-vp-sign-documents'); ?></h2>
		<p><?php esc_html_e("Thank you for installing this extension. If you don't know it already, theres a PRO version of this plugin, which offers more functions, for example the ability to create multiple documents, attach documents to WooCommerce emails, setup conditional logic and more. To use this extension, please check out the settings page and configure it first.", 'wc-vp-sign-documents'); ?></p>
		<p>
			<a class="button-primary" target="_blank" rel="noopener noreferrer" href="https://visztpeter.me/"><?php esc_html_e( 'Buy the PRO version', 'woocommerce' ); ?></a>
			<a class="button-secondary" href="<?php echo esc_url(admin_url( wp_nonce_url('admin.php?page=wc-settings&tab=wc_vp_sign_documents&welcome=1', 'wc-vp-sign-documents-hide-notice' ) )); ?>"><?php esc_html_e( 'Settings', 'wc-vp-sign-documents' ); ?></a>
		</p>
	</div>
</div>
