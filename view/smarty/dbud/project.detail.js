function initializeState(url, delay) {
	var refreshInterval = setInterval(
		function() {
			refreshState()
		}, delay
	);

	function refreshState() {
		$('#state').load(url + ' #state-inner', function() {
			var span = $('#state span').first();
			
			if (span.hasClass('label-success') || span.hasClass('label-important')) {
				window.location = url;
			}
		});
	}
}