# Progression en temps réel du test SMTP - Design Spec

**Date:** 2026-04-28  
**Objectif:** Afficher la progression détaillée du test de connexion SMTP en temps réel avec feedback visuel et diagnostic précis

## Vue d'ensemble

Implémenter un système de progression bidirectionnel pour le test SMTP qui :
- Affiche les étapes du test au fur et à mesure de leur exécution
- Fournit une barre de progression visuelle
- Aide au diagnostic des problèmes de connexion
- Rassure l'utilisateur pendant l'attente

## Architecture

### 1. Backend (PHP)

#### Système de gestion des étapes

Créer une nouvelle classe `Alert404_Test_Progress` qui gère le stockage temporaire des étapes :

- Stockage via WordPress transients (clé unique par session/utilisateur)
- Structure : `[ { step: string, status: 'pending'|'running'|'success'|'error', message: string, timestamp: float } ]`
- TTL : 5 minutes (expiration automatique)
- Méthodes :
  - `init_test()` : Initialise la liste d'étapes vide
  - `update_step(string $step, string $status, string $message)` : Met à jour une étape
  - `get_progress()` : Retourne l'état actuel pour le polling
  - `clear_test()` : Nettoie après le test

#### Modification du test SMTP

Refactoriser `Alert404_SMTP_Handler::test_connection()` pour :
- Appeler `Alert404_Test_Progress::init_test()` au début
- Découper le test en étapes distinctes :
  1. "Vérification de la configuration" → validation config SMTP
  2. "Connexion au serveur" → établissement de la connexion TCP/SMTP
  3. "Authentification" → login avec username/password
  4. "Configuration de l'email" → préparation du message
  5. "Envoi de l'email de test" → envoi effectif
  6. "Fermeture de la connexion" → déconnexion propre
- Appeler `Alert404_Test_Progress::update_step()` pour chaque transition
- En cas d'erreur, capturer le message détaillé et marquer l'étape comme 'error'

#### Nouveaux endpoints AJAX

Ajouter dans `Alert404_Settings::init()` :
- Action `wp_ajax_404_alert_get_test_progress` → `Alert404_Settings::handle_get_progress()`
  - Récupère l'état actuel sans relancer le test
  - Requête légère, fréquente (polling)

### 2. Frontend (JavaScript)

#### Fichier : `assets/js/alert404-admin.js`

Ajouter un gestionnaire de test SMTP progressif :

1. **Au clic du bouton test** :
   - Désactiver le bouton
   - Initialiser et afficher la zone de progression
   - Lancer le test via AJAX
   - Démarrer le polling des étapes

2. **Système de polling** :
   - Appel `wp.ajax.send('404_alert_get_test_progress')` toutes les 500ms
   - Mettre à jour l'UI avec les nouvelles étapes
   - Arrêter quand status final détecté (last step = 'success' ou 'error')

3. **Gestion de l'interface** :
   - Afficher/masquer la zone de progression au besoin
   - Mise à jour classe CSS pour chaque étape (pending → running → success/error)
   - Calcul % progression : (nombre étapes complétées / nombre étapes total) × 100

### 3. Frontend (HTML/CSS)

#### Zone de progression

Ajouter dans le formulaire SMTP settings (sous le bouton test) :

```html
<div id="404-alert-test-progress" style="display: none; margin-top: 20px;">
  <div class="alert404-progress-bar-container">
    <div class="alert404-progress-bar" style="width: 0%"></div>
  </div>
  <div class="alert404-steps-list">
    <!-- Généré dynamiquement par JS -->
  </div>
</div>
```

#### Styling

Ajouter à `assets/css/alert404-admin.css` :

- `.alert404-progress-bar-container` : conteneur gris clair (height: 6px)
- `.alert404-progress-bar` : barre bleue avec transition smooth (0.3s)
- `.alert404-steps-list` : liste avec spacing
- `.alert404-step` : ligne pour chaque étape
  - `.alert404-step-icon` : icône/spinner (pending=⏳, running=⌛, success=✓, error=✗)
  - `.alert404-step-text` : label + message détaillé
  - `.alert404-step.pending` : gris
  - `.alert404-step.running` : bleu + animation spinner
  - `.alert404-step.success` : vert
  - `.alert404-step.error` : rouge

## Étapes du test SMTP

| # | Étape | Validation | Message succès | Message erreur |
|---|-------|-----------|---|---|
| 1 | Vérification de la configuration | Config complète (host, port, username, password) | "Configuration SMTP valide" | "Configuration SMTP incomplète ou invalide" |
| 2 | Connexion au serveur | Socket TCP établie | "Connecté au serveur SMTP" | "Impossible de se connecter: [détail erreur]" |
| 3 | Authentification | LOGIN accepté | "Authentification réussie" | "Authentification échouée: [détail]" |
| 4 | Configuration de l'email | Headers/body OK | "Email configuré" | "Erreur de configuration email" |
| 5 | Envoi de l'email de test | Send() sans exception | "Email de test envoyé" | "Erreur d'envoi: [détail]" |
| 6 | Fermeture de la connexion | Disconnect propre | "Connexion fermée" | "Erreur de fermeture (non-bloquant)" |

## Flux utilisateur

1. Admin clique "Tester la connexion SMTP"
2. Bouton se désactive
3. Zone de progression apparaît avec 6 étapes en "pending"
4. Backend lance le test, 1ère étape passe à "running"
5. JavaScript polle toutes les 500ms
6. Chaque étape se complète, passe au vert, suivante devient "running"
7. Barre de progression remplit au fur et à mesure
8. À la fin : message final + bouton se réactive
9. Utilisateur peut voir exactement où ça a échoué (si erreur)

## Gestion d'erreurs

- **Timeout de test** : Si > 60 secondes, arrêter et afficher "Test expiré"
- **Erreur à une étape** : Arrêter le test, marquer comme 'error' avec message détaillé
- **Perte de polling** : Retry intelligente avec backoff (500ms → 1s → 2s)
- **Transient expiré** : Afficher "Session expirée, relancez le test"

## Implémentation

### Fichiers à créer/modifier

**Créer :**
- `includes/class-alert404-test-progress.php` — Gestion des étapes
- `assets/css/alert404-progress.css` — Styling de la barre
- (Potentiellement) `tests/unit/Test_Alert404_Test_Progress.php` — Tests unitaires

**Modifier :**
- `includes/class-alert404-smtp-handler.php` — Refactor `test_connection()` avec étapes
- `includes/class-alert404-settings.php` — Ajouter `handle_get_progress()` + AJAX action
- `assets/js/alert404-admin.js` — Ajouter gestion de la progression
- `404-alert.php` — Charger la nouvelle classe

### Dépendances

- Aucune dépendance externe requise
- Utilise WordPress transients (natif)
- Compatible avec Redis handler existant (optionnel)

## Succès et validation

✅ Utilisateur voit le progression détaillée du test SMTP  
✅ Barre et liste d'étapes se mettent à jour en temps réel  
✅ Messages d'erreur précis pour diagnostic  
✅ Pas de blocage de l'interface (polling asynchrone)  
✅ Aucun rechargement de page requis  

## Notes

- Le système de transients sera limité à ce test spécifique (pas d'impact sur les autres fonctionnalités)
- Les logs SMTP existants continueront de fonctionner en parallèle
- Compatible avec les installations Redis et sans Redis
