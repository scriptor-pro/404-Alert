# SMTP Configuration Redesign - Spécification

**Date:** 2026-04-29  
**Objectif:** Repenser complètement le processus de configuration SMTP pour garantir que tous les paramètres des fournisseurs identifiés sont fournis par le plugin et correctement sauvegardés.

## Problème actuel

- Les paramètres des fournisseurs SMTP (host/port/encryption) ne sont pas enregistrés en base de données
- Les champs `disabled` du mode preset n'envoient pas leurs données au serveur lors de la soumission
- L'architecture actuelle (deux colonnes) crée une confusion sur les données qui sont effectivement sauvegardées
- Impossible de déboguer quel fournisseur a été utilisé pour une configuration donnée

## Solution : Mode Hybride Adaptif avec Accordions

### Architecture générale

Un formulaire unique SMTP avec deux accordions indépendants et une zone commune de paramètres expéditeur.

```
┌──────────────────────────────────────────────────────┐
│  Configuration SMTP                                  │
├──────────────────────────────────────────────────────┤
│                                                      │
│  📌 Accordion 1: Fournisseur connu [➖]              │
│  ├─ Dropdown: Sélectionner un fournisseur            │
│  ├─ Email/Username: [____________]                   │
│  ├─ Mot de passe: [____________]                     │
│  ├─ Aperçu: Serveur/Port/Chiffrement (lecture seule)│
│  └─ Conseil du fournisseur (optionnel)               │
│                                                      │
│  📌 Accordion 2: Configuration personnalisée [➖]    │
│  ├─ Serveur SMTP: [____________]                     │
│  ├─ Port: [____________]                             │
│  ├─ Chiffrement: [Dropdown]                          │
│  ├─ Email/Username: [____________]                   │
│  ├─ Mot de passe: [____________]                     │
│  └─ Aperçu: tous les paramètres saisis              │
│                                                      │
│  ─────────────────────────────────────────────────   │
│  Paramètres communs (toujours visibles)              │
│  ├─ Adresse expéditeur: [____________]               │
│  ├─ Nom expéditeur: [____________]                   │
│  ├─ [Tester la connexion]                            │
│  └─ Résumé pré-sauvegarde                            │
│                                                      │
│  [Enregistrer les paramètres]                        │
└──────────────────────────────────────────────────────┘
```

### 1. Accordion 1 : Fournisseur connu

**Comportement :**
- Fermé par défaut lors du chargement initial
- Contient un dropdown avec tous les presets (Gmail, Outlook, Yahoo, etc.) + custom sauvegardés
- Quand un preset est sélectionné :
  - Les paramètres du serveur (host, port, encryption) sont automatiquement remplis
  - Affichés en lecture seule ou désactivés visuellement
  - L'utilisateur saisit uniquement : email/username et password
  - Un aperçu affiche : "Gmail • smtp.gmail.com:587 • TLS • user@gmail.com"

**Champs :**
- `preset_id` (select) : sélectionneur de fournisseur
- `preset_username` (text) : email ou identifiant du fournisseur
- `preset_password` (password) : mot de passe
- `preset_host` (hidden) : serveur rempli par le preset
- `preset_port` (hidden) : port rempli par le preset
- `preset_encryption` (hidden) : chiffrement rempli par le preset

**Hidden inputs :** 
- Les trois champs hidden (host/port/encryption) recevront les valeurs du preset via JavaScript
- Ils garantissent que les paramètres du fournisseur sont envoyés au serveur lors de la soumission
- Présents dans le formulaire à tout moment, invisibles à l'utilisateur

**Validation :**
- Accordion 1 est valide si : preset_id !== "" ET preset_username !== "" ET preset_password !== ""

**Conseil du fournisseur :**
- Affichage optionnel d'un message d'aide spécifique au fournisseur sélectionné (ex: "Gmail : utilisez un mot de passe d'application")

### 2. Accordion 2 : Configuration personnalisée

**Comportement :**
- Fermé par défaut lors du chargement initial
- Tous les champs sont obligatoires et doivent être remplis complètement
- Un aperçu affiche les paramètres saisis en temps réel
- Aucun champ n'est pré-rempli

**Champs :**
- `custom_host` (text) : serveur SMTP
- `custom_port` (number) : port SMTP
- `custom_encryption` (select) : type de chiffrement (TLS, SSL, Aucun)
- `custom_username` (text) : identifiant SMTP
- `custom_password` (password) : mot de passe

