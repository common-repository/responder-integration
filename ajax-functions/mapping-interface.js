(function($) {
    var customFields = null;
    var productDetails = null;
    var selectedListId = null;
    var selectedSystemType = null;
    var productId = null;

    $(document).ready(function() {
        console.log('Document ready - initializing event handlers');

        function initializeTagSelect(selector) {
            $(selector).select2({
                tags: true,
                tokenSeparators: [',', ' '],
                data: [],
                placeholder: 'כדי להפריד בין התגיות לוחצים רווח',
                ajax: {
                    url: ajax_object.ajaxurl,
                    method: 'POST',
                    dataType: 'json',
                    contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
                    processData: true,
                    data: function(params) {
                        return {
                            action: 'fetch_subscriber_tags',
                            nonce: ajax_object.fetch_tags_nonce,
                            searchTerm: params.term
                        };
                    },
                    processResults: function(data) {
                        console.log('Received AJAX response for tags:', data);
                        if (data.success) {
                            return {
                                results: data.data.map(function(tag) {
                                    return {
                                        id: tag.id,
                                        text: tag.text
                                    };
                                })
                            };
                        } else {
                            return {
                                results: []
                            };
                        }
                    },
                    cache: true
                }
            });
        }

        $('#system_selection').change(function() {
            selectedSystemType = $(this).val();
            console.log('System Type Change - Selected System Type:', selectedSystemType);

            if (selectedSystemType === 'new_responder') {
                console.log('System type is new_responder');

                if ($('#tag_select').length === 0) {
                    var tagLabel = $('<label for="tag_select" style="display: block;">הוספת תגית לרוכשים:</label>');
                    var tagSelect = $('<select multiple="multiple" id="tag_select" class="woosponder-tag-select" style="width: 100%;"></select>');
                    $('#list_selection').parent().append(tagLabel).append(tagSelect);
                    initializeTagSelect('#tag_select');
                }

                if ($('#tag_select_interested').length === 0) {
                    var tagLabelInterested = $('<label for="tag_select_interested" style="display: block;">הוספת תגית למעוניינים:</label>');
                    var tagSelectInterested = $('<select multiple="multiple" id="tag_select_interested" class="woosponder-tag-select" style="width: 100%;"></select>');
                    $('#list_selection_interested').parent().append(tagLabelInterested).append(tagSelectInterested);
                    initializeTagSelect('#tag_select_interested');
                }

                $('#tag_select, #tag_select_interested').show();
                $('label[for="tag_select"], label[for="tag_select_interested"]').show();
                console.log('Tag select and label for buyers and interested displayed');
            } else {
                console.log('System type is not new_responder, hiding and removing tag_select for buyers and interested if they exist');
                hideAndDestroyTagSelects();
            }

            checkAndDisplaySaveButton();
        });

        $('#save_custom_fields_mapping').prop('disabled', true);
        console.log('Save button initialized as disabled');

        $('#product_selection, #order_status_dropdown, #system_selection, #list_selection').change(function() {
            checkAndDisplaySaveButton();
        });

        $('#product_selection').change(function() {
            productId = $(this).val();
            console.log('Product Selection Change - Selected Product ID:', productId);
            $('#save_custom_fields_mapping').prop('disabled', true);

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
                        checkAndDisplaySaveButton();
                    } else {
                        console.error('Error fetching product details:', response);
                    }
                },
                error: function(_jqXHR, textStatus, errorThrown) {
                    console.error('AJAX error:', textStatus, errorThrown);
                }
            });
        });

        $('#list_selection').change(function() {
            selectedListId = $(this).val();
            var selectedSystem = $('#system_selection').val();
            console.log('List Selection Change - Selected List ID:', selectedListId);
            console.log('Selected system type:', selectedSystem);

            $('#save_custom_fields_mapping').prop('disabled', true);

            if (productDetails) {
                retrieveCustomFields(selectedListId, selectedSystem, productDetails);
            } else {
                $(document).one("productDetailsFetched", function(_event, productDetails) {
                    retrieveCustomFields(selectedListId, selectedSystem, productDetails);
                });
            }

            console.log('Calling retrieveCustomFields with List ID:', selectedListId, 'System Type:', selectedSystem, 'Product Details:', productDetails);
        });

        function retrieveCustomFields(listId, systemType, prodDetails) {
            console.log('Inside retrieveCustomFields - Parameters:', {
                listId,
                systemType,
                prodDetails
            });

            $.ajax({
                url: ajax_object.ajaxurl,
                type: 'POST',
                data: {
                    action: 'retrieve_custom_fields',
                    list_id: listId,
                    system_selection: systemType,
                    security: ajax_object.nonce
                },
                beforeSend: function() {
                    console.log('Preparing to make AJAX request for custom fields with system:', systemType);
                    $('#save_custom_fields_mapping').prop('disabled', true);
                },
                success: function(response) {
                    console.log('Custom Fields AJAX Success. Response:', response);

                    if (response.success && response.data) {
                        if (systemType === 'new_responder') {
                            const personalFields = response.data.personal_fields || [];
                            const allListsPersonalFields = response.data.all_lists_personal_fields || [];

                            if (Array.isArray(personalFields) && Array.isArray(allListsPersonalFields)) {
                                customFields = [...personalFields, ...allListsPersonalFields];
                            } else {
                                console.error('Expected arrays for personal_fields and all_lists_personal_fields');
                                customFields = [];
                            }
                        } else if (systemType === 'rav_messer') {
                            customFields = transformRavMesserData(response.data);
                        } else {
                            console.error('Unknown system type:', systemType);
                            return;
                        }

                        if (typeof displayMappingInterface === 'function') {
                            displayMappingInterface(customFields, prodDetails);
                        } else {
                            console.error('displayMappingInterface function not found.');
                        }

                        checkAndDisplaySaveButton();
                    } else {
                        customFields = [];
                        if (typeof displayMappingInterface === 'function') {
                            displayMappingInterface(customFields, prodDetails);
                        } else {
                            console.error('displayMappingInterface function not found.');
                        }
                        checkAndDisplaySaveButton();
                    }
                },
                error: function(_jqXHR, textStatus, errorThrown) {
                    console.error('AJAX error:', textStatus, errorThrown);
                    checkAndDisplaySaveButton();
                }
            });
        }

        function transformRavMesserData(data) {
            return data.PERSONAL_FIELDS.map(field => {
                return {
                    id: field.ID,
                    name: field.NAME,
                    type: 'rav_messer'
                };
            });
        }

        function deleteConnection(productId, connectionType, connectionIndex) {
            return new Promise((resolve, reject) => {
                console.log('Deleting connection:', { productId, connectionType, connectionIndex });
                $.ajax({
                    url: ajax_object.ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'woosponder_delete_connection',
                        product_id: productId,
                        connection_type: connectionType,
                        connection_index: connectionIndex,
                        security: ajax_object.delete_nonce
                    },
                    success: function(response) {
                        console.log('Delete connection response:', response);
                        if (response.success) {
                            console.log('Connection deleted successfully');
                            $('#' + connectionType + '-connection-row-' + productId + '-' + connectionIndex).remove();
                            resolve();
                        } else {
                            console.error('Failed to delete connection:', response.data);
                            reject(response.data);
                        }
                    },
                    error: function(_, _, error) {
                        console.error('AJAX error:', error);
                        reject(error);
                    }
                });
            });
        }

        $('#save_custom_fields_mapping').on('click', async function() {
            console.log('Save Button Clicked');
            $('#save_custom_fields_mapping').prop('disabled', true);  // Disable the button after click

            var productId = $('#product_selection').val();
            var selectedSystemType = $('#system_selection').val();
            var selectedBuyerListId = $('#list_selection').val();
            var selectedInterestedListId = $('#list_selection_interested').val();
            var selectedProductStatus = $('#order_status_dropdown').val();

            var errorOccurred = false;

            if (!selectedSystemType) {
                displayErrorMessage('system_selection', 'לא בחרתם מערכת*');
                errorOccurred = true;
            }
            if (!selectedBuyerListId) {
                displayErrorMessage('list_selection', '*לא בחרתם רשימת רוכשים');
                errorOccurred = true;
            }

            if (errorOccurred) {
                $('#save_custom_fields_mapping').prop('disabled', false);  // Enable the button if there is an error
                return;
            }

            try {
                const response = await $.ajax({
                    url: ajax_object.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'check_product_meta',
                        product_id: productId,
                        security: ajax_object.nonce
                    }
                });
                console.log('AJAX request successful. Response:', response);

                if (response.success) {
                    var existingMeta = response.data.existing_meta || [];

                    if (!Array.isArray(existingMeta)) {
                        existingMeta = [existingMeta];
                    }

                    var combinedMessage = "";
                    var existingInterestedConnectionIndex = null;
                    var existingBuyerConnectionIndex = null;

                    console.log('Existing Meta:', existingMeta);

                    // Check for existing interested connection
                    existingMeta.forEach(function(conn, index) {
                        if (conn.system_type === selectedSystemType && conn.interested_list_id) {
                            combinedMessage += "כבר מחוברת רשימת מתעניינים למוצר הזה האם לעדכן את החיבור הקיים?\n";
                            existingInterestedConnectionIndex = index;
                            console.log('Found existing interested connection:', conn);
                        }

                        // Check for existing buyer connection with the same list and status
                        if (conn.system_type === selectedSystemType &&
                            conn.buyer_list_id === selectedBuyerListId &&
                            conn.order_status === selectedProductStatus) {
                            combinedMessage += "כבר מחוברת רשימת רוכשים זו עם הסטטוס הנבחר למוצר הזה האם לעדכן את החיבור הקיים?\n";
                            existingBuyerConnectionIndex = index;
                            console.log('Found existing buyer connection:', conn);
                        }
                    });

                    console.log('Combined Message:', combinedMessage);

                    // Prompt user for confirmation
                    if (combinedMessage && !confirm(combinedMessage)) {
                        console.log('User chose not to update the existing connections.');
                        $('#save_custom_fields_mapping').prop('disabled', false);  // Enable the button if user cancels
                        return;
                    }

                    // If the user confirmed, delete the existing interested and buyer connections if they exist
                    if (existingInterestedConnectionIndex !== null && selectedInterestedListId) {
                        console.log('Deleting existing interested connection at index:', existingInterestedConnectionIndex);
                        await deleteConnection(productId, 'interested', existingInterestedConnectionIndex);
                    }

                    if (existingBuyerConnectionIndex !== null) {
                        console.log('Deleting existing buyer connection at index:', existingBuyerConnectionIndex);
                        await deleteConnection(productId, 'buyer', existingBuyerConnectionIndex);
                    }

                    // Add logic for only buyer addition without interested list
                    if (selectedInterestedListId === "" && existingInterestedConnectionIndex === null) {
                        existingMeta.forEach(function(conn, index) {
                            if (conn.system_type === selectedSystemType &&
                                conn.buyer_list_id === selectedBuyerListId &&
                                conn.order_status === selectedProductStatus) {
                                existingBuyerConnectionIndex = index;
                                console.log('Found existing buyer connection for only buyer addition:', conn);
                            }
                        });
                        if (existingBuyerConnectionIndex !== null) {
                            console.log('Deleting existing buyer connection at index for only buyer addition:', existingBuyerConnectionIndex);
                            await deleteConnection(productId, 'buyer', existingBuyerConnectionIndex);
                        }
                    }

                    // Proceed to open mapping interface and save new connection
                    openMappingInterface();
                } else {
                    console.error('Error in AJAX response:', response.data);
                    $('#save_custom_fields_mapping').prop('disabled', false);  // Enable the button if AJAX fails
                }
            } catch (error) {
                console.error('AJAX request to check existing meta keys failed:', error);
                $('#save_custom_fields_mapping').prop('disabled', false);  // Enable the button if AJAX request fails
            }
        });

        $('#SaveTheConnectionButton').on('click', function() {
            console.log('Save Connection Button Clicked');

            // Attempt to hide the mapping interface
            console.log('Attempting to hide #mapping_interface');
            $('#mapping_interface').hide();
            $('#mapping_interface').css('display', 'none');

            var productId = $('#product_selection').val();
            var selectedSystemType = $('#system_selection').val();
            var selectedBuyerListId = $('#list_selection').val();
            var selectedInterestedListId = $('#list_selection_interested').val();
            var selectedProductStatus = $('#order_status_dropdown').val();

            var buyerTags = selectedSystemType === 'new_responder' ?
                $('#tag_select').select2('data').map(tag => tag.id ? tag.id : tag.text) : [];
            var interestedTags = selectedSystemType === 'new_responder' ?
                $('#tag_select_interested').select2('data').map(tag => tag.id ? tag.id : tag.text) : [];

            var connection = {
                system_type: selectedSystemType,
                buyer_list_id: selectedBuyerListId,
                interested_list_id: selectedInterestedListId,
                field_mappings: getMappings() || {},
                order_status: selectedProductStatus,
                buyer_tags: buyerTags,
                interested_tags: interestedTags
            };

            console.log('Connection object before sending:', connection);

            $.ajax({
                url: ajax_object.ajaxurl,
                type: 'POST',
                data: {
                    action: 'woosponder_save_system_type',
                    product_id: productId,
                    connection: connection,
                    security: ajax_object.nonce
                },
                success: function(response) {
                    if (response.success) {
                        console.log('Connection saved successfully:', response);
                        $('#messageContainer').text("החיבור נשמר בהצלחה!").css('color', 'green');
                        $('#mapping_interface').hide(); // Hide mapping interface on successful save

                        // Hide and destroy tag select for buyers and interested
                        hideAndDestroyTagSelects();

                        // Reset system selection to trigger hiding of tag selects
                        $('#system_selection').val('').trigger('change');
                    } else {
                        console.error('Error saving connection:', response);
                        $('#messageContainer').text("Error saving the connection.").css('color', 'red');
                    }
                    $('#SaveTheConnectionButton').prop('disabled', false);
                },
                error: function(_jqXHR, textStatus, errorThrown) {
                    console.error('AJAX error when saving connection:', textStatus, errorThrown);
                    $('#SaveTheConnectionButton').prop('disabled', false);
                }
            });
        });

        function displayErrorMessage(elementId, message) {
            var errorMessage = $('<div/>', {
                text: message,
                class: 'error-message'
            }).css('color', 'red');

            $('#' + elementId).next('.error-message').remove();
            $('#' + elementId).after(errorMessage);
        }

        function openMappingInterface() {
            console.log('Displaying mapping interface.');

            if (typeof window.displayMappingInterface === 'function') {
                console.log('Calling displayMappingInterface function.');
                window.displayMappingInterface(customFields, productDetails);
                $('#mapping_interface').show();
            } else {
                console.error('displayMappingInterface function not found.');
            }
        }

        function hideAndDestroyTagSelects() {
            ['#tag_select', '#tag_select_interested'].forEach(function(selector) {
                if ($(selector).data('select2')) {
                    console.log('Destroying Select2 instance on ' + selector);
                    $(selector).select2('destroy');
                }
                $(selector).hide();
                $('label[for="' + selector.replace('#', '') + '"]').hide();
            });
            console.log('Tag select and label for buyers and interested removed');
        }

        function checkAndDisplaySaveButton() {
            var productSelected = $('#product_selection').val();
            var orderStatusSelected = $('#order_status_dropdown').val();
            var systemSelected = $('#system_selection').val();
            var listSelected = $('#list_selection').val();

            if (productSelected && orderStatusSelected && systemSelected && listSelected) {
                $('#save_custom_fields_mapping').prop('disabled', false);
            } else {
                $('#save_custom_fields_mapping').prop('disabled', true);
            }
        }
    });
})(jQuery);