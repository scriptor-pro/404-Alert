# Refactorisation complète du système de statistiques

## Vue d'ensemble

Le système de statistiques a été complètement refactorisé pour être robuste, fiable et maintenable. Toute l'implémentation a été déplacée de `Alert404_Storage` vers une nouvelle classe `Alert404_Stats` mieux structurée.

## Problèmes résolus

### 1. Signature de méthode confuse
**Avant :**
```php
Alert404_Storage::record_404( $to, $subject, $payload );
```
Les paramètres `$to` et `$subject` n'étaient jamais utilisés.

**Après :**
```php
Alert404_Stats::record( $payload );
```
Signature claire et fonctionnelle.

### 2. Accès incohérent aux clés du payload
**Avant :** Accès inconsistants avec `??` et mélange de clés (`url`, `full_url`, `user_readable`, etc.)
**Après :** Validation stricte et normalisation au cœur de `validate_event()`.

### 3. Gestion d'erreurs inexistante
**Avant :** Pas de vérification systématique des résultats.
**Après :** Chaque opération retourne un bool ou gère les exceptions.

### 4. Cache non invalidé proprement
**Avant :** `invalidate_cache()` manuel à chaque insert.
**Après :** Invalidation automatique via `clear_all_cache()` après chaque mutation.

## Caractéristiques nouvelles

### Validation robuste
```php
private static function validate_event( array $event ) {
    $url = (string) ( $event['url'] ?? $event['full_url'] ?? '' );
    $ip = (string) ( $event['ip'] ?? '' );
    
    if ( empty( $url ) || empty( $ip ) ) {
        return false; // Rejet explicite
    }
    
    return array(
        'url' => sanitize_url( $url ),
        'ip' => sanitize_text_field( $ip ),
        // ...
    );
}
```

### Gestion d'erreurs complète
```php
public static function record( array $event ): bool {
    try {
        self::ensure_table_exists();
        $validated = self::validate_event( $event );
        if ( !$validated ) {
            return false;
        }
        return self::insert_record( $validated );
    } catch ( Throwable $e ) {
        Alert404_Logger::log_stats_error(
            'Record 404 failed',
            $e->getMessage()
        );
        return false;
    }
}
```

### Caching intelligent
- Clés de cache uniques et prévisibles
- TTL de 5 minutes pour tous les caches
- Invalidation complète lors de mutations
- Fallback manuel si `wp_cache_flush_group()` n'existe pas

### Types de retour explicites
```php
public static function record( array $event ): bool
public static function get_total_count(): int
public static function get_recent( int $limit = 100 ): array
public static function clear(): bool
```

### Limites de sécurité
- URL tronquées à 2000 caractères
- IPs validées à 45 caractères (IPv6 max)
- Limites enforced sur les paramètres `$limit`
- Nettoyage automatique des anciennes données (max 1000 records)

## Migration

### Mises à jour requises

#### 1. Mailer (class-alert404-mailer.php)
```php
// AVANT
if ( class_exists( 'Alert404_Storage' ) ) {
    Alert404_Storage::record_404( $to, $subject, $payload );
}

// APRÈS
if ( class_exists( 'Alert404_Stats' ) ) {
    $options = get_option( '404_alert_options', array() );
    if ( !empty( $options['enable_stats'] ) ) {
        Alert404_Stats::record( $payload );
    }
}
```

#### 2. Dashboard (class-alert404-dashboard.php)
```php
// AVANT
$stats = Alert404_Storage::get_stats( 100 );

// APRÈS
$stats = Alert404_Stats::get_recent( 100 );
```

#### 3. Fichier principal (404-alert.php)
```php
// Remplacer Alert404_Storage par Alert404_Stats
require_once ALERT404_DIR . 'includes/class-alert404-stats.php';

// Supprimer l'initialisation manuelle
// Avant: Alert404_Storage::init();
```

## API complète

### Méthodes de lecture

#### `get_total_count(): int`
Retourne le nombre total d'erreurs 404 enregistrées.

#### `get_unique_urls_count(): int`
Retourne le nombre d'URLs uniques ayant généré des 404.

#### `get_recent(int $limit = 100): array`
Retourne les 404 les plus récents avec tous les détails.

```php
[
    [
        'id' => 1,
        'url' => 'http://example.com/missing',
        'ip' => '192.168.1.1',
        'referrer' => 'http://google.com',
        'user_agent' => 'Mozilla/5.0',
        'user_agent_readable' => 'Chrome 120',
        'created_at' => '2024-01-15 10:30:45',
    ],
    // ...
]
```

