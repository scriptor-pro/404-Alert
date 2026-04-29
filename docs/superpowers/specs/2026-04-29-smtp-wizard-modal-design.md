# Wizard SMTP Modal pour Débutants - Design Spec

**Date:** 2026-04-29  
**Objectif:** Créer un assistant interactif (modal) qui guide les utilisateurs débutants à travers la configuration SMTP en 4 étapes, avec branching conditionnel et persistance d'état.

---

## Vue d'ensemble

Un wizard modal WordPress standard qui :
1. S'ouvre depuis un menu item "Configuration SMTP" dans le menu 404-Alert
2. Progresse à travers 4 étapes avec état persisté en transient WordPress
3. Affiche le contenu de chaque étape dynamiquement selon les réponses
4. Sauvegarde les données partielles après chaque étape (pour reprendre si fermeture)
5. À la fin, valide et enregistre les paramètres SMTP définitifs

## Architecture

### Stockage d'État

**Option WordPress transient :** `404_alert_smtp_wizard_state` (expire 24h)

Structure :
```php
[
    'step' => 1,  // Étape courante (1, 2, 3, 4)
    'provider_id' => 'gmail',  // ID du preset ou null si custom
    'email' => 'user@example.com',  // Adresse utilisateur (étape 2)
    'password' => '***encrypted***',  // Chiffré en base64
    'host' => 'smtp.gmail.com',  // Auto-rempli si preset, sinon manuel
    'port' => 587,  // Auto-rempli si preset, sinon manuel
    'encryption' => 'tls',  // Auto-rempli si preset, sinon manuel
    'test_result' => null  // 'success', 'error', ou null
]
```

### Flux de Navigation

```
Étape 1 (Choix fournisseur)
├─ Preset sélectionné → Étape 2.1 (Email + Mot de passe)
└─ "Autre" sélectionné → Étape 2.2 (Tous les paramètres)

Étape 2.1 ou 2.2
└─ Validé → Étape 3 (Test connexion)

Étape 3 (Test connexion)
├─ Succès (✅) → Étape 4 (Résumé)
└─ Erreur (❌) → Retour à 2.1 ou 2.2

Étape 4 (Résumé)
├─ "Modifier" → Retour à Étape 1
└─ "Terminer" → Enregistrement définitif + Fermeture
```

---

## Interface Détaillée

### Étape 1 : Choix du fournisseur

**Titre :** "Quel fournisseur SMTP utilisez-vous ?"

**Contenu :**
- Combo-box ou boutons radio avec options :
  - Gmail
  - Yahoo
  - ProtonMail
  - Brevo
  - Mailtrap
  - SendGrid
  - Mailgun
  - Autre (custom)
- Petit texte d'aide : "Sélectionnez votre fournisseur de messagerie. Si vous ne le trouvez pas, choisissez 'Autre'."

**Comportement :**
- Sélection d'un preset → Stocke `provider_id` en transient, charge l'étape 2.1
- Sélection "Autre" → Stocke `provider_id = null`, charge l'étape 2.2
- Bouton "Suivant" (actif seulement après sélection)

---

### Étape 2.1 : Identifiants pour Preset

**Titre :** "Identifiants pour [Nom du preset]"

**Contenu :**
- Champ Email / Adresse email (type=email, requis)
- Champ Mot de passe (type=password, masqué, requis)
- Aide contextuelle selon preset :
  - Gmail : "Pour Gmail, utilisez un [mot de passe d'application](https://support.google.com/accounts/answer/185833) si vous avez la 2FA activée."
  - Yahoo : "Utilisez un [mot de passe d'application](https://login.yahoo.com) généré dans les paramètres de sécurité."
  - Autres : Messages génériques
- Petit texte : "Vos identifiants ne seront jamais stockés en clair."

**Comportement :**
- Validation : Tous les champs remplis et email valide
- Bouton "Retour" → Revient à Étape 1, conserve `provider_id`
- Bouton "Test de connexion" → Validé + Avance à Étape 3

---

### Étape 2.2 : Paramètres SMTP Personnalisés

**Titre :** "Paramètres SMTP personnalisés"

**Contenu :**
- Champ Serveur SMTP / Hôte (type=text, requis)
- Champ Port (type=number, défaut=587, requis)
- Select Chiffrement (options: "Aucun", "TLS", "SSL", défaut=TLS)
- Champ Email / Adresse de l'expéditeur (type=email, requis)
- Champ Mot de passe (type=password, requis)
- Petit texte : "Votre hébergeur ou fournisseur mail vous donne ces informations dans les paramètres de compte."

**Comportement :**
- Validation : Tous les champs remplis, port est un nombre valide, email valide
- Bouton "Retour" → Revient à Étape 1, conserve les paramètres saisis
- Bouton "Test de connexion" → Validé + Avance à Étape 3

---

### Étape 3 : Test de Connexion

**Titre :** "Vérification de la configuration..."

**Contenu (en attente) :**
- Spinner animé
- Message : "Connexion en cours, veuillez patienter..."

**Contenu (après test) :**

**Cas Succès (✅) :**
```
✅ Connexion réussie !
Les paramètres SMTP sont corrects et fonctionnels.
```
- Bouton "Suivant" → Avance à Étape 4

