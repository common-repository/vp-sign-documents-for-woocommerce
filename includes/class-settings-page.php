<?php
defined( 'ABSPATH' ) || exit;

class WC_VP_SignDocuments_Settings_Page {

  //Init hooks and stuff
  public function __construct() {
    add_action( 'admin_init', array( $this, 'actions' ) );
    add_action( 'woocommerce_settings_page_init', array( $this, 'screen_option' ) );
    add_filter( 'woocommerce_save_settings_advanced_webhooks', array( $this, 'allow_save_settings' ) );
  }

  //Check if we are on the edit page
  public function allow_save_settings( $allow ) {
    if ( ! isset( $_GET['edit-document'] ) ) {
      return false;
    }

    return $allow;
  }

  //Check if its the sign document settings page
  private function is_document_settings_page() {
    return isset( $_GET['page'], $_GET['tab'] ) && 'wc-settings' === $_GET['page'] && 'wc_vp_sign_documents' === $_GET['tab'];
  }

  //Save settings
  private function save() {
    check_admin_referer( 'woocommerce-settings' );

    if ( ! current_user_can( 'manage_woocommerce' ) ) {
      wp_die( esc_html__( 'You do not have permission to update documents', 'wc-vp-sign-documents' ) );
    }

    $errors = array();
    $document_id = isset( $_POST['document_id'] ) ? absint( $_POST['document_id'] ) : 0;
    $document = array(
      'ID' => $document_id,
      'post_type' => 'wc-vp-sign-document'
    );

    //PRO check
    if(!get_option('_wc_vp_sign_documents_pro_enabled') && wp_count_posts('wc-vp-sign-document') > 0) {
      wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=wc_vp_sign_documents&edit-document=' . $document_id . '&error=' . rawurlencode( __('You can only create one document with the free version', 'wc-vp-sign-documents') ) ) );
      exit();
    }

    // Name
    if ( !empty( $_POST['document_name'] ) ) {
      $document['post_title'] = sanitize_text_field( wp_unslash( $_POST['document_name'] ) );
    } else {
      $document['post_title'] = sprintf(__( 'Document created on %s', 'wc-vp-sign-documents' ), strftime( '%b %d, %Y @ %I:%M %p'));
    }

    //Content
    $document['post_content'] = wp_kses_post( $_POST['document_content'] );

    //Status
    $document['post_status'] = sanitize_text_field( wp_unslash($_POST['document_status']) );

    //Create or update document basic data
    $document_id = wp_insert_post($document);

    //Save custom meta too(only required for pro versions)
    if(get_option('_wc_vp_sign_documents_pro_enabled')) {
      $multi_selects = array('emails', 'payment_methods', 'shipping_methods');
      foreach ($multi_selects as $multi_select) {
        if ( !empty( $_POST['document_'.$multi_select] ) ) {
          $document_value = array_filter( array_map( 'wc_clean', $_POST['document_'.$multi_select] ) );
          update_post_meta($document_id, '_wc_vp_sign_documents_'.$multi_select, $document_value);
        } else {
          update_post_meta($document_id, '_wc_vp_sign_documents_'.$multi_select, false);
        }
      }
      $document_order_status = sanitize_text_field( wp_unslash($_POST['document_order_status']) );
      $document_emailitin = sanitize_email( wp_unslash($_POST['document_emailitin']) );
      update_post_meta($document_id, '_wc_vp_sign_documents_order_status', $document_order_status);
      update_post_meta($document_id, '_wc_vp_sign_documents_emailitin', $document_emailitin);
    }

    // Redirect to webhook edit page to avoid settings save actions.
    if($document_id != absint( $_POST['document_id'] )) {
      wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=wc_vp_sign_documents&edit-document=' . $document_id . '&created=1' ) );
    } else {
      wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=wc_vp_sign_documents&edit-document=' . $document_id . '&updated=1' ) );
    }
    exit();
  }

  //Delete documents in bulk
  public static function bulk_delete( $documents ) {
    foreach ( $documents as $document_id ) {
      wp_delete_post( $document_id, true );
    }

    $qty    = count( $documents );
    $status = isset( $_GET['status'] ) ? '&status=' . sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';

    //Redirect to webhooks page.
    wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=wc_vp_sign_documents' . $status . '&deleted=' . $qty ) );
    exit();
  }

  //Delete single document
  private function delete() {
    check_admin_referer( 'delete-document' );

    if ( isset( $_GET['delete'] ) ) { // WPCS: input var okay, CSRF ok.
      $document_id = absint( $_GET['delete'] ); // WPCS: input var okay, CSRF ok.

      if ( $document_id ) {
        $this->bulk_delete( array( $document_id ) );
      }
    }
  }

  //Save and delete actions
  public function actions() {
    if ( $this->is_document_settings_page() ) {

      //Save
      if ( isset( $_POST['save'] ) && isset( $_POST['document_id'] ) ) {
        $this->save();
      }

      //Delete
      if ( isset( $_GET['delete'] ) ) {
        $this->delete();
      }

    }
  }

