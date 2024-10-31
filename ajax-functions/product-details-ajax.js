(function($) {
    // Global variables
    var selectedListId = null;
    var productDetails = null;
    var customFields = null;

    // Event handler for list selection change
    $('#list_selection').change(function() {
        selectedListId = $(this).val();
        var selectedSystem = $('#system_selection').val();
        console.log('Selected list ID:', selectedListId);
        console.log('Selected system type:', selectedSystem);

        updateFieldAvailability();

        if (!productDetails) {
            console.error('Product details not available');
            return;
        }

        $.ajax({
            url: ajax_object.ajaxurl,
            type: 'POST',
            data: {
                action: 'retrieve_custom_fields',
                list_id: selectedListId,
                system_selection: selectedSystem,
                security: ajax_object.nonce
            },
            beforeSend: function() {
                console.log('Preparing to make AJAX request for custom fields');
            },
            success: function(response) {
                console.log('Custom fields response:', response);
                if (response.success && response.data) {
                    customFields = response.data;
                    displayMappingInterface(customFields, productDetails);
                } else {
                    customFields = [];
                    displayMappingInterface(customFields, productDetails);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
            }
        });
    });

    $('#product_selection').change(function() {
        var productId = $(this).val();
        console.log('Product Selection Change - Selected Product ID:', productId);

        $.ajax({
            url: ajax_object.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_product_details',
                product_id: productId,
                security: ajax_object.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    productDetails = response.data;
                    console.log('AJAX Success - Product Details:', productDetails);
                    $(document).trigger("productDetailsFetched", [productDetails]);
                } else {
                    console.error('Error fetching product details:', response);
                }
            },
            error: function(_jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
            }
        });
    });

    // Function to display the mapping interface
    function displayMappingInterface(customFields, productDetails) {
        console.log('displayMappingInterface called with:', {
            customFields,
            productDetails
        });

        if (!productDetails || typeof productDetails !== 'object' || Array.isArray(productDetails)) {
            console.error('Invalid productDetails:', productDetails);
            return;
        } else {
            console.log('productDetails are valid:', productDetails);
        }

        var mappingInterface = $('#mapping_interface');
        mappingInterface.empty();
        console.log('Mapping Interface emptied.');

        var title = $('<h3 class="mapping-title">התאמת פרטי המוצר לשדות לרשימת הרוכשים (אופציונלי)</h3>');
        mappingInterface.append(title);

        var hasCustomFields = Array.isArray(customFields) && customFields.length > 0;

        if (!hasCustomFields) {
            mappingInterface.append('<p class="no-fields-message">הרשימה שנבחרה אינה מכילה שדות מותאמים להתאמה.</p>');
            appendSaveConnectionButton(mappingInterface, false);
            return;
        }

        const translations = {
            '_name': 'שם המוצר',
            '_regular_price': 'מחיר רגיל',
            '_sku': 'מק״ט',
            '_stock': 'מלאי',
            '_sale_price': 'מחיר מבצע'
        };

        $.each(translations, function(key, label) {
            var isEnabled = productDetails[key] && (key !== '_sale_price' || parseFloat(productDetails[key]) > 0);
            addMappingRow(label, key, customFields, isEnabled);
        });

        addMappingRow('מזהה עיסקה', '_transaction_id', customFields, true);
        addMappingRow('סה״כ לתשלום', '_total', customFields, true);
        addMappingRow('מע"מ', '_total_tax', customFields, true);
        addMappingRow('משלוח', '_shipping_total', customFields, true);
        addMappingRow('מספר הזמנה', '_order_id', customFields, true);
        addMappingRow('קופון', '_used_coupons', customFields, true);

        updateFieldAvailability();

        function addMappingRow(label, fieldId, customFields, isEnabled) {
            console.log('Adding mapping row for:', label);
            var rowDiv = $('<div class="mapping-row">');
            var fieldText = $('<div class="product-field">').text(label);
            var customFieldSelect = $('<select class="custom-field" data-original-key="' + fieldId + '">');

            customFieldSelect.append($('<option>').val('').text('בחירת שדה'));
            if (!isEnabled) {
                customFieldSelect.attr('disabled', 'disabled');
                rowDiv.addClass('disabled');
            }

            $.each(customFields, function(_, field) {
                if (field && field.id && field.name) {
                    customFieldSelect.append($('<option>').val(field.id).text(field.name));
                }
            });

            rowDiv.append(fieldText);
            rowDiv.append(customFieldSelect);
            mappingInterface.append(rowDiv);
        }

        appendSaveConnectionButton(mappingInterface, true);
    }

    // Ensure the addMappingRow function is called in the right place
    function updateFieldAvailability() {
        var selectedFields = {};

        $('.custom-field option').prop('disabled', false);

        $('.custom-field').each(function() {
            var value = $(this).val();
            if (value) {
                if (!selectedFields[value]) {
                    selectedFields[value] = [];
                }
                selectedFields[value].push(this);
            }
        });

        $.each(selectedFields, function(value, selects) {
            if (value) {
                $('.custom-field').not(selects).find('option[value="' + value + '"]').prop('disabled', true);
            }
        });

        $('.custom-field option[value=""]').prop('disabled', false);
    }

    $(document).on('change', '.custom-field', function() {
        updateFieldAvailability();
    });

    function appendSaveConnectionButton(mappingInterface, hasCustomFields) {
        var buttonText = hasCustomFields ? 'שמירת חיבור' : 'דילוג ושמירת חיבור';

        // Check if the button already exists
        if (!$('#SaveTheConnectionButton').length) {
            // If it doesn't exist, create the button
            var saveConnectionButton = $('<button/>', {
                id: 'SaveTheConnectionButton',
                text: buttonText,
                class: 'button button-primary',
                click: function() {
                    var selectedOrderStatus = $('#order_status_dropdown').val();
                    var selectedSystem = $('#system_selection').val();
                    if (selectedOrderStatus === '') {
                        var errorMessage = '*לא בחרתם סטטוס הזמנה';
                        $('#order_status_dropdown').next('.error-message').remove();
                        $('<div>').addClass('error-message').css({
                            "color": "red",
                            "margin-top": "5px"
                        }).text(errorMessage).insertAfter('#order_status_dropdown');
                        return;
                    } else {
                        $('#order_status_dropdown').next('.error-message').remove();
                    }

                    saveMappings(selectedListId, selectedOrderStatus, selectedSystem);
                }
            });

            mappingInterface.append(saveConnectionButton);
        } else {
            // If it exists, only update the text if it's different
            if ($('#SaveTheConnectionButton').text() !== buttonText) {
                $('#SaveTheConnectionButton').text(buttonText);
            }
        }
    }

    function saveMappings(listId, orderStatus, systemType) {
        console.log('saveMappings function called with:', {
            listId,
            orderStatus,
            systemType
        });

        var productId = $('#product_selection').val();
        var interestedListId = $('#list_selection_interested').val();

        var mappings = getMappings() || {};

        var connection = {
            system_type: systemType,
            buyer_list_id: listId,
            interested_list_id: interestedListId,
            field_mappings: mappings,
            order_status: orderStatus
        };

        var ajaxData = {
            action: 'save_mapping_fetch_product_details',
            product_id: productId,
            connection: connection,
            security: ajax_object.nonce
        };

        console.log('Prepared ajaxData:', ajaxData);

        if (systemType === 'new_responder') {
            var buyerTagsData = $('#tag_select').select2('data');
            var interestedTagsData = $('#tag_select_interested').select2('data');

            function processTagsData(tagsData) {
                return {
                    existing: tagsData.filter(tag => !isNaN(tag.id)).map(tag => tag.id),
                    new: tagsData.filter(tag => isNaN(tag.id)).map(tag => tag.text)
                };
            }

            var buyerTags = processTagsData(buyerTagsData);
            var interestedTags = processTagsData(interestedTagsData);

            connection.buyer_tags = buyerTags;
            connection.interested_tags = interestedTags;
        } else {
            connection.buyer_tags = [];
            connection.interested_tags = [];
        }

        console.log('Final connection data before sending:', connection);

        $.post(ajax_object.ajaxurl, ajaxData, function(response) {
            if (response.success) {
                console.log('Mappings and tags saved successfully:', response);
                resetSelectionsAndDisplayMessage();
                $('#mapping_interface').hide();  // Hide the mapping interface on successful save

                // Clear and destroy tag selects for buyers and interested
                clearAndDestroyTagSelects();

                // Reset system selection to trigger hiding of tag selects
                $('#system_selection').val('').trigger('change');
            } else {
                console.error('Failed to save mappings and tags:', response);
                // Display error message to the user
                var errorMessage = $('<div/>', {
                    text: response.data,
                    class: 'error-message'
                }).css('color', 'red');
                $('#SaveTheConnectionButton').after(errorMessage);
            }
        });
    }

    function resetSelectionsAndDisplayMessage() {
        console.log('Reset selections and display message function called');
    
        $('#list_selection').val('');
        $('#product_selection').val('');
        $('#system_selection').val('');
        $('#list_selection_interested').val('');
        $('#tag_select').val('').trigger('change');
        $('#tag_select_interested').val('').trigger('change');
        $('#order_status_dropdown').val('').trigger('change');
    
        $('.custom-field').each(function() {
            $(this).val('');
            $(this).trigger('change');
        });
    
        console.log('All selections and custom fields cleared.');
    
        var successMessage = $('<p/>', {
            text: 'החיבור בוצע בהצלחה!',
            class: 'connection-success-message'
        }).css({
            'color': 'green',
            'font-weight': 'bold'
        });
    
        // Remove any existing success messages
        $('.connection-success-message').remove();
    
        // Append the success message to the success message container
        $('#success_message_container').append(successMessage);
    
        // Set a timeout to hide the success message after 3 seconds
        setTimeout(function() {
            successMessage.fadeOut('slow', function() {
                $(this).remove(); // Remove the element after fading out
            });
        }, 2000);
    }
    
    
    function clearAndDestroyTagSelects() {
        ['#tag_select', '#tag_select_interested'].forEach(function(selector) {
            if ($(selector).data('select2')) {
                console.log('Destroying Select2 instance on ' + selector);
                $(selector).val(null).trigger('change'); // Clear the select before destroying
                $(selector).select2('destroy');
            }
            $(selector).remove(); // Remove the element from DOM
            $('label[for="' + selector.replace('#', '') + '"]').remove(); // Remove the corresponding label
        });
        console.log('Tag select and label for buyers and interested removed');
    }
    
    
    function clearAndDestroyTagSelects() {
        ['#tag_select', '#tag_select_interested'].forEach(function(selector) {
            if ($(selector).data('select2')) {
                console.log('Destroying Select2 instance on ' + selector);
                $(selector).val(null).trigger('change'); // Clear the select before destroying
                $(selector).select2('destroy');
            }
            $(selector).remove(); // Remove the element from DOM
            $('label[for="' + selector.replace('#', '') + '"]').remove(); // Remove the corresponding label
        });
        console.log('Tag select and label for buyers and interested removed');
    }
    

    function clearAndDestroyTagSelects() {
        ['#tag_select', '#tag_select_interested'].forEach(function(selector) {
            if ($(selector).data('select2')) {
                console.log('Destroying Select2 instance on ' + selector);
                $(selector).val(null).trigger('change'); // Clear the select before destroying
                $(selector).select2('destroy');
            }
            $(selector).remove(); // Remove the element from DOM
            $('label[for="' + selector.replace('#', '') + '"]').remove(); // Remove the corresponding label
        });
        console.log('Tag select and label for buyers and interested removed');
    }

    function getMappings() {
        var mappings = {};
        $('.mapping-row').each(function() {
            var originalKey = $(this).find('.custom-field').data('original-key');
            var customFieldId = $(this).find('.custom-field').val();
            if (customFieldId) {
                mappings[originalKey] = customFieldId;
            }
        });
        console.log('Mappings obtained:', mappings);
        return mappings;
    }

    window.displayMappingInterface = displayMappingInterface;
})(jQuery);