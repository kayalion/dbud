function initializeQueue(url, delay) {
	setInterval(
		function() {
			refreshQueue()
		}, delay
	);

	function refreshQueue() {
		$('#queue').load(url + ' #queue-inner');
	}
}