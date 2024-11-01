<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html lang="en">
	<head>
	  <meta charset="utf-8">
	  <title><?php echo esc_html($document['name']); ?></title>
	  <link rel="stylesheet" href="<?php echo esc_attr($css); ?>">
	  <style>@page { size: A4 }</style>
	</head>
	<body class="A4">
	  <section class="sheet padding-10mm" id="page">
	    <article>
				<?php	echo $document['content']; ?>
			</article>
	  </section>
		<?php if(!$preview): ?>
			<div class="signature-pad-submit">
				<button type="button" class="button signature-pad-submit-clear"><?php esc_html_e('Clear', 'wc_vp_sign_document'); ?></button>
				<button type="button" class="button signature-pad-submit-save" data-url="<?php echo esc_url(admin_url( 'admin-ajax.php' )); ?>" data-nonce="<?php echo wp_create_nonce('wc_vp_sign_documents_save_'.$order->get_id()); ?>" data-order="<?php echo esc_attr($order->get_id()); ?>" data-document="<?php echo esc_attr($document['id']); ?>"><?php esc_html_e('Save', 'wc-vp-sign-documents'); ?></button>
			</div>
			<div class="loading">
				<div class="spinner">
				  <div class="bounce1"></div>
				  <div class="bounce2"></div>
				  <div class="bounce3"></div>
				</div>
			</div>
			<div class="loaded">
				<div class="loaded-content">
					<div class="icon"></div>
					<h2><?php esc_html_e('Document signed', 'wc_vp_sign_document'); ?></h2>
					<p><?php esc_html_e('The document was signed and saved successfully.', 'wc_vp_sign_document'); ?></p>
					<p><a href="#" target="_blank" class="loaded-pfd-link"><?php esc_html_e('Download PDF', 'wc_vp_sign_document'); ?></a></p>
				</div>
			</div>
		<?php endif; ?>
		<?php wp_print_scripts( array( 'jquery' ) ); ?>
		<?php foreach ($javascript as $javascript_src): ?>
			<script src="<?php echo esc_url($javascript_src); ?>"></script>
		<?php endforeach; ?>
	</body>
</html>
