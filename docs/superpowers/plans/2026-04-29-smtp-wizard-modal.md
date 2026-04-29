# Wizard SMTP Modal pour Débutants - Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement an interactive 4-step modal wizard that guides users through SMTP configuration with conditional branching, state persistence, and connection testing.

**Architecture:** The wizard uses a modal dialog with server-side state persistence (WordPress transient), AJAX-driven step navigation, and conditional branching between preset vs. custom configuration. Each step saves state to allow resumption if the user closes and reopens. The final step commits the configuration to WordPress options.

**Tech Stack:** PHP 7.4+, WordPress Settings API, jQuery, PHPMailer (WordPress native), AJAX with nonces.

---

## File Structure

### Files to Create
1. **`includes/class-alert404-smtp-wizard.php`** (320 lines)
   - Core wizard logic: state management, step handlers, AJAX endpoints
   - Methods: initialization, step navigation, connection testing, finalization
   
2. **`assets/js/alert404-smtp-wizard.js`** (280 lines)
   - Modal DOM management: step rendering, transitions, form handling
   - AJAX communication: save step state, test connection, finalize
   - Front-end validation and error display

3. **`assets/css/alert404-smtp-wizard.css`** (120 lines)
   - Modal styling: responsive layout, form controls, animations
   - Spinner animation, error/success message styling
   - Accessibility: focus states, color contrast

4. **`templates/smtp-wizard-modal.php`** (200 lines)
   - HTML structure for all 4 steps (hidden by default, revealed by JS)
   - Form fields, help text, validation messages
   - Navigation buttons (Back, Next, Test Connection, Finalize)

### Files to Modify
1. **`includes/class-alert404-settings.php`** (add ~50 lines)
   - Add menu item "Configuration SMTP" to 404-Alert submenu
   - Enqueue wizard scripts and styles

2. **`includes/class-alert404-smtp-handler.php`** (add ~40 lines)
   - Add method `test_connection()` to verify SMTP parameters
   - Returns array with success flag and error message

---

## Task 1: Create SMTP Wizard Backend Class

**Files:**
- Create: `includes/class-alert404-smtp-wizard.php`

- [ ] **Step 1: Write test for wizard state initialization**

Create file `tests/unit/Test_Alert404_SMTP_Wizard.php`:

```php
<?php
class Test_Alert404_SMTP_Wizard extends WP_UnitTestCase {
    public function test_get_initial_state_returns_empty_when_no_transient() {
        delete_transient( '404_alert_smtp_wizard_state' );
        $state = Alert404_SMTP_Wizard::get_wizard_state();
        
        $this->assertIsArray( $state );
        $this->assertFalse( isset( $state['step'] ) );
    }

    public function test_save_step_stores_state_in_transient() {
        $data = array(
            'step'        => 1,
            'provider_id' => 'gmail',
        );
        Alert404_SMTP_Wizard::save_step( $data );
        
        $transient = get_transient( '404_alert_smtp_wizard_state' );
        $this->assertEquals( 1, $transient['step'] );
        $this->assertEquals( 'gmail', $transient['provider_id'] );
    }

    public function test_clear_wizard_state_deletes_transient() {
        set_transient( '404_alert_smtp_wizard_state', array( 'step' => 1 ), HOUR_IN_SECONDS );
        Alert404_SMTP_Wizard::clear_wizard_state();
        
        $this->assertFalse( get_transient( '404_alert_smtp_wizard_state' ) );
    }
}
```

