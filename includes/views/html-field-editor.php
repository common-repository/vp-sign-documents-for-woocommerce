<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<tr valign="top">
  <th scope="row" class="titledesc">
    <label for="<?php echo esc_attr( $data['id'] ); ?>"><?php echo esc_html( $data['title'] ); ?></label>
  </th>
  <td class="forminp forminp-<?php echo sanitize_title( $data['type'] ) ?>">
    <?php wp_editor( $data['value'], $data['id'], $settings ); ?>
    <div>
			<p><small><em>You can use basic formatting(headers, text styles, text alignment, images), but any special HTML code or extra formatting will be stripped from the final signed PDF file.</em></small></p>
      <p><?php esc_html_e('You can use the following variables in the document:', 'wc-vp-sign-documents'); ?></p>
      <p><strong>{customer_first_name}</strong> - <?php esc_html_e('The first name of the customer who purchased from your store.', 'wc-vp-sign-documents'); ?></p>
      <p><strong>{customer_last_name}</strong> - <?php esc_html_e('The last name of the customer who purchased from your store.', 'wc-vp-sign-documents'); ?></p>
      <p><strong>{customer_name}</strong> - <?php esc_html_e('The full name of the customer who purchased from your store.', 'wc-vp-sign-documents'); ?></p>
      <p><strong>{order_number}</strong> - <?php esc_html_e('The order number for the purchase.', 'wc-vp-sign-documents'); ?></p>
      <p><strong>{order_items}</strong> - <?php esc_html_e('A name and price list of the products purchased.', 'wc-vp-sign-documents'); ?></p>
      <p><strong>{order_date}</strong> - <?php esc_html_e('The date that the order was made.', 'wc-vp-sign-documents'); ?></p>
      <p><strong>{current_date}</strong> - <?php esc_html_e('The current date, when the document is displayed & signed.', 'wc-vp-sign-documents'); ?></p>
      <p><strong>{signature}</strong> - <?php esc_html_e('Displays a signature box. You can add multiple signatures.', 'wc-vp-sign-documents'); ?></p>
    </div>
  </td>
</tr>
