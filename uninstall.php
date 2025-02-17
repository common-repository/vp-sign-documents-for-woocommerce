<?php

// If uninstall not called from WordPress exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

//Check if we need to delete anything
$wc_vp_sign_documents_settings = get_option( 'woocommerce_wc_vp_sign_documents_settings', null );
if($wc_vp_sign_documents_settings['uninstall'] && $wc_vp_sign_documents_settings['uninstall'] == 'yes') {
	// Delete admin notices
	delete_metadata( 'user', 0, 'wc_vp_sign_documents_admin_notices', '', true );

	//Delete options
	$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'wc\_vp_sign_documents\_%';");
	$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_wc\_vp_sign_documents\_%';");
	delete_option('woocommerce_wc_vp_sign_documents_settings');
}