**Validation :**
- Accordion 2 est valide si : tous les 5 champs sont remplis

**Message helper :**
- "Complétez tous les champs pour enregistrer une configuration personnalisée"

### 3. Zone commune : Paramètres expéditeur

**Toujours visible en bas**, indépendante des accordions :
- `from_email` (email) : adresse expéditeur (défaut = admin_email)
- `from_name` (text) : nom expéditeur (défaut = blog name)
- Bouton "Tester la connexion"
- Résumé pré-sauvegarde (tableau montrant tous les paramètres prêts à être enregistrés)

### 4. Logique JavaScript

**État global préservé :**
```javascript
state = {
  preset: {
    selected: "gmail",        // preset_id sélectionné
    username: "user@gmail.com",
    password: "***"
  },
  custom: {
    host: "",
    port: "",
    encryption: "tls",
    username: "",
    password: ""
  },
  common: {
    fromEmail: "noreply@example.com",
    fromName: "Mon Site"
  }
}
```

**Événements et comportements :**

1. **Changement de preset (dropdown)**
   - Récupérer les paramètres du preset depuis `a404Presets[key]`
   - Remplir les hidden inputs : `preset_host`, `preset_port`, `preset_encryption`
   - Mettre à jour l'aperçu du preset
   - Préserver les valeurs `preset_username` et `preset_password` si déjà saisies

2. **Changement dans un champ custom**
   - Mettre à jour l'état custom correspondant
   - Mettre à jour l'aperçu du custom
   - Préserver les données (ne jamais vider)

3. **Ouverture d'accordion**
   - Si Accordion 1 s'ouvre : charger et afficher les données du mode preset mémorisées
   - Si Accordion 2 s'ouvre : charger et afficher les données du mode custom mémorisées
   - L'autre accordion ne change pas (fermeture optionnelle)

4. **Mise à jour du résumé pré-sauvegarde**
   - En temps réel lors de chaque changement
   - Affiche les paramètres qui seront enregistrés selon le mode actif

### 5. Validation à la soumission

Avant d'envoyer le formulaire au serveur, vérifier :

```
SI (Accordion 1 rempli ET valide) OU (Accordion 2 rempli ET valide)
  ALORS soumettre le formulaire
SINON
  Afficher erreur : "Complétez soit un fournisseur connu, soit une configuration personnalisée"
  Bloquer la soumission
```

### 6. Sauvegarde en base de données

**PHP : `sanitize_smtp_options()`**

La fonction détecte quel mode a été utilisé et sauvegarde les données appropriées.

**Mode Preset (Accordion 1 utilisé) :**
- Récupérer `preset_id` depuis le formulaire
- Valider que `preset_id` existe dans `Alert404_SMTP_Presets::get_presets()`
- Récupérer le preset complet via `Alert404_SMTP_Presets::get_preset($preset_id)`
- Extraire : host, port, encryption du preset
- Récupérer du formulaire : username, password
- Enregistrer en base :
  ```php
  [
    'provider_id'  => $preset_id,           // ex: 'gmail'
    'host'         => $preset['host'],      // ex: 'smtp.gmail.com'
    'port'         => $preset['port'],      // ex: 587
    'encryption'   => $preset['encryption'],// ex: 'tls'
    'username'     => $form_username,
    'password'     => $encrypted_password,
    'from_email'   => $form_from_email,
    'from_name'    => $form_from_name
  ]
  ```

**Mode Custom (Accordion 2 utilisé) :**
- Récupérer tous les champs custom du formulaire
- Valider que tous les champs sont remplis
- Enregistrer en base :
  ```php
  [
    'provider_id'  => 'custom',
    'host'         => $form_host,
    'port'         => $form_port,
    'encryption'   => $form_encryption,
    'username'     => $form_username,
    'password'     => $encrypted_password,
    'from_email'   => $form_from_email,
    'from_name'    => $form_from_name
  ]
  ```

**Validation :**
- Au moins un mode doit être valide et rempli
- Si les deux accordions sont vides → erreur de validation
- Si un mode est partiellement rempli → erreur spécifique au mode

### 7. Rechargement et édition ultérieure

**Au chargement de la page :**
1. Récupérer `404_alert_smtp_options` depuis la base
2. Lire le `provider_id` enregistré
3. **Si `provider_id` est un preset connu (ex: "gmail") :**
   - Ouvrir Accordion 1
   - Pré-sélectionner le preset dans le dropdown
   - Remplir `preset_username` et `preset_password` avec les valeurs enregistrées
   - Remplir les hidden inputs avec les paramètres du preset
   - Fermer Accordion 2
