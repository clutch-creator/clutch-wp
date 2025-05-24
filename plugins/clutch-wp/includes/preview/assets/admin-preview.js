(function () {
	// Ensure wp.data is available
	if (typeof wp !== 'undefined' && wp.data) {
		const { subscribe } = wp.data;
		const { select } = wp.data;

		let lastIsSaving = select('core/editor').isSavingPost();

		// Subscribe to changes in the editor state
		subscribe(() => {
			const isSaving = select('core/editor').isSavingPost();
			const isAutosaving = select('core/editor').isAutosavingPost();

			// Detect when a post is saved (not autosaved)
			if (!isSaving && lastIsSaving && !isAutosaving) {
				localStorage.setItem('refresh-preview', Date.now().toString());
			}

			lastIsSaving = isSaving;
		});
	} else {
		console.error('wp.data is not available');
	}
})();
