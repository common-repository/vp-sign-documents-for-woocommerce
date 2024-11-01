<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<input type="hidden" name="document_id" value="<?php echo esc_attr( $document['id'] ); ?>" />
<div id="wc-vp-sign-documents-options" class="settings-panel">
	<h2><?php esc_html_e( 'Document data', 'woocommerce' ); ?></h2>
	<table class="form-table">
		<tbody>
			<?php WC_Admin_Settings::output_fields($settings); ?>
			<tr valign="top">
				<td colspan="2" scope="row" style="padding-left: 0;">
					<p class="submit">
						<button type="submit" class="button button-primary button-large" name="save" id="publish" accesskey="p"><?php esc_html_e( 'Save document', 'wc-vp-sign-documents' ); ?></button>
						<?php if ( $document['id'] ) : ?>
							<?php
							$preview_url = wp_nonce_url(
								add_query_arg(
									array(
										'document' => $document['id'],
									), admin_url( '?preview_wc_vp_sign_documents=true' )
								), 'preview-document'
							);
							?>
							<a style="text-decoration: none; margin-left: 10px;" href="<?php echo esc_url( $preview_url ); ?>" target="_blank"><?php esc_html_e( 'Preview document', 'wc-vp-sign-documents' ); ?></a>
						<?php endif; ?>
						<?php if ( $document['id'] ) : ?>
							<?php
							$delete_url = wp_nonce_url(
								add_query_arg(
									array(
										'delete' => $document['id'],
									), admin_url( 'admin.php?page=wc-settings&tab=wc_vp_sign_documents' )
								), 'delete-document'
							);
							?>
							<a style="color: #a00; text-decoration: none; margin-left: 10px;" href="<?php echo esc_url( $delete_url ); ?>"><?php esc_html_e( 'Delete permanently', 'wc-vp-sign-documents' ); ?></a>
						<?php endif; ?>
					</p>
				</td>
			</tr>
		</tbody>
	</table>
</div>
