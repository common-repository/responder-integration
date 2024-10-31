(function($) {
    $(document).ready(function() {
        console.log('woosponder-search.js loaded and ready'); // Log when the script is ready

        $(document).on('connections-loaded', function() {
            // Initialize search functionality here
            console.log('Initializing search functionality');

            // Function to filter the connections list based on search input
            function filterConnections() {
                console.log('Filtering connections'); // Check if function is called
                var searchInput = $('#search-connections').val().toLowerCase();
                console.log('Search input:', searchInput); // Log the search input

                $('.connection-row').each(function() {
                    var connectionText = $(this).text().toLowerCase();
                    console.log('Connection text:', connectionText); // Log text of each connection

                    if (connectionText.includes(searchInput)) {
                        console.log('Showing:', $(this)); // Log when a match is found
                        $(this).show();
                    } else {
                        console.log('Hiding:', $(this)); // Log when no match is found
                        $(this).hide();
                    }
                });
            }

            // Event handler for the search input
            $('#search-connections').on('input', function() {
                console.log('Search input changed'); // Log when the search input changes
                filterConnections();
            });

            // Event handler for the search button click
            $('#search-button').on('click', function() {
                console.log('Search button clicked'); // Log when the search button is clicked
                filterConnections();
            });
        });
    });
})(jQuery);