4. **Si `provider_id` === "custom" :**
   - Ouvrir Accordion 2
   - Remplir tous les 5 champs custom avec les valeurs enregistrées
   - Fermer Accordion 1
5. Remplir les paramètres communs : `from_email` et `from_name`

**Édition :**
- L'utilisateur peut ouvrir l'accordion qui n'est pas actuellement actif (basculer de mode)
- Les données de chaque mode sont préservées
- En cliquant sur un preset différent, les données du nouvel preset remplacent l'ancien (mais on peut revenir)
- En modifiant un champ du mode custom, on reste en mode custom

### 8. Préservation intelligente des données

**Règle clé :** Chaque mode (preset et custom) a sa propre mémoire.

- Basculer entre les accordions ne perd jamais de données
- Si je sélectionne Gmail, remplis email/password, puis bascule vers Custom et remplit tout
- Si je reviens à Accordion 1 → Gmail est toujours sélectionné avec mes identifiants
- Si je reviens à Accordion 2 → mes données custom sont toujours là

**Exception :** Changer de preset dans le dropdown remplace les paramètres du serveur du preset précédent (logique).

### 9. Cas d'usage spécifiques

**Scénario A : Nouvel utilisateur avec preset**
1. Ouvre les paramètres SMTP
2. Sélectionne "Gmail" dans le dropdown
3. Remplit email et password
4. Clique "Enregistrer"
→ Tous les paramètres de Gmail (host/port/encryption) + email/password sont enregistrés

**Scénario B : Nouvel utilisateur avec serveur custom**
1. Ouvre les paramètres SMTP
2. Ouvre Accordion 2
3. Remplit host, port, encryption, email/username, password
4. Clique "Enregistrer"
→ Tous les paramètres custom sont enregistrés

**Scénario C : Migration preset → custom**
1. Utilisateur avait Gmail enregistré
2. Ouvre les paramètres
3. Ouvre Accordion 2 (Custom)
4. Remplit une configuration personnalisée différente
5. Clique "Enregistrer"
→ Mode custom remplace le preset, `provider_id` devient "custom"

**Scénario D : Changement d'identifiants sans toucher au serveur**
1. Utilisateur avait Gmail enregistré
2. Ouvre les paramètres
3. Modifie juste le password du preset Gmail
4. Clique "Enregistrer"
→ Les paramètres du serveur (du preset) restent, juste le password change

## Avantages de cette approche

✅ **Tous les paramètres du fournisseur sont sauvegardés**
- Les hidden inputs garantissent l'envoi au serveur
- `sanitize_smtp_options()` les récupère via le preset

✅ **Traçabilité**
- `provider_id` enregistré permet de savoir quel fournisseur/mode a été utilisé

✅ **Débogage facilité**
- Support peut voir : "Ah, c'était Gmail" et donner des conseils ciblés

✅ **UX claire et prévisible**
- Deux modes distincts, pas de confusion
- Accordions gardent les données
- Validation claire

✅ **Flexible**
- Preset avec pré-remplissage automatique pour les utilisateurs lambda
- Custom pour ceux avec serveurs spécifiques

## Fichiers à modifier/créer

1. **`includes/class-alert404-settings.php`**
   - Refonte complète de `render_smtp_two_column_form()`
   - Créer nouvelle structure accordion
   - Ajouter hidden inputs pour le preset

2. **`assets/js/alert404-smtp-config.js`**
   - Refonte complète de la logique JavaScript
   - Gérer l'état des deux accordions indépendamment
   - Préservation intelligente des données

3. **`includes/class-alert404-settings.php`** (fonction `sanitize_smtp_options()`)
   - Détecter quel mode a été utilisé (preset vs custom)
   - Récupérer les paramètres appropriés
   - Enregistrer `provider_id` + tous les paramètres

## Tests nécessaires

- Enregistrer un preset → vérifier que host/port/encryption/username/password sont en base
- Enregistrer une config custom → vérifier que tous les 5 champs sont en base
- Recharger la page → vérifier que l'accordion correct s'ouvre avec les bonnes données
- Basculer entre accordions → vérifier que les données sont préservées
- Tester la connexion SMTP → vérifier que ça utilise les bons paramètres

