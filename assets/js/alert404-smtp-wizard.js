(function() {
    const STEP1 = 1, STEP2_1 = '2-1', STEP2_2 = '2-2', STEP3 = 3, STEP4 = 4;
    let currentState = {};
    let currentStep = STEP1;

    // Initialize wizard
    document.addEventListener('DOMContentLoaded', function() {
        initializeWizard();
    });

    function initializeWizard() {
        // Fetch current state from server
        fetchWizardState();

        // Attach event listeners
        document.getElementById('btn-back').addEventListener('click', goBack);
        document.getElementById('btn-next').addEventListener('click', goNext);
        document.getElementById('btn-test').addEventListener('click', testConnection);
        document.getElementById('btn-finalize').addEventListener('click', finalize);
        document.getElementById('btn-modify').addEventListener('click', goToStep1);
        document.getElementById('404-alert-wizard-close').addEventListener('click', closeWizard);

        // Provider selection
        document.querySelectorAll('input[name="provider_id"]').forEach(radio => {
            radio.addEventListener('change', selectProvider);
        });
    }

    function fetchWizardState() {
        const nonce = document.getElementById('wizard-nonce').value;
        const ajaxUrl = document.getElementById('wizard-ajax-url').value;

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=404_alert_smtp_wizard_get_state&_wpnonce=' + nonce
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentState = data.data;
                if (data.data.step) {
                    currentStep = data.data.step;
                    showStep(currentStep);
                } else {
                    showStep(STEP1);
                }
            }
        });
    }

    function selectProvider(e) {
        const providerId = e.target.value;
        currentState.provider_id = providerId;

        if (providerId === 'custom') {
            currentStep = STEP2_2;
        } else {
            currentState.host = window.alert404SMTPPresets[providerId].host;
            currentState.port = window.alert404SMTPPresets[providerId].port;
            currentState.encryption = window.alert404SMTPPresets[providerId].encryption;
            currentStep = STEP2_1;
            updatePresetHelp(providerId);
        }

        showStep(currentStep);
    }

    function updatePresetHelp(providerId) {
        const preset = window.alert404SMTPPresets[providerId];
        const helpDiv = document.getElementById('preset-help');
        if (preset && preset.info) {
            helpDiv.innerHTML = preset.info;
        }
        document.getElementById('step-2-1-title').innerText = 'Identifiants pour ' + preset.name;
    }

    function showStep(step) {
        // Hide all steps
        document.querySelectorAll('.wizard-step').forEach(el => {
            el.style.display = 'none';
        });

        // Show current step
        if (step === STEP1) {
            document.getElementById('wizard-step-1').style.display = 'block';
            updateButtons(false, false, true, false, false);
        } else if (step === STEP2_1) {
            document.getElementById('wizard-step-2-1').style.display = 'block';
            restoreFormFields(['email', 'password']);
            updateButtons(true, false, false, true, false);
        } else if (step === STEP2_2) {
            document.getElementById('wizard-step-2-2').style.display = 'block';
            restoreFormFields(['host', 'port', 'encryption', 'email', 'password']);
            updateButtons(true, false, false, true, false);
        } else if (step === STEP3) {
            document.getElementById('wizard-step-3').style.display = 'block';
            document.getElementById('test-spinner').style.display = 'block';
            document.getElementById('test-result').style.display = 'none';
            updateButtons(false, false, false, false, false);
        } else if (step === STEP4) {
            document.getElementById('wizard-step-4').style.display = 'block';
            displaySummary();
            updateButtons(false, false, false, false, true);
        }

        currentStep = step;
    }

    function restoreFormFields(fields) {
        fields.forEach(field => {
            const input = document.querySelector('[name="' + field + '"]');
            if (input && currentState[field]) {
                input.value = currentState[field];
            }
        });
    }

    function updateButtons(showBack, showTest, showNext, showTestBtn, showFinalize) {
        document.getElementById('btn-back').style.display = showBack ? 'inline-block' : 'none';
        document.getElementById('btn-test').style.display = showTestBtn ? 'inline-block' : 'none';
        document.getElementById('btn-next').style.display = showNext ? 'inline-block' : 'none';
        document.getElementById('btn-finalize').style.display = showFinalize ? 'inline-block' : 'none';
        document.getElementById('btn-modify').style.display = showFinalize ? 'inline-block' : 'none';
    }

    function saveFormData() {
        if (currentStep === STEP2_1) {
            currentState.email = document.getElementById('wizard-email').value;
            currentState.password = document.getElementById('wizard-password').value;
            currentState.step = STEP2_1;
        } else if (currentStep === STEP2_2) {
            currentState.host = document.getElementById('wizard-host').value;
            currentState.port = document.getElementById('wizard-port').value;
            currentState.encryption = document.getElementById('wizard-encryption').value;
            currentState.email = document.getElementById('wizard-custom-email').value;
            currentState.password = document.getElementById('wizard-custom-password').value;
            currentState.step = STEP2_2;
        }
    }

    function goNext() {
        saveFormData();

        // Validate
        if (!validateStep(currentStep)) {
            return;
        }

        saveStepToServer(currentState);
        showStep(STEP3);
    }

    function testConnection() {
        saveFormData();

        if (!validateStep(currentStep)) {
            return;
        }

        currentState.step = currentStep;
        saveStepToServer(currentState);

        // Perform test
        const nonce = document.getElementById('wizard-nonce').value;
        const ajaxUrl = document.getElementById('wizard-ajax-url').value;

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=404_alert_smtp_wizard_test_connection&_wpnonce=' + nonce + '&data=' + encodeURIComponent(JSON.stringify(currentState))
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('test-spinner').style.display = 'none';
            document.getElementById('test-result').style.display = 'block';

            const msgEl = document.getElementById('test-message');
            if (data.success) {
                msgEl.innerHTML = '✅ ' + data.data.message;
                msgEl.className = 'success';
                setTimeout(() => showStep(STEP4), 1500);
            } else {
                msgEl.innerHTML = '❌ ' + data.data.message;
                msgEl.className = 'error';
                setTimeout(() => showStep(currentStep === STEP2_1 ? STEP2_1 : STEP2_2), 2000);
            }
        });
    }

    function goBack() {
        if (currentStep === STEP2_1 || currentStep === STEP2_2) {
            showStep(STEP1);
        } else if (currentStep === STEP3) {
            showStep(currentState.provider_id === 'custom' ? STEP2_2 : STEP2_1);
        }
    }

    function goToStep1() {
        showStep(STEP1);
    }

    function validateStep(step) {
        if (step === STEP2_1) {
            const email = document.getElementById('wizard-email').value.trim();
            const password = document.getElementById('wizard-password').value;

            if (!email || !password) {
                showMessage('Veuillez remplir tous les champs.', 'error');
                return false;
            }
            if (!isValidEmail(email)) {
                showMessage('Veuillez entrer une adresse email valide.', 'error');
                return false;
            }
        } else if (step === STEP2_2) {
            const host = document.getElementById('wizard-host').value.trim();
            const port = document.getElementById('wizard-port').value;
            const email = document.getElementById('wizard-custom-email').value.trim();
            const password = document.getElementById('wizard-custom-password').value;

            if (!host || !port || !email || !password) {
                showMessage('Veuillez remplir tous les champs.', 'error');
                return false;
            }
            if (!isValidEmail(email)) {
                showMessage('Veuillez entrer une adresse email valide.', 'error');
                return false;
            }
            if (port < 1 || port > 65535) {
                showMessage('Veuillez entrer un port valide (1-65535).', 'error');
                return false;
            }
        }
        return true;
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function saveStepToServer(data) {
        const nonce = document.getElementById('wizard-nonce').value;
        const ajaxUrl = document.getElementById('wizard-ajax-url').value;

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=404_alert_smtp_wizard_save_step&_wpnonce=' + nonce + '&data=' + encodeURIComponent(JSON.stringify(data))
        });
    }

    function displaySummary() {
        document.getElementById('summary-provider').innerText = currentState.provider_id === 'custom' ? 'Personnalisé' : (window.alert404SMTPPresets[currentState.provider_id]?.name || '-');
        document.getElementById('summary-email').innerText = currentState.email || '-';
        document.getElementById('summary-server').innerText = (currentState.host || '-') + ':' + (currentState.port || '-');
    }

    function finalize() {
        const nonce = document.getElementById('wizard-nonce').value;
        const ajaxUrl = document.getElementById('wizard-ajax-url').value;

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=404_alert_smtp_wizard_finalize&_wpnonce=' + nonce + '&data=' + encodeURIComponent(JSON.stringify(currentState))
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Configuration sauvegardée !', 'success');
                setTimeout(() => closeWizard(), 1000);
            } else {
                showMessage(data.data.message || 'Erreur lors de la sauvegarde.', 'error');
            }
        });
    }

    function showMessage(msg, type) {
        const msgEl = document.getElementById('wizard-message');
        msgEl.innerText = msg;
        msgEl.className = 'wizard-message ' + type;
        msgEl.style.display = 'block';
    }

    function closeWizard() {
        document.getElementById('404-alert-smtp-wizard-overlay').style.display = 'none';
    }

    // Expose openWizard globally
    window.alert404OpenSMTPWizard = function() {
        document.getElementById('404-alert-smtp-wizard-overlay').style.display = 'flex';
        fetchWizardState();
    };
})();
