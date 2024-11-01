jQuery(document).ready(function($) {

  //Settings page
  var vp_wc_sign_document_settings = {
    init: function() {
      $('#wc_vp_sign_documents_pro_email').keypress(this.submit_pro_on_enter);
      $('#wc-vp-sign-documents-field-pro-submit').on('click', this.submit_activate_form);
      $('#wc-vp-sign-documents-field-pro-deactivate').on('click', this.submit_deactivate_form);

      //Edit page
      $('#wc-vp-sign-documents-options input#document_emailitin[disabled]').after('<i class="vp-wc-sign-documents-pro-label">PRO</i>');
    },
    submit_pro_on_enter: function(e) {
      if (e.which == 13) {
        $(this).parent().find('button').click();
        return false;
      }
    },
    submit_activate_form: function() {
      var key = $('#wc_vp_sign_documents_pro_key').val();
      var email = $('#wc_vp_sign_documents_pro_email').val();
      var button = $(this);
      var form = button.parents('.wc-vp-sign-documents-section-pro');

      var data = {
        action: 'wc_vp_sign_documents_pro_check',
        key: key,
        email: email
      };

      form.block({
        message: null,
        overlayCSS: {
          background: '#ffffff url(' + wc_vp_sign_documents_params.loading + ') no-repeat center',
          backgroundSize: '16px 16px',
          opacity: 0.6
        }
      });

      form.find('.notice').hide();

      $.post(ajaxurl, data, function(response) {
        //Remove old messages
        if(response.success) {
          window.location.reload();
          return;
        } else {
          form.find('.notice p').html(response.data.message);
          form.find('.notice').show();
        }
        form.unblock();
      });

      return false;
    },
    submit_deactivate_form: function() {
      var button = $(this);
      var form = button.parents('.wc-vp-sign-documents-section-pro');

      var data = {
        action: 'wc_vp_sign_documents_pro_deactivate'
      };

      form.block({
        message: null,
        overlayCSS: {
          background: '#ffffff url(' + wc_vp_sign_documents_params.loading + ') no-repeat center',
          backgroundSize: '16px 16px',
          opacity: 0.6
        }
      });

      form.find('.notice').hide();

      $.post(ajaxurl, data, function(response) {
        //Remove old messages
        if(response.success) {
          window.location.reload();
          return;
        } else {
          form.find('.notice p').html(response.data.message);
          form.find('.notice').show();
        }
        form.unblock();
      });
      return false;
    }
  }

  //Metabox
  var wc_vp_sign_documents_metabox = {
    init: function() {
      this.createQrCodes();

      var order_id = $('.vp-wc-sign-documents-document-order-id').data('order');

      $(document).on( 'heartbeat-send', function ( event, data ) {
        data.wc_vp_sign_documents = order_id;
      });

      $(document).on( 'heartbeat-tick', function ( event, data ) {
        if (!data._wc_vp_signed_documents) {
          return;
        }

        data._wc_vp_signed_documents.forEach(function(document){
          var $documentRow = $('.vp-wc-sign-documents-document-unsigned[data-document="'+document['id']+'"]');
          $documentRow.find('.vp-wc-sign-documents-document-row-unsigned').slideUp();
          $documentRow.find('.vp-wc-sign-documents-document-row-signed').slideDown();
          $documentRow.find('.vp-wc-sign-documents-document-row-signed a').attr('href', document['link']);
        });

        console.log(data._wc_vp_signed_documents);
      });
    },
    createQrCodes: function() {

      $('.vp-wc-sign-documents-document-qr').each(function(i, obj) {
        var link = $(this).data('link');
        $(this).qrcode({
          text: link,
          width: 128,
          height: 128
        });
      });
    }
  }

  //Init metabox functions
  if($('#wc_vp_sign_documents_metabox').length) {
    wc_vp_sign_documents_metabox.init();
  }

  //Settings page
  if($('.wc-vp-sign-documents-section-pro').length || $('#wc-vp-sign-documents-options').length) {
    vp_wc_sign_document_settings.init();
  }

  // Hide notice
	$( '.wc-vp-sign-documents-notice .wc-vp-sign-documents-hide-notice').on('click', function(e) {
		e.preventDefault();
		var el = $(this).closest('.wc-vp-sign-documents-notice');
		$(el).find('.wc-vp-sign-documents-wait').remove();
		$(el).append('<div class="wc-vp-sign-documents-wait"></div>');
		if ( $('.wc-vp-sign-documents-notice.updating').length > 0 ) {
			var button = $(this);
			setTimeout(function(){
				button.triggerHandler( 'click' );
			}, 100);
			return false;
		}
		$(el).addClass('updating');
		$.post( ajaxurl, {
				action: 	'wc_vp_sign_documents_hide_notice',
				security: 	$(this).data('nonce'),
				notice: 	$(this).data('notice'),
				remind: 	$(this).hasClass( 'remind-later' ) ? 'yes' : 'no'
		}, function(){
			$(el).removeClass('updating');
			$(el).fadeOut(100);
		});
	});

});
