(function($) {
	// Function to update the lists dropdown
	function updateListsDropdown(lists) {
		console.log('Starting updateListsDropdown function');

		// Validate the lists data
		if (!Array.isArray(lists) || lists.length === 0) {
			console.log('Received lists for dropdown');
			return;
		}


		// Selectors for the lists dropdowns
		var listSelection = $('#list_selection');
		var listSelectionInterested = $('#list_selection_interested');

		// Check if dropdowns are found
		if (listSelection.length === 0 || listSelectionInterested.length === 0) {
			console.error('List dropdown elements not found.');
			return;
		}
		console.log('Dropdown elements found:', listSelection, listSelectionInterested);

		// Clearing existing options and appending placeholders
		listSelection.empty().append('<option value="">בחרו רשימת רוכשים</option>');
		listSelectionInterested.empty().append('<option value="">בחרו רשימת מתעניינים</option>');

		// Appending each list as an option
		$.each(lists, function(index, list) {
			if (!list.id || !list.name) {
				console.error('Invalid list data:', list);
				return true; // Continue to the next iteration
			}

			var optionHtml = '<option value="' + $('<div>').text(list.id).html() + '">' + $('<div>').text(list.name).html() + '</option>';
			listSelection.append(optionHtml);
			listSelectionInterested.append(optionHtml);
		});

		console.log('Finished updating lists dropdown');
	}

	// Expose updateListsDropdown to the global scope
	window.updateListsDropdown = updateListsDropdown;

	function fetchListsForSystemType(action, systemType) {
		console.log('Initiating AJAX request for system type:', systemType, 'with action:', action);

		$.ajax({
			url: ajax_object.ajaxurl,
			type: 'POST',
			data: {
				action: action,
				security: ajax_object.nonce
			},
			beforeSend: function() {
				console.log('Sending AJAX request to fetch lists for system type:', systemType);
			},
			success: function(response, textStatus, jqXHR) {
				console.log('Received AJAX response for', systemType, 'Status:', textStatus);
				console.log('Debug: Raw response:', response);

				if (response.success) {
					var lists = response.data;
					updateListsDropdown(lists);
				} else {
					console.error('Error in AJAX response:', response.data);
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				console.error('AJAX request failed for', systemType, '. Status:', textStatus, 'Error:', errorThrown, 'Full response:', jqXHR.responseText);
			}
		});
	}

	// Reset custom fields function
	function resetCustomFields() {
		$('#mapping_interface .custom-field').each(function() {
			$(this).empty().append($('<option>', {
				value: '',
				text: 'בחירת שדה'
			}));
		});
	}

	// Attach the function to the global scope
	window.fetchListsForSystemType = fetchListsForSystemType;

	// Event listener for system selection change
	$('#system_selection').change(function() {
		resetCustomFields(); // Reset custom fields on system type change

		var selectedSystemType = $(this).val(); // Update the global variable
		console.log('System Selection Change - Selected System Type:', selectedSystemType);

		var action = '';
		if (selectedSystemType === 'new_responder') {
			action = 'fetch_new_responder_lists';
		} else if (selectedSystemType === 'rav_messer') {
			action = 'fetch_rav_messer_lists';
		}

		if (action !== '') {
			fetchListsForSystemType(action, selectedSystemType);
		}
	});

	console.log('updateListsDropdown function is ready and exposed globally');
})(jQuery);