(function($) {
    $(document).ready(function() {
        console.log('Document ready function executed.');

        // Function to fetch connections
        function fetchConnections() {
            console.log('fetchConnections function called.');
            $.ajax({
                url: ajax_object.ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'fetch_connections',
                    security: ajax_object.nonce
                },
                beforeSend: function() {
                    console.log('Before sending fetch_connections AJAX request.');
                },
                success: function(response) {
                    console.log('fetch_connections AJAX request successful.', response);
                    if (response.success) {
                        updateConnectionsList(response.data);
                        $(document).trigger('connections-loaded');
                    } else {
                        console.error('Failed to fetch connections:', response.data);
                    }
                },
                error: function(_, _, error) {
                    console.error('AJAX error:', error);
                }
            });
        }

        function updateConnectionsList(connections) {
            var connectionsList = $('#connections-list');
            connectionsList.empty();

            var orderStatusTranslations = {
                'wc-pending': 'ממתין לתשלום',
                'wc-processing': 'בתהליך',
                'wc-on-hold': 'בהמתנה',
                'wc-completed': 'הושלם',
                'wc-cancelled': 'בוטל',
                'wc-refunded': 'הוחזר',
                'wc-failed': 'נכשל'
            };

            $.each(connections, function(index, connection) {
                var systemTypeLabel = connection.system_type === 'rav_messer' ? 'רב מסר' :
                    connection.system_type === 'new_responder' ? 'רב מסר החדשה' : 'Unknown System';

                var connectionTypeLabel = connection.connection_type === 'buyer' ? 'חיבור רוכשים' : 'חיבור מתעניינים';

                var tagsHtml = '';
                if (connection.tags) {
                    var tagArray = Array.isArray(connection.tags) ? connection.tags : [connection.tags];
                    tagsHtml = '<div class="connection-tags"><strong>תגיות:</strong> ' + tagArray.join(', ') + '</div>';
                }

                var orderStatusHtml = '';
                if (connection.connection_type === 'buyer' && connection.order_status) {
                    var translatedStatus = orderStatusTranslations[connection.order_status] || connection.order_status;
                    var orderStatusClass = 'status-' + translatedStatus.replace(/\s+/g, '-').toLowerCase();
                    orderStatusHtml = '<div class="connection-order-status ' + orderStatusClass + '"><strong>סטטוס הזמנה:</strong> ' + translatedStatus + '</div>';
                }

                var deleteButtonHtml = '<button class="delete-connection" data-product-id="' + connection.product_id +
                    '" data-connection-type="' + connection.connection_type + '" data-connection-index="' + connection.index + '" data-product-name="' + connection.product_name +
                    '" data-list-name="' + connection.list_name + '">מחיקה</button>';

                var connectionHtml = '<div class="connection-row ' + connection.connection_type + '-connection" id="' + connection.connection_type + '-connection-row-' + connection.product_id + '-' + connection.index + '">' +
                    '<div class="connection-details">' +
                    connectionTypeLabel + ': מוצר ' + connection.product_name + ' לרשימת ' + connection.list_name + ' במערכת ' + systemTypeLabel +
                    '</div>' +
                    tagsHtml +
                    orderStatusHtml +
                    deleteButtonHtml +
                    '</div>';

                connectionsList.append(connectionHtml);
            });
        }

        function deleteConnection(productId, connectionType, connectionIndex) {
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
                    security: ajax_object.delete_nonce // Ensure this is correct
                },
                success: function(response) {
                    console.log('Delete connection response:', response);
                    if (response.success) {
                        console.log('Connection deleted successfully');
                        $('#' + connectionType + '-connection-row-' + productId + '-' + connectionIndex).remove();
                    } else {
                        console.error('Failed to delete connection:', response.data);
                    }
                },
                error: function(_, _, error) {
                    console.error('AJAX error:', error);
                }
            });
        }

        function loadActiveConnectionsContent() {
            console.log('Loading Active Connections content.');

            $.ajax({
                url: ajax_object.ajaxurl,
                type: 'POST',
                data: {
                    action: 'load_active_connections',
                    security: ajax_object.nonce
                },
                beforeSend: function() {
                    console.log('Before sending load_active_connections AJAX request.');
                },
                success: function(response) {
                    console.log('load_active_connections AJAX request successful.', response);
                    $('#tab-content').html(response);
                    fetchConnections();
                },
                error: function(_, _, error) {
                    console.error('Error loading active connections:', error);
                }
            });
        }

        if (window.location.href.indexOf("tab=active_connections") > -1) {
            $('#woosponder-active-connections-tab').addClass('nav-tab-active');
            loadActiveConnectionsContent();
        }

        $(document).on('click', '.delete-connection', function() {
            var productId = $(this).data('product-id');
            var connectionType = $(this).data('connection-type');
            var connectionIndex = $(this).data('connection-index');
            var productName = $(this).attr('data-product-name');
            var listName = $(this).attr('data-list-name');
            var connectionTypeHebrew = connectionType === 'buyer' ? 'רוכשים' : 'מתעניינים';

            var confirmMessage = 'למחוק את חיבור ' + connectionTypeHebrew + ' למוצר "' + productName + '" עם הרשימה "' + listName + '"?';

            if (confirm(confirmMessage)) {
                if (productId) {
                    deleteConnection(productId, connectionType, connectionIndex);
                }
            } else {
                console.log('Deletion cancelled.');
            }
        });

        $('#woosponder-active-connections-tab').on('click', function(e) {
            loadActiveConnectionsContent();
            e.preventDefault();
            console.log('Active Connections tab clicked.');

            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');

            var newUrl = '?page=woosponder&tab=active_connections';
            history.pushState(null, '', newUrl);

            $.ajax({
                url: ajax_object.ajaxurl,
                type: 'POST',
                data: {
                    action: 'load_active_connections',
                    security: ajax_object.nonce
                },
                beforeSend: function() {
                    console.log('Before sending load_active_connections AJAX request.');
                },
                success: function(response) {
                    console.log('load_active_connections AJAX request successful.', response);
                    $('#tab-content').html(response);
                    fetchConnections();
                },
                error: function(_, _, error) {
                    console.error('Error loading active connections:', error);
                }
            });
        });
    });
})(jQuery);
