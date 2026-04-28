# Mécanisme de Mise à Jour — 404 Alert

## Vue d'ensemble

Le plugin 404-Alert possède un système de mise à jour **automatique** pour les migrations de schéma et les données héritées, mais **pas** de vérification automatique de nouvelles versions.

## Architecture de Mise à Jour

### 1. Classes Impliquées

#### Alert404_Storage (`class-alert404-storage.php`)

Gère les migrations de schéma de base de données :

- **Constante:** `SCHEMA_VERSION = '1'`
- **Méthode:** `ensure_storage_ready()` - Vérifie et applique les migrations
- **Méthode:** `create_or_update_table()` - Crée/met à jour la table MySQL
- **Méthode:** `migrate_legacy_option_storage()` - Migre les données anciennes

#### Alert404_Activator (`class-alert404-activator.php`)

Gère l'activation/désactivation du plugin :

- **Hook:** `register_activation_hook()` - À l'activation
- **Hook:** `register_deactivation_hook()` - À la désactivation
- **Méthode:** `activate()` - Initialise les options et tables
- **Méthode:** `deactivate()` - Conserve les données

## Flux de Mise à Jour

### À l'Activation du Plugin

```
User active le plugin
  ↓
register_activation_hook() déclenche Alert404_Activator::activate()
  ↓
Alert404_Storage::init()
  ↓
ensure_storage_ready()
  ├─ Récupère version schéma stockée (404_alert_stats_schema_version)
  ├─ Compare avec SCHEMA_VERSION = '1'
  ├─ Si différent → create_or_update_table()
  ├─ Migre données legacy si nécessaire → migrate_legacy_option_storage()
  ├─ Met à jour options WordPress
  │  ├─ 404_alert_options (email, limite, etc.)
  │  └─ 404_alert_smtp_options (SMTP config)
  ├─ Crée transient alert404_activated
  └─ Retour
```

### À Chaque Chargement du Plugin

```
WordPress charge les plugins (plugins_loaded hook)
  ↓
alert404_init() est exécutée
  ↓
Alert404_Storage::init()
  ↓
ensure_storage_ready()
  ├─ Vérifie SCHEMA_VERSION
  ├─ Si besoin → create_or_update_table()
  └─ Migre données legacy si nécessaire
```

## Système de Migration de Schéma

### `ensure_storage_ready()`

```php
private static function ensure_storage_ready(): void {
    // 1. Récupère version actuelle du schéma
    $current_version = (string) get_option( self::SCHEMA_OPTION_KEY, '' );
    
    // 2. Compare avec version définie
    if ( self::SCHEMA_VERSION !== $current_version ) {
        self::create_or_update_table();
        update_option( self::SCHEMA_OPTION_KEY, self::SCHEMA_VERSION, false );
    }
    
    // 3. Migre données legacy (une seule fois)
    if ( ! get_option( self::MIGRATION_OPTION_KEY, false ) ) {
        self::migrate_legacy_option_storage();
        update_option( self::MIGRATION_OPTION_KEY, 1, false );
    }
}
```

### `create_or_update_table()`

Crée ou met à jour la table `wp_404_alert_stats` :

```sql
CREATE TABLE wp_404_alert_stats (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  url TEXT NOT NULL,
  ip VARCHAR(45) NOT NULL,
  referrer TEXT NOT NULL,
  user_agent TEXT NOT NULL,
  user_agent_readable TEXT NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY created_at (created_at),
  KEY ip (ip)
) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Utilise `dbDelta()` de WordPress :
- ✓ Crée la table si n'existe pas
- ✓ Ajoute les colonnes manquantes
- ✓ Crée les index
- ✓ Ne supprime jamais de colonnes

### `migrate_legacy_option_storage()`

Migre les données de l'option WordPress vers la table MySQL :

1. Récupère `404_alert_stats` option (format ancien)
2. Itère sur chaque record
3. Insère dans la table `wp_404_alert_stats`
4. Sanitize tous les champs avec `sanitize_text_field()`
5. Supprime l'option legacy après succès

Exemple de transformation :

**Avant (Option WordPress):**
```php
[
  [
    'url' => '/non-existent',
    'ip' => '192.168.1.1',
    'timestamp' => '2026-04-20 10:30:00',
    'user_agent' => 'Mozilla/5.0...',
    // ... autres champs
  ]
]
```

**Après (Table MySQL):**
```sql
INSERT INTO wp_404_alert_stats 
  (url, ip, referrer, user_agent, user_agent_readable, created_at)
VALUES 
  ('/non-existent', '192.168.1.1', '', 'Mozilla/5.0...', '...', '2026-04-20 10:30:00')
