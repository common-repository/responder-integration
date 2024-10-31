(function($) {
    $(document).ready(function() {
        console.log('Checkout form script initiated.');

        // Flag to prevent sending data multiple times
        var checkoutDataSent = false; 

        // Check if the user is logged in and adjust the placement of the GDPR message accordingly
        var userLoggedIn = checkoutParams.isUserLoggedIn == '1'; // Ensure boolean comparison

        // Perform an AJAX request to get the GDPR messages.
        $.ajax({
            url: checkoutParams.ajaxurl, // Using checkoutParams for ajaxurl
            type: 'POST',
            data: {
                action: 'fetch_gdpr_message',
            },
            success: function(response) {
                if (response.success && response.data) {
                    // Check if the GDPR block has already been injected
                    if ($('.custom-checkout-block').length === 0) { // Only proceed if the block does not exist

                        // Construct the custom block HTML, including placeholders for the messages
                        var customBlockHtml = '<div class="custom-checkout-block">' +
                            '<div id="woosponder_gdpr_message">' + response.data.message + '</div>' +
                            '<div id="woosponder_gdpr_container">' +
                            '<label for="woosponder_gdpr_consent_checkbox">' +
                            '<input type="checkbox" id="woosponder_gdpr_consent_checkbox" name="woosponder_gdpr_consent" value="1" required> ' +
                            response.data.optOutMessage +
                            '</label>' +
                            '</div>' +
                            '<div id="gdprCartOptOutMessage" style="display:none;">' + response.data.thankYouMessage + '</div>' +
                            '</div>';

                        // Decide where to append the custom GDPR block based on user login status
                        if (userLoggedIn) {
                            // For logged-in users, attempt to find the email field with a more flexible selector
                            var emailField = $("[id*='email'], [name*='email'], .wc-block-components-text-input input[type='email']").first();
                            if (emailField.length > 0) {
                                emailField.closest('.wc-block-components-text-input').after(customBlockHtml);
                            }
                        } else {
                            // For guests, append it to the end of the checkout form fields
                            $(".woocommerce-checkout-review-order").before(customBlockHtml);
                        }
                    }

                    $('body').on('change', '#woosponder_gdpr_consent_checkbox', function() {
                        if ($(this).is(':checked') && !checkoutDataSent) {
                            console.log('GDPR consent given.');

                            $('#woosponder_gdpr_container').hide();
                            console.log('Checkbox hidden.');

                            $('#gdprCartOptOutMessage').show().delay(5000).fadeOut(400, function() {
                                $('.custom-checkout-block').hide();
                                console.log('Entire block hidden after showing thank you message.');
                            });

                            var billing_email = $('#billing_email').val(); // Corrected to '#billing_email' assuming standard WooCommerce ID
                            var billing_phone = $('#billing_phone').val(); // Assuming standard WooCommerce ID
                            var billing_first_name = $('#billing_first_name').val(); // Assuming standard WooCommerce ID
                            var billing_last_name = $('#billing_last_name').val(); // Assuming standard WooCommerce ID

                            $.ajax({
                                type: 'POST',
                                url: checkoutParams.ajaxurl,
                                data: {
                                    action: 'woosponder_process_checkout_fields',
                                    email: billing_email,
                                    phone: billing_phone,
                                    first_name: billing_first_name,
                                    last_name: billing_last_name,
                                    security: wcal_checkout_params.wcal_nonce
                                },
                                success: function(response) {
                                    console.log('AJAX request successful. Response:', response);
                                    checkoutDataSent = true;

                                    if (response && response.registered_user) {
                                        console.log('Consent stored for registered user.');
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error('AJAX request failed. Status:', status, 'Error:', error);
                                }
                            });
                        } else {
                            console.log('GDPR consent not given or already processed.');
                        }
                    });
                } else {
                    console.log('Failed to fetch GDPR content or incomplete data received.');
                }
            }
        });
    });
})(jQuery);
