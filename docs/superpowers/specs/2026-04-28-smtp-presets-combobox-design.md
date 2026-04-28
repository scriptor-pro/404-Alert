# Combo-box SMTP avec Présets et Solutions Personnalisées - Design Spec

**Date:** 2026-04-28  
**Objectif:** Remplacer les boutons preset par une combo-box intelligente permettant de filtrer les présets existants et de créer/sauvegarder des solutions personnalisées réutilisables

## Vue d'ensemble

Implémenter une **combo-box SMTP** qui :
- Affiche les 7 présets existants (Gmail, Yahoo, ProtonMail, Brevo, Mailtrap, SendGrid, Mailgun)
- Permet la recherche/filtrage en tapant
- Permet la sélection directe en cliquant
- Propose "Autre" pour créer des solutions personnalisées nommées
- Sauvegarde et réutilise les solutions personnalisées

## Architecture

### 1. Frontend - Combo-box

#### Composant HTML
Remplacer les boutons `render_preset_buttons()` par une combo-box avec :
- `<input type="text" id="404-alert-smtp-preset-search">` — Champ de recherche/sélection
- `<ul id="404-alert-preset-suggestions">` — Liste déroulante dynamique des suggestions
- Affiche présets filtrés + "Autre"

#### Logique JavaScript (`assets/js/alert404-smtp-presets.js`)

**Initialisation :**
- Charger les présets via `wp_localize_script()`
- Charger les solutions personnalisées sauvegardées
- Fusionner les deux listes

**Filtrage :**
- À chaque frappe, filtrer par nom/host/description
- Afficher suggestions filtrées en temps réel
- Case-insensitive, recherche partielle

**Sélection :**
- Clic ou Enter sur un preset → remplir les champs automatiquement
- Clic sur "Autre" → ouvrir modal de création

**Création de solution personnalisée :**
- Modal avec champ "Nom de votre solution" (requis)
- Affiche les champs actuels (host, port, encryption) en readonly
- Bouton "Sauvegarder cette solution" → AJAX vers endpoint

**Sauvegarde :**
- AJAX POST vers `wp_ajax_404_alert_save_custom_preset`
- Envoie : nom, host, port, encryption
- Réponse : ID unique de la solution
- Ajouter la nouvelle solution à la combo-box
- Afficher confirmation "Solution sauvegardée"

### 2. Backend (PHP)

#### Classe Alert404_SMTP_Presets

Ajouter méthode pour gérer les solutions personnalisées :

```php
public static function get_custom_presets(): array {
    $custom = get_option( '404_alert_smtp_custom_presets', array() );
    return $custom;
}

public static function save_custom_preset( string $name, string $host, int $port, string $encryption ): string {
    $custom = self::get_custom_presets();
    $id = 'custom_' . time() . '_' . rand( 1000, 9999 );
    
    $custom[ $id ] = array(
        'name'       => sanitize_text_field( $name ),
        'host'       => sanitize_text_field( $host ),
        'port'       => absint( $port ),
        'encryption' => sanitize_text_field( $encryption ),
        'created_at' => current_time( 'mysql' ),
    );
    
    update_option( '404_alert_smtp_custom_presets', $custom );
    return $id;
}

public static function delete_custom_preset( string $id ): bool {
    $custom = self::get_custom_presets();
    if ( isset( $custom[ $id ] ) ) {
        unset( $custom[ $id ] );
        update_option( '404_alert_smtp_custom_presets', $custom );
        return true;
    }
    return false;
}

public static function get_all_presets(): array {
    $presets = self::get_presets();
    $custom = self::get_custom_presets();
    return array_merge( $presets, $custom );
}
```

#### Alert404_Settings - AJAX handlers

Ajouter action AJAX dans `init()` :
```php
add_action( 'wp_ajax_404_alert_save_custom_preset', array( self::class, 'handle_save_custom_preset' ) );
add_action( 'wp_ajax_404_alert_delete_custom_preset', array( self::class, 'handle_delete_custom_preset' ) );
add_action( 'wp_ajax_404_alert_get_presets', array( self::class, 'handle_get_presets' ) );
```