**Cas Erreur (❌) :**
```
❌ Erreur de connexion

Détail : [Message d'erreur SMTP, ex: "Authentification échouée" ou "Serveur non joignable"]

Suggestions :
- Vérifiez vos identifiants (email et mot de passe)
- Vérifiez que le serveur SMTP et le port sont corrects
- Vérifiez votre connexion internet
- Consultez la documentation de votre fournisseur
```
- Bouton "Retour" → Revient à Étape 2.1 ou 2.2 selon preset/custom
- Les paramètres saisis sont conservés

**Backend :**
- Appel AJAX `wp_ajax_404_alert_smtp_test_connection`
- Utilise `Alert404_SMTP_Handler::test_connection()` pour vérifier la connexion réelle
- Retourne JSON : `{ success: true/false, message: '...' }`

---

### Étape 4 : Résumé et Confirmation

**Titre :** "Configuration SMTP terminée !"

**Contenu :**
- Tableau de synthèse :
  ```
  Fournisseur : Gmail
  Email : user@example.com
  Serveur : smtp.gmail.com:587 (TLS)
  ```
  - Si custom : Affiche également Port et Chiffrement
  - ⚠️ **Le mot de passe n'est jamais affiché**
- Petit texte : "Ces paramètres sont maintenant sauvegardés. Vous pouvez les modifier à tout moment en relançant cet assistant."

**Comportement :**
- Bouton "Modifier" → Revient à Étape 1, conserve tous les paramètres
- Bouton "Terminer" → Enregistre définitivement, vide le transient, ferme le modal
- Sur "Terminer", affiche brièvement "Sauvegarde en cours..." puis ferme

---

## Composants à Créer/Modifier

### Fichiers à créer

1. **`includes/class-alert404-smtp-wizard.php`**
   - Classe `Alert404_SMTP_Wizard`
   - Méthodes :
     - `init()` — Enregistre les hooks admin et AJAX
     - `add_menu_page()` — Ajoute le menu item dans 404-Alert
     - `render_wizard_page()` — Affiche le conteneur modal vide (JS prend le relais)
     - `ajax_get_current_step()` — Retourne l'étape courante + données du transient
     - `ajax_save_step()` — Sauvegarde une étape en transient
     - `ajax_test_connection()` — Teste la connexion SMTP
     - `ajax_finalize()` — Enregistre définitivement et vide le transient
     - `get_wizard_state()` — Récupère l'état du transient
     - `clear_wizard_state()` — Vide le transient

2. **`assets/js/alert404-smtp-wizard.js`**
   - Gestion du modal jQuery
   - Navigation entre étapes
   - Gestion des appels AJAX
   - Sauvegarde locale après chaque étape
   - Affichage dynamique des branches (2.1 vs 2.2)
   - Spinner et messages d'erreur
   - Validation front-end des champs

3. **`assets/css/alert404-smtp-wizard.css`**
   - Styling du modal (responsive, conforme design WordPress)
   - Animations (transitions d'étapes, spinner)
   - Mise en forme des formulaires
   - Styling des messages de succès/erreur

4. **`templates/smtp-wizard-modal.php`**
   - HTML du modal avec toutes les étapes (imbriquées, cachées par défaut)
   - Champs de formulaire selon étape
   - Messages d'aide/erreur
   - Boutons de navigation

### Fichiers à modifier

1. **`includes/class-alert404-settings.php`**
   - Dans `init()` : Enqueuer les scripts/styles du wizard
   - Ajouter une action dans le menu admin pour ouvrir le wizard

2. **`includes/class-alert404-smtp-handler.php`**
   - Ajouter méthode `test_connection( $host, $port, $encryption, $email, $password )` :
     - Utilise PHPMailer ou library SMTP native
     - Retourne `[ 'success' => true/false, 'message' => '...' ]`

---

## Sécurité

- **Mot de passe en transient** : Stocké en base64, jamais en clair
- **Nonce AJAX** : Toutes les requêtes AJAX sont protégées par nonce WordPress
- **Sanitization** : Tous les paramètres sont validés/échappés côté serveur
- **Enregistrement final** : Utilise `update_option()` avec `sanitize_text_field()` / `absint()`
- **Aucun affichage du mot de passe** : Jamais affiché en étape 4

---

## Comportement Avancé

### Reprendre si Fermeture
- Au chargement du wizard, AJAX récupère l'état du transient
- Si transient existe → Affiche le modal à l'étape sauvegardée
- Si transient n'existe pas → Démarre à l'étape 1

### Expiration
- Transient expire après 24h d'inactivité
- À l'expiration, wizard réinitialise à l'étape 1

### Modification Ultérieure
- Bouton "Modifier" (étape 4) → Retour à étape 1
- Accès futur via menu "Configuration SMTP" → Si paramètres existants, affiche option "Modifier la configuration actuelle" ou "Nouvelle configuration"

---

## Tests et Validation

- ✅ Flux complet : Étape 1 → 2 → 3 → 4
- ✅ Branching 2.1 vs 2.2 selon preset vs custom
- ✅ Erreur au test (étape 3) → Retour à 2.1/2.2
- ✅ Persistance sur fermeture/réouverture
- ✅ Expiration transient après 24h
- ✅ Sécurité : Mot de passe jamais affiché
- ✅ Validation : Champs obligatoires, formats email/numérique
- ✅ AJAX protégé par nonce

---

## Notes

- Wizard coexiste avec la page de paramètres SMTP classique (accordéon) : les deux lisent/écrivent la même option `404_alert_options['smtp_*']`
- Futur : Pourrais ajouter "Mode Avancé" si besoin d'accéder à l'accordéon directement
- Pas de limite de relances du wizard : utilisateur peut le réexécuter autant de fois
