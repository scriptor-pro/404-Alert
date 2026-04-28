# Vérification de Connexion SMTP — 404 Alert

## Aperçu

La fonctionnalité de test de connexion SMTP permet aux administrateurs de vérifier instantanément la validité de leur configuration SMTP **avant de sauvegarder** les paramètres.

## Architecture

### Backend Components

#### 1. AJAX Handler (`class-alert404-settings.php:59`)

**Méthode:** `Alert404_Settings::handle_test_smtp()`

```php
// Vérifications de sécurité
- check_ajax_referer() → Protection CSRF
- current_user_can('manage_options') → Vérification permissions
- Alert404_SMTP_Handler::test_connection() → Teste la connexion
```

**Réponse:**
- Succès: `wp_send_json_success(['message' => '...'])`
- Erreur: `wp_send_json_error(['message' => '...'])`

#### 2. SMTP Connection Test (`class-alert404-smtp-handler.php:228`)

**Méthode:** `Alert404_SMTP_Handler::test_connection()`

**Étapes:**
1. Valider configuration (host, username, password non vides)
2. Charger PHPMailer depuis WordPress core
3. Configurer les paramètres SMTP
4. Appeler `$phpmailer->smtpConnect()`
5. Fermer avec `$phpmailer->smtpClose()`
6. Retourner `['success' => true/false, 'message' => '...']`

**Paramètres utilisés:**
- Host: serveur SMTP
- Port: numéro port
- Username: identifiant
- Password: mot de passe (déchiffré)
- Encryption: TLS/SSL/none
- Timeout: 30 secondes

**Exception Handling:**
```php
try {
    $phpmailer->smtpConnect();
    $phpmailer->smtpClose();
    return ['success' => true, 'message' => 'Connexion SMTP réussie! ✓'];
} catch (Exception $e) {
    return ['success' => false, 'message' => 'Erreur de connexion SMTP: ' . $e->getMessage()];
}
```

### Frontend Components

#### 3. JavaScript Handler (`assets/js/alert404-admin.js`)

**Déclencheur:** Clic sur bouton `#404-alert-smtp-test`

**Actions:**
1. Désactiver le bouton et afficher "Test en cours..."
2. Envoyer requête AJAX POST
3. Afficher résultat (vert si succès, rouge si erreur)
4. Réactiver le bouton

```javascript
$.ajax({
    type: 'POST',
    url: alert404AdminVars.ajaxurl,
    data: {
        action: '404_alert_test_smtp',
        nonce: alert404AdminVars.nonce
    },
    success: function(response) {
        if (response.success) {
            // Afficher en VERT ✓
        } else {
            // Afficher en ROUGE ✗
        }
    }
});
```

#### 4. Button Rendering (`class-alert404-settings.php:660`)

**Méthode:** `Alert404_Settings::render_field_smtp_test()`

```html
<button type="button" class="button" id="404-alert-smtp-test">
    Tester la connexion
</button>
<div id="404-alert-smtp-test-result" style="margin-top: 10px; display: none;"></div>
```

#### 5. Script Enqueuing (`class-alert404-settings.php:33`)

**Méthode:** `Alert404_Settings::enqueue_admin_scripts()`

```php
- Condition: Seulement sur Settings > 404 Alert
- Version: ALERT404_VERSION (dynamique)
- Dépendance: jQuery
- Localisation: alert404AdminVars.ajaxurl, alert404AdminVars.nonce
```

## Flux Complet

```
1. Admin clique bouton "Tester la connexion"
   ↓
2. JavaScript désactive le bouton
   ↓
3. jQuery envoie AJAX POST avec nonce
   ↓
4. WordPress route vers handle_test_smtp()
   ↓
5. Vérifications de sécurité (nonce, permissions)
   ↓
6. Appel test_connection()
   ↓
7. PHPMailer teste la connexion SMTP
   ↓
8. Résultat retourné en JSON
   ↓
9. JavaScript affiche le message
   ↓
10. Bouton réactivé
```

## Cas d'Usage

### ✓ Configuration Valide

**Input:**
- Host: `smtp.gmail.com`
- Port: `587`
- Username: `user@gmail.com`
- Password: `app-password`
- Encryption: `tls`

**Output:**
```
✓ Connexion SMTP réussie! ✓
```

### ✗ Credentials Invalides

**Input:**
- Host: `smtp.gmail.com`
- Port: `587`
- Username: `user@gmail.com`
- Password: `wrong-password`
- Encryption: `tls`

**Output:**
```
✗ Erreur de connexion SMTP: Unable to authenticate.
```

### ✗ Configuration Incomplète

**Input:**
- Host: (vide)
- Username: (vide)
- Password: (vide)

**Output:**
```
✗ Configuration SMTP incomplète. Veuillez remplir tous les champs.
```

## Sécurité

### ✓ Protections Implémentées

| Protection | Implémentation |
|-----------|-----------------|
| **CSRF** | WordPress nonce obligatoire |
| **Permissions** | Vérification `manage_options` |
| **Exception Handling** | Toutes erreurs capturées |
| **Timeout** | 30 secondes (évite hang) |
| **Configuration** | Validation des champs requis |
| **Message** | Pas d'injection XSS possible |

### ⚠️ Notes de Sécurité

- Les credentials ne sont **jamais** stockés lors du test
- Le test utilise **uniquement** la configuration actuelle
- Les messages d'erreur révèlent les détails techniques (intentionnel pour admin)
- Le test ne valide **pas** les règles SPF/DKIM

## Dépannage

### Le bouton "Tester la connexion" ne réagit pas

1. Vérifier que jQuery est chargé
2. Vérifier la console navigateur pour les erreurs JS
3. Vérifier que `alert404AdminVars` est présent

### "Configuration SMTP incomplète"

1. Vérifier que Host, Username, Password sont remplis
2. Vérifier qu'aucun champ n'est vide

### "Erreur de connexion SMTP"

1. Vérifier la validité de Host:Port
2. Vérifier Username et Password
3. Vérifier le type de chiffrement (TLS/SSL/none)
4. Vérifier les règles firewall/pare-feu
5. Vérifier les logs WordPress pour plus de détails

## Fichiers Impliqués

```
404-alert.php
├── Charge Alert404_SMTP_Handler
└── Initialise Alert404_Settings

includes/class-alert404-settings.php
├── handle_test_smtp() [AJAX handler]
├── enqueue_admin_scripts() [Script enqueuing]
└── render_field_smtp_test() [Button rendering]

includes/class-alert404-smtp-handler.php
└── test_connection() [Core logic]

assets/js/alert404-admin.js
└── Événement clic et requête AJAX
```

## Statut

✅ **Opérationnel et Sécurisé**

- Syntaxe PHP valide
- Dépendances WordPress respectées
- Sécurité CSRF implémentée
- Permissions vérifiées
- Exceptions gérées
- Tests couverts
