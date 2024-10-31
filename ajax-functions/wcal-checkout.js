(function($) {
    $(document).ready(function() {
        console.log('Checkout form script initiated.');

        var checkoutDataSent = false;

        $.ajax({
            url: ajax_object.ajaxurl,
            type: 'POST',
            data: {
                action: 'fetch_gdpr_message',
            },
            success: function(response) {
                if (response.success && response.data) {
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

                    $(".wc-block-components-text-input").last().after(customBlockHtml);

                    $('body').on('change', '#woosponder_gdpr_consent_checkbox', function() {
                        if ($(this).is(':checked') && !checkoutDataSent) {
                            console.log('GDPR consent given.');

                            $('#woosponder_gdpr_container').hide();
                            console.log('Checkbox hidden.');

                            $('#gdprCartOptOutMessage').show().delay(5000).fadeOut(400, function() {
                                $('.custom-checkout-block').hide();
                                console.log('Entire block hidden after showing thank you message.');
                            });

                            // Gather the checkout data, mapping to the expected backend fields
                            var billing_email = $('#email').val();
                            var billing_phone = $('#billing-phone').val() || $('#shipping-phone').val();
                            var billing_first_name = $('#billing-first_name').val() || $('#shipping-first_name').val();
                            var billing_last_name = $('#billing-last_name').val() || $('#shipping-last_name').val();

                            // Capture the interested tags from your form or another source
                            var interestedTags = $('#interested_tags').val() || []; // Adjust selector as necessary

                            // Log each field individually to see if they are found
                            console.log('email field:', billing_email);
                            console.log('billing_phone field:', $('#billing-phone').val());
                            console.log('shipping_phone field:', $('#shipping-phone').val());
                            console.log('billing_first_name field:', $('#billing-first_name').val());
                            console.log('shipping_first_name field:', $('#shipping-first_name').val());
                            console.log('billing_last_name field:', $('#billing_last_name').val());
                            console.log('shipping_last_name field:', $('#shipping_last_name').val());
                            console.log('Interested Tags:', interestedTags);

                            console.log('Captured data:', {
                                email: billing_email,
                                phone: billing_phone,
                                first_name: billing_first_name,
                                last_name: billing_last_name,
                                interested_tags: interestedTags
                            });

                            // Check if any field is still missing
                            if (!billing_email) {
                                console.error('billing_email is missing');
                            }
                            if (!billing_phone) {
                                console.error('billing_phone is missing');
                            }
                            if (!billing_first_name) {
                                console.error('billing_first_name is missing');
                            }
                            if (!billing_last_name) {
                                console.error('billing_last_name is missing');
                            }

                            // Send checkout data if not already sent
                            if (billing_email && (billing_phone || (billing_first_name && billing_last_name))) {
                                console.log('Sending AJAX request with data:', {
                                    action: 'woosponder_process_checkout_fields',
                                    email: billing_email,
                                    phone: billing_phone,
                                    first_name: billing_first_name,
                                    last_name: billing_last_name,
                                    interested_tags: interestedTags,
                                    security: wcal_checkout_params.wcal_nonce
                                });
                                $.ajax({
                                    type: 'POST',
                                    url: ajax_object.ajaxurl,
                                    data: {
                                        action: 'woosponder_process_checkout_fields',
                                        email: billing_email,
                                        phone: billing_phone,
                                        first_name: billing_first_name,
                                        last_name: billing_last_name,
                                        interested_tags: interestedTags,
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
                                console.error('Not all required fields are provided. Aborting AJAX request.');
                            }
                        } else {
                            console.log('GDPR consent not given or already processed.');
                        }
                    });

                    $(document).ajaxComplete(function() {
                        if (!$("#woosponder_gdpr_consent_checkbox").length) {
                            $(".wc-block-components-text-input").last().after(customBlockHtml);
                            console.log('Custom GDPR block with dynamic opt-out message injected.');
                        }
                    });
                } else {
                    console.log('Failed to fetch GDPR content or incomplete data received.');
                }
            }
        });
    });
})(jQuery);