Implémenter les méthodes :
- `handle_save_custom_preset()` — Sauvegarde une solution personnalisée
- `handle_delete_custom_preset()` — Supprime une solution personnalisée
- `handle_get_presets()` — Retourne tous les présets (natifs + personnalisés) pour le JavaScript

#### Enqueuing

Modifier `enqueue_admin_scripts()` :
- Enqueuer `alert404-smtp-presets.js`
- Passer via `wp_localize_script()` :
  - `alert404PresetVars.presets` — Les 7 présets natifs
  - `alert404PresetVars.customPresets` — Solutions personnalisées sauvegardées
  - `alert404PresetVars.ajaxurl` — URL admin-ajax
  - `alert404PresetVars.nonce` — Nonce pour AJAX

#### Rendu HTML

Modifier `render_field_smtp_host()` (ou créer nouvelle fonction) :
- Remplacer les boutons par la combo-box HTML
- Ajouter container pour suggestions
- Ajouter modal pour création de solution personnalisée

## Stockage

### Option WordPress : `404_alert_smtp_custom_presets`

```php
array(
    'custom_1234_5678' => array(
        'name'       => 'Mon serveur ProtonMail',
        'host'       => 'custom.smtp.host',
        'port'       => 587,
        'encryption' => 'tls',
        'created_at' => '2026-04-28 12:00:00',
    ),
    'custom_1234_5679' => array(
        'name'       => 'Serveur entreprise',
        'host'       => 'mail.company.com',
        'port'       => 465,
        'encryption' => 'ssl',
        'created_at' => '2026-04-28 13:00:00',
    ),
)
```

## Flux utilisateur

### Scénario 1 : Utiliser un preset natif
1. Admin clique dans la combo-box
2. Voit : "Gmail", "Yahoo", "ProtonMail", etc., "Autre"
3. Tape "gm" → affiche "Gmail"
4. Clique sur Gmail → fields se remplissent (smtp.gmail.com, 587, tls)

### Scénario 2 : Créer une solution personnalisée
1. Admin tape "Mon serveur" dans la combo-box
2. Voit "Autre" en bas de la liste
3. Clique sur "Autre" → modal s'ouvre
4. Demande "Nom de votre solution ?"
5. Admin voit les champs actuels (host, port, encryption)
6. Admin a rempli manuellement : custom.host, 587, tls
7. Admin clique "Sauvegarder cette solution"
8. AJAX sauvegarde → modal ferme → "Solution sauvegardée !"
9. Prochaine fois, admin tape "Mon serveur" → voit la solution dans la liste

### Scénario 3 : Réutiliser une solution personnalisée
1. Admin tape "Mon serveur" dans la combo-box
2. Voit la solution personnalisée sauvegardée
3. Clique → fields se remplissent automatiquement

## Implémentation

### Fichiers à créer
- `assets/js/alert404-smtp-presets.js` — Combo-box avec filtrage et AJAX

### Fichiers à modifier
- `includes/class-alert404-settings.php` — Ajouter AJAX handlers, enqueuer JS, modifier render
- `includes/class-alert404-smtp-presets.php` — Ajouter méthodes pour personnalisées

### Dépendances
- Aucune (jQuery déjà enqueué, WordPress natif)

## Succès et validation

✅ Combo-box affiche et filtre les 7 présets  
✅ Sélection d'un preset remplit automatiquement les champs  
✅ "Autre" permet créer une solution personnalisée nommée  
✅ Solutions sauvegardées sont réutilisables  
✅ Filtrage rapide et réactif (case-insensitive)  
✅ Interface intuitive et mobile-friendly  
✅ Données personnalisées persistantes  

## Notes

- Solutions personnalisées ne sont pas partagées entre users (stockées globalement en option WordPress)
- Pas de limite de solutions personnalisées
- Suppression future possible via AJAX (pas demandée mais implémentée pour flexibilité)
- Pas de migration des anciennes données (nouveaux déploiements seulement)