```

## Options WordPress Créées

À l'activation, le plugin crée ces options :

### 1. `404_alert_options`

```php
[
  'email' => 'admin@example.com',          // Email destinataire
  'daily_limit' => 500,                     // Max emails/jour
  'ip_cooldown' => 300,                     // Délai entre emails (secondes)
  'force_logging' => 0,                     // Boolean
  'enable_stats' => 0                       // Boolean
]
```

### 2. `404_alert_smtp_options`

```php
[
  'host' => '',                             // Vide par défaut
  'port' => 587,                            // Port par défaut
  'username' => '',                         // Vide par défaut
  'password' => '',                         // Chiffré en production
  'encryption' => 'tls',                    // tls, ssl, ou none
  'from_email' => 'admin@example.com',      // Email administrateur
  'from_name' => 'My Site'                  // Nom du blog
]
```

### 3. Flags de Schéma

```php
'404_alert_stats_schema_version' => '1'     // Version du schéma
'404_alert_stats_migrated' => 1             // Flag de migration
```

## Sécurité des Migrations

✓ **Sanitization :** `sanitize_text_field()` sur tous les champs

✓ **Prepared Statements :** `$wpdb->prepare()` pour les requêtes SQL

✓ **Charset :** `$wpdb->get_charset_collate()` appliqué

✓ **Idempotent :** `dbDelta()` peut être appelé plusieurs fois sans danger

✓ **Données Préservées :** Les données legacy ne sont supprimées qu'après migration réussie

## Limitations Actuelles

### ❌ Pas de Vérification de Mise à Jour En Ligne

Le plugin n'effectue pas de requête HTTP pour vérifier s'il existe une nouvelle version. L'administrateur doit :
- Vérifier manuellement la nouvelle version
- Télécharger le ZIP
- Télécharger via wp-admin

### ❌ Pas de Support WordPress.org Auto-Update

Pour bénéficier de l'auto-update via wp-admin, le plugin doit être publié sur WordPress.org avec les bons headers. Actuellement :
- Le header `Version: 1.2.0` est présent ✓
- Mais pas de publication WordPress.org ✗

### ❌ Pas de Vérification d'Incompatibilité

En cas de downgrade, aucune vérification n'empêche les problèmes de schéma. Le plugin suppose toujours une mise à niveau (version ascending).

### ❌ Pas de Notification d'Update

L'administrateur reçoit l'activation transient, mais pas de notification pour les mises à jour futures.

## Points Forts

✓ **Migrations Idempotentes :** Les migrations peuvent s'exécuter plusieurs fois sans problème

✓ **Données Héritées :** Les anciennes données sont automatiquement converties

✓ **Schéma Versionné :** Prêt pour de futures migrations (SCHEMA_VERSION = '2', etc.)

✓ **Activation/Désactivation Saine :** Utilise les bons hooks WordPress

✓ **Options Initialisées :** Les bonnes valeurs par défaut sont créées

✓ **Transient d'Activation :** Permet l'affichage de messages d'accueil

## Futures Améliorations Recommandées

### 1. Ajouter Vérification de Version Plugin

```php
public static function check_plugin_version(): void {
    $stored_version = get_option( 'alert404_plugin_version', '0' );
    
    if ( ALERT404_VERSION !== $stored_version ) {
        do_action( 'alert404_upgrade', $stored_version, ALERT404_VERSION );
        update_option( 'alert404_plugin_version', ALERT404_VERSION );
    }
}
```

Ajouter à `alert404_init()`.

### 2. Ajouter Hook de Mise à Jour

```php
do_action( 'alert404_upgrade', $old_version, $new_version );
```

Permet aux extensions d'exécuter du code lors de la mise à jour.

### 3. Ajouter Notification de Mise à Jour

Afficher un message admin lors de la détection d'une nouvelle version téléchargée.

### 4. Publier sur WordPress.org

Une fois publié, WordPress gère automatiquement les mises à jour.

### 5. Versionner le Schéma de Base de Données

Pour les futures migrations, incrémenter `SCHEMA_VERSION` et ajouter une méthode de migration correspondante.

## Exemple : Ajouter Une Migration Futur

Pour ajouter une migration de v1 à v2 de schéma :

```php
// Dans Alert404_Storage

private const SCHEMA_VERSION = '2';  // Incrémenter

private static function create_or_update_table(): void {
    global $wpdb;
    
    // ... code existant ...
    
    // Ajouter nouvelle colonne pour v2
    $wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN new_field VARCHAR(255)" );
}
```

## Conclusion

Le plugin 404-Alert possède un système de mise à jour **solide** pour les migrations de données internes, mais **pas** de système de détection et déploiement automatique des nouvelles versions du plugin.

- ✅ **Interne :** Migrations de schéma robustes
- ❌ **Externe :** Pas de vérification de mise à jour en ligne
- ⚠️ **Recommandé :** Publier sur WordPress.org pour auto-updates
