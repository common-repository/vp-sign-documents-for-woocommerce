<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<?php foreach ($documents as $document): ?>
		<div class="vp-wc-sign-documents-document-order-id" data-order="<?php echo esc_attr($_GET['post']); ?>"></div>
    <?php if(isset($document['signed'])): ?>
			<div class="vp-wc-sign-documents-document vp-wc-sign-documents-document-signed">
				<div class="vp-wc-sign-documents-document-row">
					<h3><?php echo esc_html($document['name']); ?></h3>
					<a target="_blank" href="<?php echo esc_url($this->generate_download_link($order, $document['id'])); ?>"><?php esc_html_e('Download', 'wc-vp-sign-documents'); ?></a>
				</div>
				<div class="vp-wc-sign-documents-document-row">
					<strong><?php esc_html_e('Signed on', 'wc-vp-sign-documents'); ?></strong>
					<span><?php echo date_i18n( get_option( 'date_format' ), $document['audit']['date'] ); ?> <?php echo date_i18n( get_option( 'time_format' ), $document['audit']['date'] ); ?></span>
				</div>
				<div class="vp-wc-sign-documents-document-row vp-wc-sign-documents-document-row-user-agent">
					<strong><?php esc_html_e('IP Address', 'wc-vp-sign-documents'); ?></strong>
					<span><?php echo esc_html( $document['audit']['ip'] ); ?></span>
				</div>
				<div class="vp-wc-sign-documents-document-row vp-wc-sign-documents-document-row-user-agent">
					<strong><?php esc_html_e('User Agent', 'wc-vp-sign-documents'); ?></strong>
					<span title="<?php echo esc_html( $document['audit']['user_agent'] ); ?>"><?php echo esc_html( $document['audit']['user_agent'] ); ?></span>
				</div>
				<div class="vp-wc-sign-documents-document-row">
					<strong><?php esc_html_e('User', 'wc-vp-sign-documents'); ?></strong>
					<?php if(intval($document['audit']['user_id']) > 0): ?>
						<span><?php esc_html_e('Signed as a guest', 'wc-vp-sign-documents'); ?></span>
					<?php else: ?>
						<?php $user_info = get_userdata($document['audit']['user_id']); ?>
						<a href="<?php echo esc_url(get_edit_user_link($document['audit']['user_id'])); ?>"><?php echo esc_html($user_info->user_nicename); ?></a>
					<?php endif; ?>
				</div>
				<div class="vp-wc-sign-documents-document-row">
					<strong><?php esc_html_e('HTML version', 'wc-vp-sign-documents'); ?></strong>
					<a target="_blank" href="<?php echo esc_url($this->generate_download_link($order, $document['id'], 'html')); ?>"><?php esc_html_e('Open', 'wc-vp-sign-documents'); ?></a>
				</div>
      </div>
    <?php else: ?>
      <div class="vp-wc-sign-documents-document vp-wc-sign-documents-document-unsigned" data-document="<?php echo esc_attr($document['id']); ?>">
				<div class="vp-wc-sign-documents-document-row vp-wc-sign-documents-document-row-signed" style="display:none;">
					<h3><?php echo esc_html($document['name']); ?></h3>
					<a target="_blank" href="#"><?php esc_html_e('Download', 'wc-vp-sign-documents'); ?></a>
				</div>
				<div class="vp-wc-sign-documents-document-row-unsigned">
	        <div class="vp-wc-sign-documents-document-qr" data-link="<?php echo esc_url($this->get_document_link($order, $document)); ?>"></div>
	        <h3><?php echo esc_html($document['name']); ?></h3>
	        <p><small><?php esc_html_e('Scan the QR code with a tablet or a phone to let your customer sign it', 'wc-vp-sign-documents'); ?></small></p>
	        <p><a href="<?php echo esc_url($this->get_document_link($order, $document)); ?>"><?php esc_html_e('Open Document', 'wc-vp-sign-documents'); ?></a></p>
				</div>
      </div>
    <?php endif; ?>

<?php endforeach; ?>
