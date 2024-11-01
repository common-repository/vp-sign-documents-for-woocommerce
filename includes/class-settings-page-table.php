<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WC_VP_SignDocuments_Settings_Page_Table extends WP_List_Table {

	//Init table
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'document',
				'plural'   => 'documents',
				'ajax'     => false,
			)
		);
	}

	//Not found
	public function no_items() {
		esc_html_e( 'No documents found.', 'wc-vp-sign-documents' );
	}

	//Setup columns
	public function get_columns() {
		return array(
			'cb'           => '<input type="checkbox" />',
			'title'        => __( 'Name', 'wc-vp-sign-documents' ),
			'content' => __( 'Content', 'wc-vp-sign-documents' ),
			'status'       => __( 'Status', 'wc-vp-sign-documents' )
		);
	}

	//Checkboxes
	public function column_cb( $document ) {
		return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['singular'], $document['id'] );
	}

	//Title
	public function column_title( $document ) {
		$edit_link = admin_url( 'admin.php?page=wc-settings&amp;tab=wc_vp_sign_documents&amp;edit-document=' . $document['id'] );
		$output    = '';

		// Title.
		$output .= '<strong><a href="' . esc_url( $edit_link ) . '" class="row-title">' . esc_html( $document['name'] ) . '</a></strong>';

		// Get actions.
		$actions = array(
			'id'     => sprintf( __( 'ID: %d', 'wc-vp-sign-documents' ), $document['id'] ),
			'edit'   => '<a href="' . esc_url( $edit_link ) . '">' . esc_html__( 'Edit', 'wc-vp-sign-documents' ) . '</a>',
			'preview_document' => '<a target="_blank" aria-label="' . esc_attr( sprintf( __( 'Preview document', 'wc-vp-sign-documents' ), $document['name'] ) ) . '" href="' . esc_url(
				wp_nonce_url(
					add_query_arg(
						array(
							'document' => $document['id'],
						), admin_url( '?preview_wc_vp_sign_documents=true' )
					),
					'preview-document'
				)
			) . '">' . esc_html__( 'Preview', 'wc-vp-sign-documents' ) . '</a>',
			'delete' => '<a class="submitdelete" aria-label="' . esc_attr( sprintf( __( 'Delete "%s" permanently', 'wc-vp-sign-documents' ), $document['name'] ) ) . '" href="' . esc_url(
				wp_nonce_url(
					add_query_arg(
						array(
							'delete' => $document['id'],
						),
						admin_url( 'admin.php?page=wc-settings&tab=wc_vp_sign_documents' )
					),
					'delete-document'
				)
			) . '">' . esc_html__( 'Delete permanently', 'wc-vp-sign-documents' ) . '</a>',
		);

		$row_actions = array();

		foreach ( $actions as $action => $link ) {
			$row_actions[] = '<span class="' . esc_attr( $action ) . '">' . $link . '</span>';
		}

		$output .= '<div class="row-actions">' . implode( ' | ', $row_actions ) . '</div>';

		return $output;
	}

	//Status
	public function column_status( $document ) {
		$labels = array(
			'publish' => __('Active', 'wc-vp-sign-documents'),
			'draft' => __('Disabled', 'wc-vp-sign-documents')
		);
		return esc_html($labels[$document['status']]);
	}

	//Content
	public function column_content( $document ) {
		return wp_strip_all_tags(get_the_excerpt($document['id']));;
	}

	//Bulk actions
	protected function get_bulk_actions() {
		return array(
			'delete' => __( 'Delete permanently', 'wc-vp-sign-documents' ),
		);
	}

	//Process bulk actions
	public function process_bulk_action() {
		$action   = $this->current_action();
		$documents = isset( $_REQUEST['document'] ) ? array_map( 'absint', (array) $_REQUEST['document'] ) : array();

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to edit Webhooks', 'woocommerce' ) );
		}

		if ( 'delete' === $action ) {
			WC_VP_SignDocuments_Settings_Page::bulk_delete( $documents );
		}
	}

	//Search field
	public function search_box( $text, $input_id ) {
		if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) {
			return;
		}

		$input_id     = $input_id . '-search-input';
		$search_query = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';

		echo '<p class="search-box">';
		echo '<label class="screen-reader-text" for="' . esc_attr( $input_id ) . '">' . esc_html( $text ) . ':</label>';
		echo '<input type="search" id="' . esc_attr( $input_id ) . '" name="s" value="' . esc_attr( $search_query ) . '" />';
		submit_button(
			$text,
			'',
			'',
			false,
			array(
				'id' => 'search-submit',
			)
		);
		echo '</p>';
	}

	//Get data for the table
	public function prepare_items() {

		// Query args.
		$args = array();

		// Handle the status query.
		if ( ! empty( $_REQUEST['status'] ) ) {
			$args['status'] = sanitize_key( wp_unslash( $_REQUEST['status'] ) );
		}

		// If its search
		if ( ! empty( $_REQUEST['s'] ) ) {
			$args['s'] = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );
		}

		// Get the webhooks.
		$documents = WC_VP_SignDocuments()->get_documents($args);
		$this->items = $documents;

		//Set the pagination.
		$this->set_pagination_args(
			array(
				'total_items' => count($documents)
			)
		);
	}
}
