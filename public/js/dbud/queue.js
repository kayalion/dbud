function initializeQueue(url, delay) {
	setInterval(
		function() {
			refreshQueue()
		}, delay
	);

	function refreshQueue() {
		$('#activity').load(url + ' #activity-inner');
	}
}