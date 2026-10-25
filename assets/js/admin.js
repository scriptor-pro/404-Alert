/**
 * 404 Alert Admin JS
 */

(function() {
	'use strict';

	// Reschedule cron if digest time changes
	document.addEventListener('DOMContentLoaded', function() {
		const digestTime = document.getElementById('digest_time');
		const testForm = document.getElementById('404-alert-smtp-test-form');
		const testButton = document.getElementById('404-alert-test-smtp');

		if (digestTime) {
			digestTime.addEventListener('change', function() {
				// Schedule will be recalculated on settings save
			});
		}

		if (testForm && testButton) {
			testButton.addEventListener('click', function(event) {
				const host = document.getElementById('smtp_host');
				const port = document.getElementById('smtp_port');
				const encryption = document.getElementById('smtp_encryption');
				const username = document.getElementById('smtp_username');
				const password = document.getElementById('smtp_password');
				const fromEmail = document.getElementById('smtp_from_email');
				const fromName = document.getElementById('smtp_from_name');

				document.getElementById('404-alert-test-smtp-host').value = host ? host.value : '';
				document.getElementById('404-alert-test-smtp-port').value = port ? port.value : '';
				document.getElementById('404-alert-test-smtp-encryption').value = encryption ? encryption.value : '';
				document.getElementById('404-alert-test-smtp-username').value = username ? username.value : '';
				document.getElementById('404-alert-test-smtp-password').value = password ? password.value : '';
				document.getElementById('404-alert-test-smtp-from-email').value = fromEmail ? fromEmail.value : '';
				document.getElementById('404-alert-test-smtp-from-name').value = fromName ? fromName.value : '';

				testForm.submit();
				event.preventDefault();
			});
		}
	});
})();
