<?php

if ( class_exists( 'WC_VP_SignDocuments_Settings', false ) ) {
	return new WC_VP_SignDocuments_Settings();
}

class WC_VP_SignDocuments_Settings extends WC_Settings_Page {
	public static $activation_url;

	public function __construct() {
		$this->id    = 'wc_vp_sign_documents';
		$this->label = __( 'Documents', 'wc-vp-sign-documents' );

		self::$activation_url = 'https://visztpeter.me/';

		parent::__construct();
		$this->notices();
		$this->init();
	}

	public static function init() {
		add_action( 'woocommerce_admin_field_wc_vp_sign_documents_content', array( __CLASS__, 'editor_field' ));
	}

	private function notices() {
		if ( isset( $_GET['tab'] ) && 'wc_vp_sign_documents' === $_GET['tab'] ) { // WPCS: input var okay, CSRF ok.
			WC_VP_SignDocuments_Settings_Page::notices();
		}
	}

	public function get_settings( $current_section = '' ) {
		$settings = array();
		return $settings;
	}

	public function output() {
		global $current_section;
		WC_VP_SignDocuments_Settings_Page::page_output();
	}

	public static function editor_field( $data ) {
		$settings = array (
			'textarea_rows' => 20,
			'tinymce' => array(
				'content_css' => WC_VP_SignDocuments()::$plugin_url . 'assets/css/editor-style.css?v=3'
			)
		);
		include 'views/html-field-editor.php';
  }

}

return new WC_VP_SignDocuments_Settings();
