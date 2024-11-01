<?php
/*
Plugin Name: VP Sign Documents for WooCommerce
Plugin URI: http://visztpeter.me
Description: Create documents that can be signed by your customers for each order
Author: Viszt Péter
Version: 1.0
WC requires at least: 3.0.0
WC tested up to: 3.7.0
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//Generate stuff on plugin activation
function wc_vp_sign_documents_activate() {
	$upload_dir =  wp_upload_dir();

	$files = array(
		array(
			'base' 		=> $upload_dir['basedir'] . '/wc_vp_sign_documents',
			'file' 		=> 'index.html',
			'content' 	=> ''
		)
	);

	foreach ( $files as $file ) {
		if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
			if ( $file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ) ) {
				fwrite( $file_handle, $file['content'] );
				fclose( $file_handle );
			}
		}
	}
}
register_activation_hook( __FILE__, 'wc_vp_sign_documents_activate' );

class WC_VP_SignDocuments {

	public static $plugin_prefix;
	public static $plugin_url;
	public static $plugin_path;
	public static $plugin_basename;
	public static $version;
	public static $activation_url;
	protected static $background_generator = null;
	public $auth = null;

	protected static $_instance = null;

	//Get main instance
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

  //Construct
	public function __construct() {

		//Default variables
		self::$plugin_prefix = 'wc_vp_sign_documents_';
		self::$plugin_basename = plugin_basename(__FILE__);
		self::$plugin_url = plugin_dir_url(self::$plugin_basename);
		self::$plugin_path = trailingslashit(dirname(__FILE__));
		self::$version = '1.0';
		self::$activation_url = 'https://szamlazz.visztpeter.me/';

		//Plugin loaded
		add_action( 'plugins_loaded', array( $this, 'init' ) );

  }

	//Load plugin stuff
	public function init() {

		//Load files
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-settings-page.php' );
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-settings-page-table.php' );

		//Create post type
		add_action( 'init', array( $this, 'create_post_type' ) );

		//Admin CSS & JS
		add_action( 'admin_init', array( $this, 'admin_init' ) );

		//Show the document to sign
		add_action( 'init', array( $this, 'render_document' ) );

		//Ajax function to handle the signature
		add_action( 'wp_ajax_wc_vp_sign_documents_save', array( $this, 'save_document' ) );
		add_action( 'wp_ajax_nopriv_wc_vp_sign_documents_save', array( $this, 'save_document' ) );

		// Load includes
		if(is_admin()) {
			require_once( plugin_dir_path( __FILE__ ) . 'includes/class-admin-notices.php' );
		}

		//Create order metabox
		add_action( 'add_meta_boxes', array( $this, 'add_metabox' ), 10, 2 );

		//Load settings page
		add_filter( 'woocommerce_get_settings_pages', array( $this, 'load_settings_page' ) );

		//Add orders column
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_orders_column' ) );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'display_orders_column' ), 2 );

		//Auto reload metabox
		add_filter( 'heartbeat_received', array( $this, 'reload_metabox' ), 10, 2 );

		//Plugin links
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );

		//Check and save PRO version
		add_action( 'wp_ajax_wc_vp_sign_documents_pro_check', array( $this, 'pro_check' ) );
		add_action( 'wp_ajax_wc_vp_sign_documents_pro_deactivate', array( $this, 'pro_deactivate' ) );

		//Attach invoices to emails
		if(get_option('_wc_vp_sign_documents_pro_enabled')) {
			add_filter( 'woocommerce_email_attachments', array( $this, 'email_attachment_file'), 10, 3 );
		}

	}

	//Add CSS & JS
	public function admin_init() {
		wp_enqueue_script( 'wc_vp_sign_documents_js', plugins_url( '/assets/js/admin.js',__FILE__ ), array('jquery','qrcode'), WC_VP_SignDocuments::$version, TRUE );
		wp_enqueue_style( 'wc_vp_sign_documents_css', plugins_url( '/assets/css/admin.css',__FILE__ ), array(), WC_VP_SignDocuments::$version );

		$wc_vp_sign_documents_local = array( 'loading' => plugins_url( '/assets/images/ajax-loader.gif',__FILE__ ) );
		wp_localize_script( 'wc_vp_sign_documents_js', 'wc_vp_sign_documents_params', $wc_vp_sign_documents_local );

		if ( isset( $_GET['preview_wc_vp_sign_documents'] ) ) {
			if ( ! ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'preview-document' ) ) ) {
				die( 'Security check' );
			}

			//Setup parameters
			$preview = true;
			$document_id = absint( $_GET['document'] );
			$document = $this->get_document($document_id);
			$order = $this->get_sample_order();

			//Get document page content
			$page_content = $this->get_document_page_content($document, $order, $preview);

			echo $page_content;
			exit;
		}
	}

	public function get_sample_order() {
		$orders = wc_get_orders( array(
			'limit' => 1
		));
		return $orders[0];
	}

	//Create the post type
	public function create_post_type() {
		$args = array(
			'public' => true,
			'label'  => __('Documents', 'wc-vp-sign-documents')
		);
		register_post_type( 'wc-vp-sign-document', $args );
	}

	public function load_settings_page($settings) {
		$settings[] = include plugin_dir_path( __FILE__ ) . 'includes/class-settings.php';
		return $settings;
	}

	public function get_document($id) {
		$defaults = array(
			'id' => $id,
			'name' => '',
			'content' => '',
			'emails' => array(),
			'payment_methods' => array(),
			'shipping_methods' => array(),
			'order_status' => 'no',
			'status' => 'publish',
			'emailitin' => ''
		);
		$document_data = array();

		//If theres an existing document
		if($id) {
			$document = get_post($id);
			$document_data = array(
				'name' => $document->post_title,
				'content' => $document->post_content,
				'status' => $document->post_status,
				'emails' => get_post_meta($id, '_wc_vp_sign_documents_emails', true),
				'payment_methods' => get_post_meta($id, '_wc_vp_sign_documents_payment_methods', true),
				'shipping_methods' => get_post_meta($id, '_wc_vp_sign_documents_shipping_methods', true),
				'order_status' => get_post_meta($id, '_wc_vp_sign_documents_order_status', true),
				'emailitin' => get_post_meta($id, '_wc_vp_sign_documents_emailitin', true)
			);
		}

		return wp_parse_args( $document_data, $defaults );
	}

	public function get_documents($args = array()) {
		$defaults = array(
		  'numberposts' => -1,
		  'post_type' => 'wc-vp-sign-document',
			'post_status' => 'any'
		);

		$args = wp_parse_args( $args, $defaults );

		$documents_posts = get_posts( $args );
		$documents = array();

		foreach ($documents_posts as $document) {
			$documents[] = $this->get_document($document->ID);
		}

		return $documents;
	}

	public function replace_document_placeholders($document, $order, $signatures = false) {
		ob_start();
		woocommerce_order_details_table($order->get_id());
		$order_items = ob_get_contents();
		ob_end_clean();

		$now_dt = new WC_DateTime();

		$note_replacements = array(
			'{customer_first_name}' => $order->get_billing_first_name(),
			'{customer_last_name}' => $order->get_billing_last_name(),
			'{customer_name}' => $order->get_formatted_billing_full_name(),
			'{order_number}' => $order->get_order_number(),
			'{order_date}' => wc_format_datetime( $order->get_date_created() ),
			'{current_date}' => wc_format_datetime( $now_dt )
		);

		//Replace signature pads with canvas
		if(!$signatures) {
			$note_replacements['{signature}'] = '<div class="signature-pad"><canvas height="130" width="720"></canvas></div>';
		}

		//Replace the placeholders
		$document['content'] = str_replace( array_keys( $note_replacements ), array_values( $note_replacements ), $document['content']);

		//If we have signatures, replaces each {signature} with an image
		if($signatures) {
			foreach ($signatures as $signature) {
			  $from = '/'.preg_quote('{signature}', '/').'/';
				$replacement = '<div class="signature-pad" data-signature="'.$signature.'"><canvas height="130" width="720"></canvas></div>';
			  $document['content'] = preg_replace($from, $replacement, $document['content'], 1);
			}
		}

		//Create <p> tags
		$allowed_html_tags = wp_kses_allowed_html( 'post' );
		unset($allowed_html_tags['section']);
		$allowed_html_tags['canvas'] = array(
			'width' => array(),
			'height' => array(),
		);
		$document_content = wpautop(wp_kses($document['content'], $allowed_html_tags));

		//Replace order items
		$document_content = str_replace('{order_items}', wp_kses($order_items, $allowed_html_tags), $document_content);
		$document['content'] = $document_content;

		return $document;
	}

	//Meta box on order page
	public function add_metabox( $post_type, $post ) {
		add_meta_box('wc_vp_sign_documents_metabox', 'Sign Documents', array( $this, 'render_meta_box_content' ), 'shop_order', 'side');
	}

	//Render metabox content
	public function render_meta_box_content($post) {
		$order = wc_get_order($post->ID);
		$documents = $this->get_order_documents($order);
		include( dirname( __FILE__ ) . '/includes/views/html-metabox.php' );
	}

	public function get_order_documents($order) {
		$documents = $this->get_documents(array('post_status' => 'publish'));
		$payment_method_id = $order->get_payment_method();
		$shipping_methods = $order->get_items( 'shipping' );
		$shipping_method_id = false;
		if($shipping_methods) $shipping_method_id = reset($shipping_methods)->get_method_id();

		//Get already signed documents
		$signed_documents = $order->get_meta('_wc_vp_signed_documents');
		if(!$signed_documents) $signed_documents = array();

		//Create new array of documents to show
		$order_documents = array();

		//Check each document to see if payment method / shipping megthod matches condotional logic
		foreach ($documents as $document) {
			$show_document = false;

			//If payment method id is a match
			if($document['payment_methods'] && in_array($payment_method_id, $document['payment_methods'])) {
				$show_document = true;
			}

			//If shipping id is a match
			if($document['shipping_methods'] && in_array($shipping_method_id, $document['shipping_methods'])) {
				$show_document = true;
			}

			//If docuemnt doesn't have a conditional logic setup
			if(!$document['payment_methods'] && !$document['shipping_methods']) {
				$show_document = true;
			}

			//If its an already signed document
			foreach ($signed_documents as $signed_document_key => $signed_document) {
				if($signed_document['id'] == $document['id']) {
					$document = $signed_document;
					$show_document = true;
					unset($signed_documents[$signed_document_key]);
				}
			}

			//Add document to new array
			if($show_document) {
				$order_documents[] = $document;
			}
		}

		//Return both signed and unsigned documents
		$documents = $signed_documents + $order_documents;

		//Get fixed but random ID for each document that is related to this order
		foreach ($documents as $key => $document) {
			if(!isset($document['signed'])) {
				$documents[$key]['document_order_id'] = $this->get_document_order_id($order, $document);
			}
		}

		return $documents;
	}

	public function get_order_document($order, $document_order_id) {
		$documents = $this->get_order_documents($order);
		$single_document = false;
		foreach ($documents as $document) {
			if($document['document_order_id'] == $document_order_id) {
				$single_document = $document;
				break;
			}
		}
		return $single_document;
	}

	public function get_document_order_id($order, $document) {

		//Check if meta exists
		$document_order_id = $order->get_meta('_wc_vp_sign_document_'.$document['id']);

		//If not found, create one
		if(!$document_order_id) {
			$document_order_id = base_convert(wp_rand(), 10, 32);
			$order->update_meta_data('_wc_vp_sign_document_'.$document['id'], $document_order_id);
			$order->save();
		}

		return $document_order_id;
	}

	public function get_document_link($order, $document) {
		$document_order_id = $this->get_document_order_id($order, $document);
		return add_query_arg(
			array(
				'wc_vp_sign_document' => $order->get_id().'-'.$document_order_id,
			), get_home_url()
		);
	}

	public function render_document() {
		if ( isset( $_GET['wc_vp_sign_document'] ) && !empty( $_GET['wc_vp_sign_document'] ) ) {
			$parameters = sanitize_text_field($_GET['wc_vp_sign_document']);
			$parameters = explode('-', $parameters);
			$order_id = absint($parameters[0]);
			$document_order_id = $parameters[1];
			$order = wc_get_order($order_id);
			$document = $this->get_order_document($order, $document_order_id);

			//Return blank page if document or order doesn't exists or document is already signed
			if(!$document || !$order || isset($document['signed'])) {
				die();
			}

			//Get document page content
			$page_content = $this->get_document_page_content($document, $order);

			echo $page_content;
			exit;
		}
	}

	public function get_document_page_content($document, $order, $preview = false, $signatures = false) {

		//Replace placeholders in document with selected order
		$document = $this->replace_document_placeholders($document, $order, $signatures);

		//Assets
		$css = plugins_url( '/assets/css/document.css',__FILE__ );
		$javascript = array();
		if(!$preview) $javascript[] = plugins_url( '/assets/js/html2pdf.js',__FILE__ );
		$javascript[] = plugins_url( '/assets/js/signature.min.js',__FILE__ );
		$javascript[] = plugins_url( '/assets/js/document.js',__FILE__ );

		ob_start();
		wc_get_template( 'html-document-template.php', array( 'order' => $order, 'preview' => $preview, 'css' => $css, 'javascript' => $javascript, 'document' => $document ), '', plugin_dir_path( __FILE__ ) . 'includes/views/' );
		$content = ob_get_clean();

		return $content;
	}

	public function save_document() {

		//Check if order id is submitted
		if(!isset($_POST['order_id'])) die();

		//Check nonce(which is based on order id)
		$order_id = intval($_POST['order_id']);
		$nonce_name = 'wc_vp_sign_documents_save_'.$order_id;
		check_ajax_referer( $nonce_name, 'nonce' );

		//Create response
		$response = array();
		$response['error'] = false;

		//Get submitted stuff
		$order = wc_get_order($order_id);
		$document_id = intval($_POST['document_id']);
		$signatures = array_filter( array_map( 'wc_clean', $_POST['signatures'] ) );

		//Document order id is in referer
		$url = parse_url(wp_get_referer());
		parse_str($url['query'], $path);
		$parameters = $path['wc_vp_sign_document'];
		$parameters = explode('-', $parameters);
		$referer_order_id = intval($parameters[0]);
		$referer_document_order_id = $parameters[1];
		$order_document = $this->get_order_document($order, $referer_document_order_id);

		$signed_documents = $order->get_meta('_wc_vp_signed_documents');
		if(!$signed_documents) $signed_documents = array();

		//If already signed
		if(isset($order_document['signed'])) {
			$response['error'] = true;
			$response['error_message'] = __('This document is already signed', 'wc-vp-sign-documents');
		}

		//Other security checks
		if(!$order_document || $referer_order_id != $order_id || $document_id != $order_document['id']) {
			$response['error'] = true;
			$response['error_message'] = __('This is not a valid document', 'wc-vp-sign-documents');
		}

		//Return error
		if($response['error']) {
			return $response;
		}

		//Create signed document
		$signed_document = $order_document;
		$signed_document['signed'] = true;
		$signed_document['audit'] = array(
			'ip' => WC_Geolocation::get_ip_address(),
			'user_agent' => wc_get_user_agent(),
			'date' => time(),
			'user_id' => get_current_user_id()
		);
		unset($signed_document['content']);

		//Create HTML
		$document_content = $this->get_document_page_content($order_document, $order, true, $signatures);

		//Create PDF
		$b64file = trim( str_replace( 'data:application/pdf;base64,', '', sanitize_text_field($_POST['pdf']) ) );
    $b64file = str_replace( ' ', '+', $b64file );
    $decoded_pdf = base64_decode( $b64file );

		//Save PDF and HTML
		$pdf_file_path = $this->get_file_path($signed_document['name'], $order_id, '.pdf');
		$html_file_path = $this->get_file_path($signed_document['name'], $order_id, '.html');
		file_put_contents($pdf_file_path['path'], $decoded_pdf);
		file_put_contents($html_file_path['path'], $document_content);

		//Store PDF and HTML links
		$signed_document['pdf'] = $pdf_file_path['name'];
		$signed_document['html'] = $html_file_path['name'];

		//Save signed document
		$signed_documents[] = $signed_document;
		$order->update_meta_data('_wc_vp_signed_documents', $signed_documents);

		//Save order notes
		$order->add_order_note(sprintf( esc_html__( 'The document called "%s" was signed by the customer.', 'wc-vp-sign-documents' ), $signed_document['name']));

		//Change order status if needed
		if($signed_document['order_status'] != '') {
			$target_status = str_replace( 'wc-', '', $signed_document['order_status']);
			$order->update_status( $target_status );
		}

		//Save order
		$order->save();

		//Add link to response
		$response['pdf'] = $this->generate_download_link($order, $signed_document['id']);

		//Send to emailitin if needed
		if($signed_document['emailitin']) {
			$this->emailt_it_in($order, $signed_document);
		}

		//Return
		wp_send_json_success($response);
	}

	//Get file path for pdf files
	public function get_file_path($document_name, $order_id, $extension = '.pdf') {
		$upload_dir = wp_upload_dir( null, false );
		$basedir = $upload_dir['basedir'] . '/wc_vp_sign_documents/';
		$baseurl = $upload_dir['baseurl'] . '/wc_vp_sign_documents/';
		$random_file_name = substr(md5(rand()),5);
		$pdf_file_name = implode( '-', array( sanitize_title($document_name), $order_id, $random_file_name ) ).$extension;
		$pdf_file_path = $basedir.$pdf_file_name;
		return array('name' => $pdf_file_name, 'path' => $pdf_file_path, 'baseurl' => $baseurl, 'basedir' => $basedir);
	}

	public function generate_download_link( $order, $document_id, $extension = 'pdf', $absolute = false) {
		if($order) {
			$file_name = '';
			$signed_documents = $order->get_meta('_wc_vp_signed_documents');
			foreach ($signed_documents as $signed_document) {
				if($signed_document['id'] == $document_id) {
					$file_name = $signed_document[$extension];
				}
			}

			if($file_name) {
				$paths = $this->get_file_path('invoice', 0);
				if($absolute) {
					$file_url = $paths['basedir'].$file_name;
				} else {
					$file_url = $paths['baseurl'].$file_name;
				}
				return apply_filters('wc_vp_sign_documents_download_link', esc_url($file_url), $order);
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	public function add_orders_column($columns) {
		$new_columns = ( is_array( $columns ) ) ? $columns : array();

		//Remove order actions
  	unset( $new_columns[ 'wc_actions' ] );

		//Add documents column
  	$new_columns['wc_vp_sign_documents'] = __('Documents', 'wc-vp-sign-docuemnts');

  	//Add back order actions
  	$new_columns[ 'wc_actions' ] = $columns[ 'wc_actions' ];
  	return $new_columns;
	}

	public function display_orders_column($column) {
		global $post;
		if ( 'wc_vp_sign_documents' === $column ) {
			$order = wc_get_order( $post->ID );
			$documents = $this->get_order_documents($order);
			$unsigned = false;
			foreach ($documents as $document) {
				if(!isset($document['signed'])) {
					$unsigned = $document;
				}
			}
			?>

			<div>
				<?php if($unsigned): ?>
					<a target="_blank" href="<?php echo esc_url($this->get_document_link($order, $unsigned)); ?>" class="order-status status-on-hold tips" data-tip="<?php printf( esc_attr__( 'The document called "%s" is not signed yet.', 'wc-vp-sign-documents' ), esc_attr($unsigned['name'])); ?>"><span><?php esc_html_e('Unsigned', 'wc-vp-sign-docuemnts'); ?></span></a>
				<?php endif; ?>
				<?php foreach ($documents as $document): ?>
					<?php if(isset($document['signed'])): ?>
					<a target="_blank" href="<?php echo esc_url($this->generate_download_link($order, $document['id'])); ?>" class="order-status status-completed tips" data-tip="<?php echo esc_attr($document['name']); ?>"><span></span></a>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
			<?php
		}
	}

	public function reload_metabox($response, $data) {
    if (empty($data['wc_vp_sign_documents'])) {
			return $response;
    }

		//Check if document is signed
		$post_id = intval($data['wc_vp_sign_documents']);
		$order = wc_get_order($post_id);
		if(!$order) {
			return $response;
		}

		$signed_documents = $order->get_meta('_wc_vp_signed_documents');
		$signed_documents_simple = array();
		foreach ($signed_documents as $signed_document) {
			$signed_documents_simple[] = array(
				'id' => $signed_document['id'],
				'link' => $this->generate_download_link($order, $signed_document['id'])
			);
		}

    $response['_wc_vp_signed_documents'] = $signed_documents_simple;
    return $response;
	}

	//Plugin links
	public function plugin_action_links( $links ) {
		$action_links = array(
			'settings' => '<a href="' . esc_url(admin_url( 'admin.php?page=wc-settings&tab=wc_vp_sign_documents' )) . '" aria-label="' . esc_attr__( 'Settings', 'wc-vp-sign-documents' ) . '">' . esc_html__( 'Settings', 'wc-vp-sign-documents' ) . '</a>',
			'documentation' => '<a href="https://visztpeter.me/documentation/" target="_blank" aria-label="' . esc_attr__( 'Documentation', 'wc-vp-sign-documents' ) . '">' . esc_html__( 'Documentation', 'wc-vp-sign-documents' ) . '</a>'
		);

		if (!get_option('_wc_vp_sign_documents_pro_enabled') ) {
			$action_links['get-pro'] = '<a target="_blank" rel="noopener noreferrer" style="color:#46b450;" href="https://visztpeter.me/" aria-label="' . esc_attr__( 'PRO version', 'wc-vp-sign-documents' ) . '">' . esc_html__( 'PRO version', 'wc-vp-sign-documents' ) . '</a>';
		}
		return array_merge( $action_links, $links );
	}

	//Emailt it in
	public function emailt_it_in($order, $signed_document) {
		$emailt_it_in_address = $signed_document['emailitin'];
		$pdf_file = $this->generate_download_link($order, $signed_document['id'], 'pdf', true);

		//Send the email
		wp_mail( $emailt_it_in_address, $signed_document['name'].' - '.$order->get_order_number(), $signed_document['name'], '', array($pdf_file) );
	}

	public function pro_check() {
		if ( !current_user_can( 'edit_shop_orders' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		$pro_key = sanitize_text_field($_POST['key']);
		$pro_email = sanitize_email($_POST['email']);
		$args = array(
			'request' => 'activation',
			'email' => $pro_email,
			'licence_key' => $pro_key,
			'product_id' => 'WC_VP_SIGN_DOCUMENTS'
		);

		//Execute request (function below)
		$base_url = add_query_arg('wc-api', 'software-api', WC_VP_SignDocuments::$activation_url);
		$target_url = $base_url . '&' . http_build_query( $args );
		$data = wp_remote_get( $target_url );
		$result = json_decode($data['body']);
		if(isset($result->activated) && $result->activated) {

			//Store the key and email
			update_option('_wc_vp_sign_documents_pro_key', $pro_key);
			update_option('_wc_vp_sign_documents_pro_email', $pro_email);
			update_option('_wc_vp_sign_documents_pro_enabled', true);
			wp_send_json_success();

		} else {
			wp_send_json_error(array(
				'message' => __('Unable to activate the PRO version. Please check the submitted data and try again.', 'wc-vp-sign-documents')
			));

		}
	}

	public function pro_deactivate() {
		if ( !current_user_can( 'edit_shop_orders' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		$pro_key = get_option('_wc_vp_sign_documents_pro_key');
		$pro_email = get_option('_wc_vp_sign_documents_pro_email');

		$args = array(
			'request' => 'activation_reset',
			'email' => $pro_email,
			'licence_key' => $pro_key,
			'product_id' => 'WC_VP_SIGN_DOCUMENTS'
		);

		//Execute request (function below)
		$base_url = add_query_arg('wc-api', 'software-api', WC_VP_SignDocuments::$activation_url);
		$target_url = $base_url . '&' . http_build_query( $args );
		$data = wp_remote_get( $target_url );
		$result = json_decode($data['body']);

		if(isset($result->reset) && $result->reset) {

			//Store the key and email
			delete_option('_wc_vp_sign_documents_pro_key');
			delete_option('_wc_vp_sign_documents_pro_email');
			delete_option('_wc_vp_sign_documents_pro_enabled');

			wp_send_json_success();

		} else {

			wp_send_json_error(array(
				'message' => __('Unable to deactivate the PRO version. Please try again later.', 'wc-vp-sign-documents')
			));

		}

	}

	//Email attachment file
	public function email_attachment_file($attachments, $email_id, $order){
		if(!is_a( $order, 'WC_Order' )) return $attachments;
		$order_id = $order->get_id();
		$order = wc_get_order($order_id);
		$documents = $this->get_documents();
		$order_documents = $this->get_order_documents($order);

		//Check if document needs to be attached to an email
		$document_ids_to_send = array();
		foreach($documents as $document) {
			if($document['emails'] && in_array($email_id, $document['emails'])) {
				$document_ids_to_send[] = $document['id'];
			}
		}

		//If we need to send some
		foreach($order_documents as $order_document) {
			if($order_document['signed'] && in_array($order_document['id'], $document_ids_to_send)) {
				$attachments[] = $this->generate_download_link($order, $order_document['id'], 'pdf', true);
			}
		}

		return $attachments;
	}

}

//WC Detection
if ( ! function_exists( 'is_woocommerce_active' ) ) {
	function is_woocommerce_active() {
		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}

		return in_array( 'woocommerce/woocommerce.php', $active_plugins ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins ) ;
	}
}


//WooCommerce inactive notice.
function wc_vp_sign_documents_woocommerce_inactive_notice() {
	if ( current_user_can( 'activate_plugins' ) ) {
		echo '<div id="message" class="error"><p>';
		printf( __( '%1$sVP Sign Documents for WooCommerce is inactive%2$s. %3$sWooCommerce %4$s needs to be installed and activated. %5$sInstall and turn on WooCommerce &raquo;%6$s', 'wc-vp-sign-documents' ), '<strong>', '</strong>', '<a href="http://wordpress.org/extend/plugins/woocommerce/">', '</a>', '<a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">', '</a>' );
		echo '</p></div>';
	}
}

//Initialize
if ( is_woocommerce_active() ) {
	function WC_VP_SignDocuments() {
		return WC_VP_SignDocuments::instance();
	}

	//For backward compatibility
	$GLOBALS['wc_vp_sign_documents'] = WC_VP_SignDocuments();
} else {
	add_action( 'admin_notices', 'wc_vp_sign_documents_woocommerce_inactive_notice' );
}