#### `get_top_urls(int $limit = 10): array`
Retourne les URLs les plus demandées.

```php
[
    'http://example.com/admin' => 45,
    'http://example.com/wp-login.php' => 23,
]
```

#### `get_top_ips(int $limit = 10): array`
Retourne les IPs les plus actives.

#### `get_count_for_date(string $date): int`
Retourne le nombre de 404 pour une date spécifique (format YYYY-MM-DD).

#### `get_count_by_referrer(int $limit = 10): array`
Retourne les referrers avec le plus de 404.

### Méthodes d'écriture

#### `record(array $event): bool`
Enregistre un événement 404.

```php
$success = Alert404_Stats::record([
    'url' => 'http://example.com/missing',
    'ip' => $_SERVER['REMOTE_ADDR'],
    'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
]);
```

#### `clear(): bool`
Efface toutes les statistiques et invalide tous les caches.

#### `export_csv(): void`
Exporte les statistiques au format CSV et termine l'exécution.

## Robustesse du code

### Validation des entrées
```
✓ URLs vérifiées pour être non-vides
✓ IPs vérifiées pour être non-vides
✓ Tous les textes sont sanitized
✓ Limites strictes sur les paramètres
✓ Types de retour explicites
```

### Gestion des erreurs
```
✓ Try-catch pour les opérations DB
✓ Vérification des résultats de wpdb
✓ Logging centralisé via Alert404_Logger
✓ Fallback gracieux en cas d'erreur
```

### Performance
```
✓ Caching avec TTL de 5 minutes
✓ Index sur created_at, ip, url
✓ Requêtes SELECT optimisées
✓ Limite de 1000 records auto-enforcée
✓ Nettoyage automatique des anciennes données
```

### Sécurité
```
✓ Prepared statements pour tous les queries
✓ Sanitization de toutes les entrées
✓ Validation de format des dates
✓ Limites de longueur sur les URLs
✓ Nettoyage du cache après mutations
```

## Tests unitaires

Un ensemble complet de tests unitaires a été créé dans `tests/unit/Test_Alert404_Stats.php` :

- ✓ `test_record_with_valid_event()` - Enregistrement valide
- ✓ `test_record_with_missing_url()` - Rejet sans URL
- ✓ `test_record_with_missing_ip()` - Rejet sans IP
- ✓ `test_record_with_full_url_fallback()` - Fallback full_url
- ✓ `test_get_total_count()` - Comptage total
- ✓ `test_get_unique_urls_count()` - Comptage d'URLs uniques
- ✓ `test_get_recent()` - Récupération des récents
- ✓ `test_get_recent_with_limit()` - Respecte la limite
- ✓ `test_get_top_urls()` - Classement des URLs
- ✓ `test_get_top_ips()` - Classement des IPs
- ✓ `test_get_count_for_date()` - Comptage par date
- ✓ `test_get_count_by_referrer()` - Comptage par referrer
- ✓ `test_clear()` - Nettoyage complet
- ✓ `test_record_returns_correct_data_types()` - Vérification des types
- ✓ `test_caching()` - Validation du caching

## Backward compatibility

`Alert404_Storage` peut être conservée pour la compatibilité, mais n'est plus utilisée.

## Fichiers modifiés

- ✓ Création: `includes/class-alert404-stats.php` (580 lignes)
- ✓ Modification: `includes/class-alert404-mailer.php` (meilleure gestion)
- ✓ Modification: `includes/class-alert404-dashboard.php` (utilisation Stats)
- ✓ Modification: `404-alert.php` (chargement Stats)
- ✓ Création: `tests/unit/Test_Alert404_Stats.php` (tests complets)

## Vérification de qualité

### Code style
- ✓ Respect des standards WordPress
- ✓ Docblocks PHPDoc complets
- ✓ Nommage clair et cohérent
- ✓ Pas de fonctions inutilisées

### Maintenabilité
- ✓ Méthodes privées pour abstraire la complexité
- ✓ Responsabilité unique par méthode
- ✓ Logging centralisé
- ✓ Pas de dépendances globales

### Performance
- ✓ Queries optimisées avec index
- ✓ Caching agressif
- ✓ Nettoyage automatique des données
- ✓ Pas de requêtes N+1
