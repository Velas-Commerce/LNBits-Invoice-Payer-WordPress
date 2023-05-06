jQuery(document).ready(function () {
  jQuery(document).on("click", "button#submit-invoice-string", function (e) {
    e.preventDefault(); // Prevent the default action of the button click

    var get_invoice_string = jQuery("#msf-text-paste-your-invoice-here").val();
    if (get_invoice_string != "") {
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
          nonce: ajax_var.nonce,
        },
        success: function (response) {
          if (response.status == "true") {
            jQuery(".message-data").html(
              '<span class="success">You have received the payment. Please check your wallet</span>'
            );
          } else {
            var errorHtml = jQuery.parseHTML(response.error);
            var errorImage = jQuery(errorHtml).filter("img");

            if (errorImage.length > 0) {
              var imageUrl = errorImage.attr("src");

              // Prepend the plugin directory URL to the image URL only if it doesn't already contain the full URL
              var fullImageUrl = imageUrl.startsWith("http")
                ? imageUrl
                : ajax_var.loader_img.replace("loader.gif", imageUrl);

              // Wrap the image with an anchor tag for Lightbox
              var lightboxImageHtml =
                '<a id="errorImageLink" href="' +
                fullImageUrl +
                '" data-lightbox="error-image" data-title="Error Image"><img src="' +
                fullImageUrl +
                '"></a>';

              // Display the image below the error message
              jQuery(".message-data").html(
                '<span class="error">No repeat payments, please. Now go zap us some sats!</span><br>' +
                  lightboxImageHtml
              );

              // Trigger the click event on the anchor tag to automatically open the Lightbox
              jQuery("#errorImageLink").click();
            } else {
              jQuery(".message-data").html(
                '<span class="error">' + response.error + "</span>"
              );
            }
          }
        },
      });
    }
  });
});
