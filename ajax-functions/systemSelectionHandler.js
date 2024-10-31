(function($) {
	// Define the fetchListsForSystemType function
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

				if (systemType === 'new_responder') {
					var lists = parseHtmlResponseToData(response);
					updateListsDropdown(lists, 'new_responder');
				} else if (systemType === 'rav_messer') {
					try {
						var lists = (typeof response === 'object') ? response.data : JSON.parse(response).data;
						updateListsDropdown(lists, 'rav_messer');
					} catch (error) {
						console.error('Error processing response:', error);
					}
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				console.error('AJAX request failed for', systemType, '. Status:', textStatus, 'Error:', errorThrown, 'Full response:', jqXHR.responseText);
			}
		});
	}

	// Function to parse HTML response into data
	function parseHtmlResponseToData(htmlResponse) {
		var tempDiv = document.createElement('div');
		tempDiv.innerHTML = htmlResponse;
		var options = tempDiv.querySelectorAll('option');
		return Array.from(options).map(function(option) {
			return {
				id: option.value,
				name: option.textContent
			};
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

		selectedSystemType = $(this).val(); // Update the global variable
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
})(jQuery);