Run: `vendor/bin/phpunit tests/unit/Test_Alert404_SMTP_Wizard.php`
Expected: 3 FAILs (class doesn't exist yet)

- [ ] **Step 2: Create wizard class with state management**

Create `includes/class-alert404-smtp-wizard.php`:

```php
<?php
/**
 * SMTP Wizard for 404 Alert plugin.
 *
 * @package Alert404
 */

defined( 'ABSPATH' ) || exit;

/**
 * Wizard for SMTP configuration.
 */
class Alert404_SMTP_Wizard {
    const TRANSIENT_KEY = '404_alert_smtp_wizard_state';
    const TRANSIENT_TTL = 24 * HOUR_IN_SECONDS;

    /**
     * Initialize wizard hooks.
     */
    public static function init(): void {
        add_action( 'admin_menu', array( self::class, 'add_menu_page' ) );
        add_action( 'wp_ajax_404_alert_smtp_wizard_get_state', array( self::class, 'ajax_get_state' ) );
        add_action( 'wp_ajax_404_alert_smtp_wizard_save_step', array( self::class, 'ajax_save_step' ) );
        add_action( 'wp_ajax_404_alert_smtp_wizard_test_connection', array( self::class, 'ajax_test_connection' ) );
        add_action( 'wp_ajax_404_alert_smtp_wizard_finalize', array( self::class, 'ajax_finalize' ) );
    }

    /**
     * Get current wizard state from transient.
     */
    public static function get_wizard_state(): array {
        $state = get_transient( self::TRANSIENT_KEY );
        return is_array( $state ) ? $state : array();
    }

    /**
     * Save wizard step state.
     */
    public static function save_step( array $data ): void {
        $state = self::get_wizard_state();
        $state = array_merge( $state, $data );
        set_transient( self::TRANSIENT_KEY, $state, self::TRANSIENT_TTL );
    }

    /**
     * Clear wizard state.
     */
    public static function clear_wizard_state(): void {
        delete_transient( self::TRANSIENT_KEY );
    }

    /**
     * AJAX: Get current wizard state.
     */
    public static function ajax_get_state(): void {
        check_ajax_referer( '404_alert_smtp_wizard_nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Access denied' ), 403 );
        }

        $state = self::get_wizard_state();
        wp_send_json_success( $state );
    }

    /**
     * AJAX: Save step state.
     */
    public static function ajax_save_step(): void {
        check_ajax_referer( '404_alert_smtp_wizard_nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Access denied' ), 403 );
        }

        $data = isset( $_POST['data'] ) ? wp_unslash( $_POST['data'] ) : array();
        
        self::save_step( $data );
        wp_send_json_success( array( 'message' => 'Step saved' ) );
    }

    /**
     * AJAX: Test SMTP connection.
     */
    public static function ajax_test_connection(): void {
        check_ajax_referer( '404_alert_smtp_wizard_nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Access denied' ), 403 );
        }

        $data = isset( $_POST['data'] ) ? wp_unslash( $_POST['data'] ) : array();
        
        $result = Alert404_SMTP_Handler::test_connection(
            array(
                'host'       => sanitize_text_field( $data['host'] ?? '' ),
                'port'       => absint( $data['port'] ?? 587 ),
                'encryption' => sanitize_text_field( $data['encryption'] ?? 'tls' ),
                'email'      => sanitize_email( $data['email'] ?? '' ),
                'password'   => $data['password'] ?? '',
            )
        );

        if ( $result['success'] ) {
            wp_send_json_success( array( 'message' => 'Connexion réussie !' ) );
        } else {
            wp_send_json_error( array( 'message' => $result['error'] ?? 'Erreur inconnue' ) );
        }
    }

    /**
     * AJAX: Finalize and save configuration.
     */
    public static function ajax_finalize(): void {
        check_ajax_referer( '404_alert_smtp_wizard_nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Access denied' ), 403 );
        }

        $state = self::get_wizard_state();
        
        if ( empty( $state['host'] ) || empty( $state['email'] ) || empty( $state['password'] ) ) {
            wp_send_json_error( array( 'message' => 'Configuration incomplète' ) );
        }

        $options = get_option( '404_alert_options', array() );
        $options['smtp_host']       = sanitize_text_field( $state['host'] );
        $options['smtp_port']       = absint( $state['port'] );
        $options['smtp_encryption'] = sanitize_text_field( $state['encryption'] );
        $options['smtp_email']      = sanitize_email( $state['email'] );
        $options['smtp_password']   = $state['password']; // Will be encrypted by sanitize_options()
        
        update_option( '404_alert_options', $options );
        self::clear_wizard_state();

        wp_send_json_success( array( 'message' => 'Configuration sauvegardée !' ) );
    }

    /**
     * Add wizard menu page.
     */
    public static function add_menu_page(): void {
        add_submenu_page(
            '404_alert',
            'Configuration SMTP',
            'Configuration SMTP',
            'manage_options',
            '404_alert_smtp_wizard',
            array( self::class, 'render_page' )
        );
    }

    /**
     * Render wizard page.
     */
    public static function render_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Accès refusé' );
        }

        wp_enqueue_script( 'alert404-smtp-wizard' );
        wp_enqueue_style( 'alert404-smtp-wizard' );

        include ALERT404_DIR . 'templates/smtp-wizard-modal.php';
    }
}

// Initialize on plugins_loaded.
add_action( 'plugins_loaded', array( 'Alert404_SMTP_Wizard', 'init' ) );
```

- [ ] **Step 3: Run test to verify implementation**

Run: `vendor/bin/phpunit tests/unit/Test_Alert404_SMTP_Wizard.php`
Expected: 3 PASSes

- [ ] **Step 4: Commit**

```bash
git add includes/class-alert404-smtp-wizard.php tests/unit/Test_Alert404_SMTP_Wizard.php
git commit -m "feat: add SMTP wizard backend class with state management"
```

---

## Task 2: Add test_connection() to SMTP Handler

**Files:**
- Modify: `includes/class-alert404-smtp-handler.php`

- [ ] **Step 1: Add test connection method**

At the end of the `Alert404_SMTP_Handler` class (before closing brace), add:

```php
    /**
     * Test SMTP connection.
     *
     * @param array $config Configuration array (host, port, encryption, email, password).
     * @return array Array with 'success' => bool and 'error' => string if failed.
     */
    public static function test_connection( array $config ): array {
        require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
        require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
        require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';

        if ( ! class_exists( 'PHPMailer' ) && class_exists( '\\PHPMailer\\PHPMailer\\PHPMailer' ) ) {
            class_alias( '\\PHPMailer\\PHPMailer\\PHPMailer', 'PHPMailer' );
        }

        $phpmailer = new PHPMailer( true );

        try {
            $phpmailer->isSMTP();
            $phpmailer->Host       = $config['host'];
            $phpmailer->Port       = $config['port'];
            $phpmailer->SMTPSecure = self::get_smtp_secure_value( $config['encryption'] );
            $phpmailer->SMTPAuth   = true;
            $phpmailer->Username   = $config['email'];
            $phpmailer->Password   = $config['password'];
            $phpmailer->SMTPDebug  = 0;

            $phpmailer->smtpConnect();
            $phpmailer->smtpClose();

            return array( 'success' => true );
        } catch ( Exception $e ) {
            return array(
                'success' => false,
                'error'   => $e->getMessage(),
            );
        }
    }

    /**
     * Convert encryption string to PHPMailer constant.
     */
    private static function get_smtp_secure_value( string $encryption ): string {
        switch ( $encryption ) {
            case 'ssl':
                return PHPMailer::ENCRYPTION_SMTPS;
            case 'tls':
                return PHPMailer::ENCRYPTION_STARTTLS;
            default:
                return '';
        }
    }
```

- [ ] **Step 2: Commit**

```bash
git add includes/class-alert404-smtp-handler.php
git commit -m "feat: add test_connection method to SMTP handler"
```

---

## Task 3: Create Modal HTML Template

**Files:**
- Create: `templates/smtp-wizard-modal.php`

- [ ] **Step 1: Create template file**

Create `templates/smtp-wizard-modal.php`:

```php
<?php
/**
 * SMTP Wizard Modal Template
 *
 * @package Alert404
 */

defined( 'ABSPATH' ) || exit;

$presets = Alert404_SMTP_Presets::get_presets();
$nonce   = wp_create_nonce( '404_alert_smtp_wizard_nonce' );
?>

<div id="404-alert-smtp-wizard-overlay" style="display: none;">
    <div id="404-alert-smtp-wizard-modal" class="404-alert-modal">
        <div class="404-alert-modal-header">
            <h2 id="wizard-title">Configuration SMTP</h2>
            <button type="button" id="404-alert-wizard-close" class="404-alert-modal-close">&times;</button>
        </div>

        <div class="404-alert-modal-body">
            <!-- Step 1: Choose Provider -->
            <div class="wizard-step" id="wizard-step-1" style="display: none;">
                <h3>Quel fournisseur SMTP utilisez-vous ?</h3>
                <p class="help-text">Sélectionnez votre fournisseur de messagerie. Si vous ne le trouvez pas, choisissez "Autre".</p>
                
                <div class="provider-grid">
                    <?php foreach ( $presets as $provider_id => $preset ) : ?>
                        <label class="provider-option">
                            <input type="radio" name="provider_id" value="<?php echo esc_attr( $provider_id ); ?>" />
                            <span><?php echo esc_html( $preset['name'] ); ?></span>
                        </label>
                    <?php endforeach; ?>
                    <label class="provider-option">
                        <input type="radio" name="provider_id" value="custom" />
                        <span>🔧 Autre (personnalisé)</span>
                    </label>
                </div>
            </div>

            <!-- Step 2.1: Email and Password (for preset) -->
            <div class="wizard-step" id="wizard-step-2-1" style="display: none;">
                <h3 id="step-2-1-title">Identifiants</h3>
                
                <div class="form-group">
                    <label for="wizard-email">Adresse email</label>
                    <input type="email" id="wizard-email" name="email" required />
                </div>

                <div class="form-group">
                    <label for="wizard-password">Mot de passe</label>
                    <input type="password" id="wizard-password" name="password" required />
                </div>

                <div id="preset-help" class="help-text"></div>
            </div>

            <!-- Step 2.2: Full SMTP Parameters (for custom) -->
            <div class="wizard-step" id="wizard-step-2-2" style="display: none;">
                <h3>Paramètres SMTP personnalisés</h3>
                <p class="help-text">Votre hébergeur ou fournisseur mail vous donne ces informations.</p>

                <div class="form-group">
                    <label for="wizard-host">Serveur SMTP</label>
                    <input type="text" id="wizard-host" name="host" placeholder="smtp.example.com" required />
                </div>

                <div class="form-group">
                    <label for="wizard-port">Port</label>
                    <input type="number" id="wizard-port" name="port" value="587" min="1" max="65535" required />
                </div>

                <div class="form-group">
                    <label for="wizard-encryption">Chiffrement</label>
                    <select id="wizard-encryption" name="encryption" required>
                        <option value="">Aucun</option>
                        <option value="tls">TLS</option>
                        <option value="ssl">SSL</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="wizard-custom-email">Adresse email</label>
                    <input type="email" id="wizard-custom-email" name="email" required />
                </div>

                <div class="form-group">
                    <label for="wizard-custom-password">Mot de passe</label>
                    <input type="password" id="wizard-custom-password" name="password" required />
                </div>
            </div>

            <!-- Step 3: Connection Test -->
            <div class="wizard-step" id="wizard-step-3" style="display: none;">
                <h3>Vérification de la configuration...</h3>
                
                <div id="test-result" style="display: none;">
                    <p id="test-message"></p>
                </div>

                <div id="test-spinner" class="spinner"></div>
            </div>

            <!-- Step 4: Summary -->
            <div class="wizard-step" id="wizard-step-4" style="display: none;">
                <h3>Configuration SMTP terminée !</h3>

                <table class="summary-table">
                    <tr>
                        <td><strong>Fournisseur</strong></td>
                        <td id="summary-provider">-</td>
                    </tr>
                    <tr>
                        <td><strong>Email</strong></td>
                        <td id="summary-email">-</td>
                    </tr>
                    <tr>
                        <td><strong>Serveur SMTP</strong></td>
                        <td id="summary-server">-</td>
                    </tr>
                </table>

                <p class="help-text">⚠️ Le mot de passe n'est pas affiché pour des raisons de sécurité.</p>
            </div>

            <!-- Error/Success Messages -->
            <div id="wizard-message" class="wizard-message" style="display: none;"></div>
        </div>

        <div class="404-alert-modal-footer">
            <button type="button" id="btn-back" class="button" style="display: none;">Retour</button>
            <button type="button" id="btn-test" class="button button-primary" style="display: none;">Test de connexion</button>
            <button type="button" id="btn-next" class="button button-primary" style="display: none;">Suivant</button>
            <button type="button" id="btn-finalize" class="button button-primary" style="display: none;">Terminer</button>
            <button type="button" id="btn-modify" class="button" style="display: none;">Modifier</button>
        </div>
    </div>
</div>

<input type="hidden" id="wizard-nonce" value="<?php echo esc_attr( $nonce ); ?>" />
<input type="hidden" id="wizard-ajax-url" value="<?php echo esc_attr( admin_url( 'admin-ajax.php' ) ); ?>" />

<script>
    window.alert404SMTPPresets = <?php echo wp_json_encode( $presets ); ?>;
</script>
```

- [ ] **Step 2: Commit**

```bash
git add templates/smtp-wizard-modal.php
git commit -m "feat: add SMTP wizard modal template with all steps"
```

---

## Task 4: Create Wizard JavaScript

**Files:**
- Create: `assets/js/alert404-smtp-wizard.js`

- [ ] **Step 1: Create wizard JS file**

Create `assets/js/alert404-smtp-wizard.js`:

```javascript
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
```

- [ ] **Step 2: Commit**

```bash
git add assets/js/alert404-smtp-wizard.js
git commit -m "feat: add SMTP wizard JavaScript with step navigation and AJAX"
```

---

## Task 5: Create Wizard CSS

**Files:**
- Create: `assets/css/alert404-smtp-wizard.css`

- [ ] **Step 1: Create wizard CSS file**

Create `assets/css/alert404-smtp-wizard.css`:

```css
/* Modal Overlay */
#404-alert-smtp-wizard-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
}

/* Modal Container */
.404-alert-modal {
    background: white;
    border-radius: 8px;
    box-shadow: 0 5px 40px rgba(0, 0, 0, 0.16);
    width: 90%;
    max-width: 500px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    max-height: 90vh;
}

.404-alert-modal-header {
    background: linear-gradient(135deg, #0073aa 0%, #005a87 100%);
    color: white;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.404-alert-modal-header h2 {
    margin: 0;
    font-size: 18px;
}

.404-alert-modal-close {
    background: none;
    border: none;
    color: white;
    font-size: 28px;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.404-alert-modal-close:hover {
    opacity: 0.8;
}

.404-alert-modal-body {
    padding: 30px;
    overflow-y: auto;
    flex: 1;
}

.404-alert-modal-footer {
    padding: 20px 30px;
    background: #f9f9f9;
    border-top: 1px solid #e0e0e0;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

/* Steps */
.wizard-step h3 {
    margin: 0 0 15px 0;
    font-size: 18px;
    color: #333;
}

.wizard-step .help-text {
    color: #666;
    font-size: 13px;
    margin-bottom: 20px;
}

/* Form Groups */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    box-sizing: border-box;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #0073aa;
    box-shadow: 0 0 0 3px rgba(0, 115, 170, 0.1);
}

/* Provider Grid */
.provider-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 10px;
}

.provider-option {
    display: flex;
    align-items: center;
    padding: 12px;
    border: 2px solid #ddd;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s;
}

.provider-option:hover {
    border-color: #0073aa;
    background: #f0f7ff;
}

.provider-option input[type="radio"] {
    margin-right: 8px;
}

.provider-option input[type="radio"]:checked {
    accent-color: #0073aa;
}

.provider-option span {
    font-size: 14px;
}

/* Summary Table */
.summary-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.summary-table td {
    padding: 10px;
    border-bottom: 1px solid #e0e0e0;
}

.summary-table td:first-child {
    width: 30%;
    color: #666;
}

/* Messages */
.wizard-message {
    padding: 12px;
    border-radius: 4px;
    margin-bottom: 15px;
    font-size: 14px;
}

.wizard-message.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.wizard-message.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

#test-message {
    padding: 15px;
    border-radius: 4px;
    text-align: center;
}

#test-message.success {
    background: #d4edda;
    color: #155724;
}

#test-message.error {
    background: #f8d7da;
    color: #721c24;
}

/* Spinner */
.spinner {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #0073aa;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin: 30px auto;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Buttons */
.button {
    padding: 10px 20px;
    border: 1px solid #ccc;
    border-radius: 4px;
    background: #f7f7f7;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s;
}

.button:hover {
    background: #e0e0e0;
}

.button-primary {
    background: #0073aa;
    color: white;
    border: none;
}

.button-primary:hover {
    background: #005a87;
}

/* Responsive */
@media (max-width: 600px) {
    .404-alert-modal {
        width: 95%;
        max-height: 95vh;
    }

    .provider-grid {
        grid-template-columns: 1fr;
    }

    .404-alert-modal-footer {
        flex-wrap: wrap;
    }

    .button {
        flex: 1;
        min-width: 100px;
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add assets/css/alert404-smtp-wizard.css
git commit -m "feat: add SMTP wizard CSS styling and animations"
```

---

## Task 6: Integrate Wizard into Settings

**Files:**
- Modify: `includes/class-alert404-settings.php`

- [ ] **Step 1: Add wizard enqueue to settings class**

In `class-alert404-settings.php`, find the `enqueue_admin_scripts()` method and add before the final closing brace:

```php
        // Enqueue wizard assets if on wizard page.
        if ( isset( $_GET['page'] ) && 'alert404_smtp_wizard' === $_GET['page'] ) {
            wp_enqueue_script(
                'alert404-smtp-wizard',
                ALERT404_DIR . 'assets/js/alert404-smtp-wizard.js',
                array( 'wp-util' ),
                ALERT404_VERSION,
                true
            );

            wp_enqueue_style(
                'alert404-smtp-wizard',
                ALERT404_DIR . 'assets/css/alert404-smtp-wizard.css',
                array(),
                ALERT404_VERSION
            );
        }
```

- [ ] **Step 2: Initialize wizard class in Alert404 plugin main file**

In `404-alert.php`, add after line 48 (after other requires):

```php
require_once ALERT404_DIR . 'includes/class-alert404-smtp-wizard.php';
```

- [ ] **Step 3: Commit**

```bash
git add includes/class-alert404-settings.php 404-alert.php
git commit -m "feat: integrate SMTP wizard into plugin initialization and settings"
```

---

## Task 7: Update Build Directory

**Files:**
- Modify: Build directory files (mirror of source)

- [ ] **Step 1: Sync build with source**

```bash
cp includes/class-alert404-smtp-wizard.php build/404-alert/includes/
cp assets/js/alert404-smtp-wizard.js build/404-alert/assets/js/
cp assets/css/alert404-smtp-wizard.css build/404-alert/assets/css/
cp templates/smtp-wizard-modal.php build/404-alert/templates/
cp includes/class-alert404-settings.php build/404-alert/includes/
cp includes/class-alert404-smtp-handler.php build/404-alert/includes/
cp 404-alert.php build/404-alert/
```

- [ ] **Step 2: Commit**

```bash
git add build/404-alert/
git commit -m "build: sync SMTP wizard changes to build directory"
```

---

## Task 8: Test Complete Wizard Flow

**Files:**
- Test: Manual testing on WordPress admin

- [ ] **Step 1: Test Step 1 (provider selection)**

1. Navigate to 404 Alert → Configuration SMTP in WordPress admin
2. Verify wizard modal opens
3. Select "Gmail" → verify provider options are selectable
4. Select "Autre" → verify custom path works

- [ ] **Step 2: Test Step 2.1 (preset email/password)**

1. Select "Gmail" preset
2. Enter email and password
3. Click "Test de connexion"
4. Verify spinner appears and test executes

- [ ] **Step 3: Test Step 2.2 (custom parameters)**

1. Go back and select "Autre"
2. Fill in all custom SMTP parameters (host, port, encryption, email, password)
3. Click "Test de connexion"
4. Verify test runs

- [ ] **Step 4: Test Step 3 (connection test)**

1. Verify success message displays if credentials work
2. Verify error message displays if credentials fail
3. On success: verify "Suivant" appears and leads to Step 4
4. On failure: verify "Retour" appears and returns to Step 2

- [ ] **Step 5: Test Step 4 (summary)**

1. Verify summary displays correct provider, email, server
2. Verify password is NOT displayed
3. Click "Terminer" → verify modal closes
4. Verify SMTP settings are saved in options

- [ ] **Step 6: Test state persistence**

1. Open wizard and progress to Step 2
2. Close browser/modal without completing
3. Reopen wizard → verify it returns to Step 2 with data intact
4. Complete wizard

- [ ] **Step 7: Test "Modifier" button**

1. After completing wizard, open again
2. Click "Modifier" → verify returns to Step 1
3. Change provider and complete again

- [ ] **Step 8: Commit test results**

```bash
git commit -m "test: verify complete wizard flow and state persistence"
```

---

## Checklist de vérification

Before closing:

- [ ] All 4 wizard steps display and navigate correctly
- [ ] Provider selection branches to 2.1 (preset) or 2.2 (custom)
- [ ] Email/password and custom SMTP parameters are captured
- [ ] Connection test works and shows success/error messages
- [ ] Summary displays correct information (no password shown)
- [ ] Settings are saved to WordPress options after completion
- [ ] State persists in transient if wizard is closed mid-way
- [ ] All AJAX endpoints are protected with nonces
- [ ] JavaScript validation prevents incomplete form submission
- [ ] CSS is responsive and accessible
- [ ] Tests pass (unit tests for wizard class)
- [ ] Build directory is synchronized with source