  //Display edit page or table
  public static function page_output() {

    // Hide the save button.
    $GLOBALS['hide_save_button'] = true;

    //Show edit form instead of table
    if ( isset( $_GET['edit-document'] ) ) {
      $document_id = absint( $_GET['edit-document'] );
      $document    = WC_VP_SignDocuments()->get_document($document_id);
      $settings    = self::init_form_fields($document);

      include 'views/html-documents-edit.php';
      return;
    }

    //Show table
    self::table_list_output();
  }

  //Edit fields
  public static function init_form_fields($document) {
    if(!$document['content']) {
      $document['content'] = '<h1>Terms & conditions sample</h1><p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p><p>{signature}</p><p>Signed by {customer_name}<br>Signed on {current_date}</p>';
    }

    $pro_required = array();
    $pro_icon = false;
    if(!get_option('_wc_vp_sign_documents_pro_enabled')) {
      $pro_required = array(
        'disabled' => 'disabled'
      );
      $pro_icon = '';
    }

		$options = array(
			array(
				'title'    => __( 'Document Name', 'wc-vp-sign-documents' ),
				'type'     => 'text',
				'desc_tip'     => __( 'Friendly name for identifying this document.', 'wc-vp-sign-documents' ),
				'id'       => 'document_name',
				'value'		 => $document['name']
			),
			array(
				'title'    => __( 'Document Content', 'wc-vp-sign-documents' ),
				'type'     => 'wc_vp_sign_documents_content',
				'id'       => 'document_content',
				'value'		 => $document['content']
			),
      array(
        'id' => 'document_status',
				'type' => 'select',
				'title' => __( 'Document Status', 'wc-vp-sign-documents' ),
				'class' => 'wc-enhanced-select',
				'default' => 'publish',
				'options'  => array(
          'publish' => __('Active', 'wc-vp-sign-documents'),
          'draft' => __('Disabled', 'wc-vp-sign-documents')
        ),
        'value'		 => $document['status'],
				'desc_tip' => __( 'Use the disabled option if you are just setting this up and want to check the preview first.', 'wc-vp-sign-documents' ),
			),
			array(
        'id' => 'document_emails',
        'type' => 'multiselect',
        'title'    => __( 'Attach signed document to WooCommerce emails', 'wc-vp-sign-documents' ).$pro_icon,
				'class' => 'wc-enhanced-select',
				'options'  => self::get_email_types(),
        'value'		 => $document['emails'],
        'desc_tip' => __( 'Select one or multiple WooCommerce emails that you want this signed document attached to.', 'wc-vp-sign-documents'),
        'custom_attributes' => $pro_required
			),
			array(
        'id' => 'document_payment_methods',
        'type' => 'multiselect',
        'title'    => __( 'Require signature based on payment methods', 'wc-vp-sign-documents' ).$pro_icon,
				'class' => 'wc-enhanced-select',
				'options'  => self::get_payment_methods(),
        'value'		 => $document['payment_methods'],
        'desc_tip' => __( 'Select one or more payment methods as a conditional logic. If the order matches the payment method, it will require a signature.', 'wc-vp-sign-documents'),
        'custom_attributes' => $pro_required
			),
			array(
        'id' => 'document_shipping_methods',
        'type' => 'multiselect',
        'title'    => __( 'Require signature based on shipping methods', 'wc-vp-sign-documents' ).$pro_icon,
				'class' => 'wc-enhanced-select',
				'options'  => self::get_shipping_methods(),
        'value'		 => $document['shipping_methods'],
        'desc_tip' => __( 'Select one or more shipping methods as a conditional logic. If the order matches the shipping method, it will require a signature.', 'wc-vp-sign-documents'),
        'custom_attributes' => $pro_required
			),
			array(
        'id' => 'document_order_status',
				'type' => 'select',
				'title' => __( 'Order status after the document is signed', 'wc-vp-sign-documents' ).$pro_icon,
				'class' => 'wc-enhanced-select',
				'default' => 'no',
				'options'  => self::get_order_statuses(),
        'value'		 => $document['order_status'],
				'desc_tip' => __( 'After the document is signed and submitted, the order status will change to this automatically.', 'wc-vp-sign-documents' ),
        'custom_attributes' => $pro_required
			),
      array(
        'title'    => __( 'Email It In address', 'wc-vp-sign-documents' ),
        'type'     => 'text',
        'desc_tip'     => __( 'Enter your Email It In address and all signed documents will be uploaded to your cloud storage automatically.', 'wc-vp-sign-documents' ),
        'id'       => 'document_emailitin',
        'value'		 => $document['emailitin'],
        'custom_attributes' => $pro_required
      ),
		);


    return $options;
	}

  //Get email ids
  public static function get_email_types() {
    $mailer = WC()->mailer();
    $email_templates = $mailer->get_emails();
    $emails = array();
    $disabled = ['failed_order', 'customer_note', 'customer_reset_password', 'customer_new_account'];
    foreach ( $email_templates as $email ) {
      if(!in_array($email->id,$disabled)) {
        $emails[$email->id] = $email->get_title();
      }
    }

    return $emails;
  }

