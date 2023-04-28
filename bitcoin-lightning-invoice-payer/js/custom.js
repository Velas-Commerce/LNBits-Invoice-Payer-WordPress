jQuery(document).ready(function () {
  jQuery(document).on("click", "button#submit-invoice-string", function () {
    var get_invoice_string = jQuery("#msf-text-paste-your-invoice-here").val();
    if (get_invoice_string != "") {
      //alert("testing------------"+ajax_var.ajax_url);
      jQuery(".message-data")
        .empty()
        .html('<img src="' + ajax_var.loader_img + '">');
      jQuery.ajax({
        type: "POST",
        dataType: "json",
        url: ajax_var.ajax_url,
        data: {
          action: "invoice_payment",
          string: get_invoice_string,
          nonce: ajax_var.nonce, // Include the nonce value within the data object
        },
        success: function (response) {
          //console.log(response);
          if (response.status == "true") {
            jQuery(".message-data").html(
              '<span class="success">You have received the payment. Please check your wallet</span>'
            );
          } else {
            jQuery(".message-data").html(
              '<span class="error">' + response.error + "</span>"
            );
          }
        },
      });
    }
  });
});
