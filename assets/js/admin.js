/**
 * 404 Alert Admin JS
 */

(function() {
	'use strict';

	// Reschedule cron if digest time changes
	document.addEventListener('DOMContentLoaded', function() {
		const digestTime = document.getElementById('digest_time');

		if (digestTime) {
			digestTime.addEventListener('change', function() {
				// Schedule will be recalculated on settings save
			});
		}
	});
})();
