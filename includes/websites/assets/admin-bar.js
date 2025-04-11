(function ($) {
	$(document).ready(async function () {
		if (
			!ClutchAdminBar ||
			!ClutchAdminBar.restUrl ||
			!ClutchAdminBar.nonce
		) {
			console.error('ClutchAdminBar.restUrl or nonce is not defined.');
			return;
		}

		const saveHostUrl = `${ClutchAdminBar.restUrl.replace('/get-websites', '/save-selected-host')}`;
		const getHostUrl = `${ClutchAdminBar.restUrl.replace('/get-websites', '/get-selected-host')}`;

		const adminBar = $('#wp-admin-bar-root-default'); // Admin bar root

		// Create a dropdown container styled like WordPress admin bar
		const dropdownContainer = $(
			'<li id="wp-admin-bar-clutch-hosts-dropdown" class="menupop"></li>'
		);
		const dropdownLink = $(
			'<a href="#" class="ab-item"><span class="selected-host" style="float: left">Loading...</span> <span class="ab-icon dashicons dashicons-arrow-down" style="display: none;"></span></a>'
		);
		const dropdownMenu = $(
			'<ul class="ab-submenu" style="display: none;"></ul>'
		);

		// Create a logo element using the existing clutch.svg file
		const clutchLogo = $(
			`<div style="background-image: url(${ClutchAdminBar.svgIcon}) !important; background-repeat: no-repeat; background-position: center; background-size: 20px; width: 20px; height: 100%; margin-right: 8px; float: left;"></div>`
		);

		// Append the logo to the dropdown link
		dropdownLink.prepend(clutchLogo);

		// Append the dropdown to the admin bar
		dropdownContainer.append(dropdownLink).append(dropdownMenu);
		adminBar.append(dropdownContainer);

		let previousSelectedHost = null; // Track the previously selected host

		// Function to fetch and update the dropdown
		async function updateDropdown() {
			try {
				const response = await fetch(ClutchAdminBar.restUrl);
				const websites = await response.json();

				// Clear the dropdown menu to avoid duplicates
				dropdownMenu.empty();

				const promises = websites.map(async (website) => {
					if (website.url.includes('localhost')) {
						try {
							const infoResponse = await fetch(
								`${website.url}/api/info`,
								{ method: 'GET' }
							);
							if (!infoResponse.ok)
								throw new Error(
									`Failed to fetch info for ${website.url}`
								);
							const data = await infoResponse.json();
							if (
								data.url &&
								data.url.startsWith(window.location.origin)
							) {
								addWebsiteToDropdown(dropdownMenu, website);
							} else {
								console.warn(
									`Website ${website.url} does not match the WordPress backend hostname.`
								);
							}
						} catch (error) {
							console.warn(
								`Website ${website.url} is not reachable.`
							);
						}
					} else {
						addWebsiteToDropdown(dropdownMenu, website);
					}
				});

				await Promise.allSettled(promises);

				// Check if there are no hosts available
				if (dropdownMenu.children().length === 0) {
					dropdownLink
						.find('.selected-host')
						.text('No hosts to preview found');
					dropdownLink.find('.ab-icon').hide(); // Hide chevron
					return; // Exit early since there are no hosts
				}

				// Retrieve the previously selected host
				try {
					const selectedHostResponse = await fetch(getHostUrl, {
						method: 'GET',
						headers: {
							'X-WP-Nonce': ClutchAdminBar.nonce,
						},
					});
					const { selectedHost } = await selectedHostResponse.json();

					// If no host is persisted, select the first host as default
					if (!selectedHost && dropdownMenu.children().length > 0) {
						const firstHost = dropdownMenu
							.children()
							.first()
							.data('url');
						await saveSelectedHost(firstHost);
					}

					// Highlight the selected host, update the trigger text, and remove it from the dropdown
					if (selectedHost) {
						dropdownMenu.children().each(function () {
							const item = $(this);
							if (item.data('url') === selectedHost) {
								item.remove(); // Remove the selected host from the dropdown
								dropdownLink
									.find('.selected-host')
									.text(`Previewing: ${item.text()}`);
								previousSelectedHost = {
									url: item.data('url'),
									name: item.text(),
								}; // Track the previous host
							}
						});
					} else {
						dropdownLink
							.find('.selected-host')
							.text('Previewing: Select Host'); // Default text if no host is selected
					}

					// Show chevron if options are available
					toggleChevronVisibility();
				} catch (error) {
					console.error('Error retrieving selected host:', error);
					dropdownLink
						.find('.selected-host')
						.text('Previewing: Select Host'); // Fallback text on error
					toggleChevronVisibility();
				}
			} catch (error) {
				console.error('Error fetching websites:', error);
			}
		}

		// Initial dropdown update
		await updateDropdown();

		// Set interval to update the dropdown every 10 seconds
		setInterval(updateDropdown, 10000);

		// Handle dropdown selection
		dropdownMenu.on('click', 'li', async function (e) {
			e.preventDefault();
			const selectedUrl = $(this).data('url');
			if (selectedUrl) {
				// Add the previously selected host back to the dropdown
				if (previousSelectedHost) {
					addWebsiteToDropdown(dropdownMenu, previousSelectedHost);
				}

				await saveSelectedHost(selectedUrl); // Save the selected host via REST API
				dropdownMenu.children().removeClass('current');
				$(this).addClass('current');
				dropdownLink
					.find('.selected-host')
					.text(`Previewing: ${$(this).text()}`); // Update the trigger text with prefix

				// Update the previous selected host
				previousSelectedHost = {
					url: $(this).data('url'),
					name: $(this).text(),
				};

				$(this).remove(); // Remove the newly selected host from the dropdown

				// Hide chevron if no options are available
				toggleChevronVisibility();
			}
		});

		// Toggle dropdown visibility on click
		dropdownLink.on('click', function (e) {
			e.preventDefault();
			dropdownMenu.toggle();
		});

		// Hide dropdown when clicking outside
		$(document).on('click', function (e) {
			if (
				!dropdownContainer.is(e.target) &&
				dropdownContainer.has(e.target).length === 0
			) {
				dropdownMenu.hide();
			}
		});

		// Helper function to toggle chevron visibility
		function toggleChevronVisibility() {
			if (dropdownMenu.children().length === 0) {
				dropdownLink.find('.ab-icon').hide(); // Hide chevron
			} else {
				dropdownLink.find('.ab-icon').show(); // Show chevron
			}
		}

		// Helper function to add a website to the dropdown
		function addWebsiteToDropdown(dropdownMenu, website) {
			let websiteName = website.name;

			if (website.url.includes('localhost')) {
				websiteName += ` (Local)`;
			}

			const menuItem = $(
				`<li class="ab-item" data-url="${website.url}"><a href="#">${websiteName}</a></li>`
			);
			dropdownMenu.append(menuItem);
		}

		// Helper function to save the selected host via REST API
		async function saveSelectedHost(selectedUrl) {
			try {
				const response = await fetch(saveHostUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': ClutchAdminBar.nonce,
					},
					body: JSON.stringify({ selectedHost: selectedUrl }),
				});
				if (!response.ok)
					throw new Error('Failed to save selected host');
				const data = await response.json();
				console.log(`Selected host saved: ${data.selectedHost}`);
			} catch (error) {
				console.error('Error saving selected host:', error);
			}
		}
	});
})(jQuery);
