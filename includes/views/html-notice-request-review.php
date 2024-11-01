<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="notice notice-info wc-vp-sign-documents-notice wc-vp-sign-documents-request-review">
	<p>⭐️ <?php printf( __( 'Hey, I noticed you are using VP Sign Documents for a few days now - that’s awesome! Could you please do me a BIG favor and give it a 5-star rating on WordPress to help us spread the word and boost our motivation?', 'wc-vp-sign-documents' ), '<strong>', '</strong>' ); ?></p>
	<p>
		<a class="button-primary" target="_blank" rel="noopener noreferrer" href="https://wordpress.org/support/plugin/vp-sign-documents-for-woocommerce/reviews/?filter=5#new-post"><?php esc_html_e( 'Ok, you deserve it', 'wc-vp-sign-documents' ); ?></a>
		<a class="button-secondary wc-vp-sign-documents-hide-notice remind-later" data-nonce="<?php echo wp_create_nonce( 'wc-vp-sign-documents-hide-notice' )?>" data-notice="request_review" href="#"><?php esc_html_e( 'Remind me later', 'wc-vp-sign-documents' ); ?></a>
		<a class="button-secondary wc-vp-sign-documents-hide-notice" data-nonce="<?php echo wp_create_nonce( 'wc-vp-sign-documents-hide-notice' )?>" data-notice="request_review" href="#"><?php esc_html_e( 'No, thanks', 'wc-vp-sign-documents' ); ?></a>
	</p>
</div>
