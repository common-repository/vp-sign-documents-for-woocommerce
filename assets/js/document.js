jQuery(document).ready(function($) {

  var signatureWrappers = $('.signature-pad');
  var clearButton = $('.signature-pad-submit-clear');
  var submitButton = $('.signature-pad-submit-save');
  var signaturePads = [];

  signatureWrappers.each(function() {
    var canvas = $(this).find("canvas")[0];
    var signaturePad = new SignaturePad(canvas, {
      penColor: 'rgb(5, 121, 134)'
    });

    if($(this).data('signature')) {
      var signature = $(this).data('signature');
      signaturePad.fromDataURL(signature);
      signaturePad.off();
      $('body').addClass('print');
    }

    signaturePads.push(signaturePad);
  });

  clearButton.click(function(){
    signaturePads.forEach(function(signaturePad){
      signaturePad.clear();
    });
  });

  submitButton.click(function(){
    var button = $(this);

    //Collect signatures as images
    var signatures = [];
    var hasEmpty = false;
    signaturePads.forEach(function(signaturePad){
      if (signaturePad.isEmpty()) {
        hasEmpty = true;
      } else {
        signatures.push(signaturePad.toDataURL());
      }
    });

    //If signature not provided
    if(hasEmpty) {
      alert("Please provide a signature first.");
      return false;
    }

    //Setup page for printing
    $('body').addClass('print');
    $('.loading').show();
    var element = document.getElementById('page');

    html2pdf().from(element).toPdf().output('datauristring').then(function (pdfAsString) {

      var url = button.data('url');
      var nonce = button.data('nonce');
      var order = button.data('order');
      var document_id = button.data('document');
      var data = {
        'action': 'wc_vp_sign_documents_save',
        'nonce': nonce,
        'order_id': order,
        'document_id': document_id,
        'signatures': signatures,
        'pdf': pdfAsString
      }

      $.post(url, data, function(response) {
        if(response.data.error) {
          $('body').removeClass('print');
          $('.loading').hide();
          alert(response.data.error_message);
        } else {
          $('.loaded').show();
          $('.loaded-pfd-link').attr('href', response.data.pdf);
        }
      });

    });

    return false;
  });

});