  //Get payment methods
  public static function get_payment_methods() {
    $available_gateways = WC()->payment_gateways->payment_gateways();
		$payment_methods = array();
		foreach ($available_gateways as $available_gateway) {
			if($available_gateway->enabled == 'yes') {
				$payment_methods[$available_gateway->id] = $available_gateway->title;
			}
		}
    return $payment_methods;
  }

  //Get shipping methods
  public static function get_shipping_methods() {
    $available_gateways = WC()->shipping->load_shipping_methods();
    $shipping_methods = array();
    foreach ($available_gateways as $available_gateway) {
      if(isset( $available_gateway->enabled ) && $available_gateway->enabled == 'yes') {
        $shipping_methods[$available_gateway->id] = $available_gateway->method_title;
      }
    }
    return $shipping_methods;
  }

  //Get order statues
  public static function get_order_statuses() {
    $built_in_statuses = array();
    if(function_exists('wc_order_status_manager_get_order_status_posts')) {
      $filtered_statuses = array();
      $custom_statuses = wc_order_status_manager_get_order_status_posts();
      foreach ($custom_statuses as $status ) {
        $filtered_statuses[ 'wc-' . $status->post_name ] = $status->post_title;
      }
      $built_in_statuses = $built_in_statuses;
    } else {
      $built_in_statuses = wc_get_order_statuses();
    }

    return array("no"=>__("Don't change status", 'wc-vp-sign-documents')) + $built_in_statuses;
  }

  //Show adminnotices
  public static function notices() {
    if ( isset( $_GET['deleted'] ) ) {
      $deleted = absint( $_GET['deleted'] );
      WC_Admin_Settings::add_message( sprintf( _n( '%d document permanently deleted.', '%d documents permanently deleted.', $deleted, 'wc-vp-sign-documents' ), $deleted ) );
    }

    if ( isset( $_GET['updated'] ) ) {
      WC_Admin_Settings::add_message( __( 'Document updated successfully.', 'wc-vp-sign-documents' ) );
    }

    if ( isset( $_GET['created'] ) ) {
      WC_Admin_Settings::add_message( __( 'Document created successfully.', 'wc-vp-sign-documents' ) );
    }

    if ( isset( $_GET['error'] ) ) {
      foreach ( explode( '|', sanitize_text_field( wp_unslash( $_GET['error'] ) ) ) as $message ) {
        WC_Admin_Settings::add_error( trim( $message ) );
      }
    }
  }

  //Screen options
  public function screen_option() {
    global $documents_table_list;

    if ( ! isset( $_GET['edit-webhook'] ) && $this->is_document_settings_page() ) {
      $documents_table_list = new WC_VP_SignDocuments_Settings_Page_Table();

      // Add screen option.
      add_screen_option(
        'per_page',
        array(
          'default' => 10,
          'option'  => 'woocommerce_webhooks_per_page',
        )
      );
    }
  }

  //Table list
  private static function table_list_output() {
    global $documents_table_list;

    //Title and add new button
    echo '<h2 class="wc-vp-sign-documents-title">' . esc_html__( 'Sign Documents', 'wc-vp-sign-documents' ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=wc_vp_sign_documents&edit-document=0' ) ) . '" class="add-new-h2">' . esc_html__( 'Add document', 'wc-vp-sign-documents' ) . '</a></h2>';

    // Get the webhooks count.
    $documents = WC_VP_SignDocuments()->get_documents();
    $count = count( $documents );

    if ( 0 < $count ) {

      //Show PRO section
      include 'views/html-pro-section.php';

      $documents_table_list->process_bulk_action();
      $documents_table_list->prepare_items();

      echo '<input type="hidden" name="page" value="wc-settings" />';
      echo '<input type="hidden" name="tab" value="wc_vp_sign_documents" />';

      $documents_table_list->views();
      $documents_table_list->search_box( __( 'Search documents', 'wc-vp-sign-documents' ), 'document' );
      $documents_table_list->display();
    } else {
      echo '<div class="woocommerce-BlankState woocommerce-BlankState--wc-vp-sign-documents">';
      ?>
      <h2 class="woocommerce-BlankState-message"><?php esc_html_e( 'Create Documents that your customers need to sign after they have made an order. For example to sign a proof of delivery document, or just accepting some terms of service in person.', 'wc-vp-sign-documents' ); ?></h2>
      <a class="woocommerce-BlankState-cta button-primary button" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=wc_vp_sign_documents&edit-document=0' ) ); ?>"><?php esc_html_e( 'Create a new document', 'wc-vp-sign-documents' ); ?></a>
      <style type="text/css">#posts-filter .wp-list-table, #posts-filter .tablenav.top, .tablenav.bottom .actions { display: none; }</style>
      <?php
    }
  }
}

new WC_VP_SignDocuments_Settings_Page();
