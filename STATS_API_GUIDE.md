# Guide API - Alert404_Stats

## Introduction

`Alert404_Stats` est la nouvelle classe robuste pour gérer les statistiques d'erreurs 404 du plugin 404 Alert. Elle remplace entièrement l'ancienne classe `Alert404_Storage`.

## Installation

La classe est chargée automatiquement :
```php
require_once ALERT404_DIR . 'includes/class-alert404-stats.php';
```

## Enregistrer un événement 404

### Méthode : `record(array $event): bool`

Enregistre une nouvelle erreur 404 dans la base de données.

**Paramètres :**
```php
$event = [
    'url' => 'http://example.com/missing',    // required
    'ip' => '192.168.1.1',                     // required
    'referrer' => 'http://google.com',         // optional
    'user_agent' => 'Mozilla/5.0',             // optional
    'user_readable' => 'Chrome 120',           // optional
];
```

**Retour :** `bool` - `true` si enregistré, `false` sinon

**Exemple :**
```php
$success = Alert404_Stats::record([
    'url' => $_SERVER['REQUEST_URI'],
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
]);

if (!$success) {
    error_log('Failed to record 404 event');
}
```

**Notes :**
- Si `url` est absent mais `full_url` est présent, `full_url` sera utilisé
- Tous les champs sont automatiquement sanitisés
- Les URLs trop longues (>2000 car) sont tronquées
- La validation rejette les événements sans URL ou IP

---

## Récupérer les statistiques

### Méthode : `get_recent(int $limit = 100): array`

Retourne les 404 les plus récents avec tous les détails.

**Paramètres :**
- `$limit` : Nombre de records (1-1000)

**Retour :** `array` - Liste d'enregistrements

**Exemple :**
```php
$recent = Alert404_Stats::get_recent(50);

foreach ($recent as $record) {
    echo $record['created_at']; // 2024-01-15 10:30:45
    echo $record['url'];        // http://example.com/missing
    echo $record['ip'];         // 192.168.1.1
}
```

**Structure d'un record :**
```php
[
    'id' => 1,
    'url' => 'http://example.com/missing',
    'ip' => '192.168.1.1',
    'referrer' => 'http://google.com',
    'user_agent' => 'Mozilla/5.0',
    'user_agent_readable' => 'Chrome 120',
    'created_at' => '2024-01-15 10:30:45',
]
```

---

### Méthode : `get_total_count(): int`

Retourne le nombre total d'erreurs 404 enregistrées.

**Retour :** `int` - Nombre total

**Exemple :**
```php
$total = Alert404_Stats::get_total_count();
echo "Erreurs 404 totales : $total";
```

---

### Méthode : `get_unique_urls_count(): int`

Retourne le nombre d'URLs uniques ayant généré une erreur 404.

**Retour :** `int` - Nombre d'URLs uniques

**Exemple :**
```php
$unique_urls = Alert404_Stats::get_unique_urls_count();
echo "$unique_urls URLs différentes ont généré des 404";
```

---

## Analyser les données

### Méthode : `get_top_urls(int $limit = 10): array`

Retourne les URLs les plus demandées (avec le plus d'erreurs 404).

**Paramètres :**
- `$limit` : Nombre d'URLs (1-100)

**Retour :** `array` - Tableau associatif `[URL => count]`

**Exemple :**
```php
$top_urls = Alert404_Stats::get_top_urls(5);

foreach ($top_urls as $url => $count) {
    printf("%s : %d erreurs\n", $url, $count);
}
// Sortie :
// http://example.com/wp-login.php : 45 erreurs
// http://example.com/admin : 23 erreurs
// ...
```

---

### Méthode : `get_top_ips(int $limit = 10): array`

Retourne les adresses IP les plus actives.

**Paramètres :**
- `$limit` : Nombre d'IPs (1-100)

**Retour :** `array` - Tableau associatif `[IP => count]`

**Exemple :**
```php
$top_ips = Alert404_Stats::get_top_ips(10);

foreach ($top_ips as $ip => $count) {
    echo "$ip a généré $count erreurs 404\n";
}
```

---

### Méthode : `get_count_for_date(string $date): int`

Retourne le nombre de 404 pour une date spécifique.

**Paramètres :**
- `$date` : Format YYYY-MM-DD (par exemple "2024-01-15")

**Retour :** `int` - Nombre d'erreurs 404 ce jour

**Exemple :**
```php
$today = gmdate('Y-m-d');
$count = Alert404_Stats::get_count_for_date($today);
echo "Erreurs 404 aujourd'hui : $count";
```

**Notes :**
- Les dates invalides retournent 0
- Pas d'exception levée, comportement dégradé gracieux

---

### Méthode : `get_count_by_referrer(int $limit = 10): array`

Retourne les sources de trafic qui génèrent le plus de 404.

**Paramètres :**
- `$limit` : Nombre de referrers (1-100)

**Retour :** `array` - Tableau associatif `[referrer => count]`

**Exemple :**
```php
$by_referrer = Alert404_Stats::get_count_by_referrer();

foreach ($by_referrer as $referrer => $count) {
    echo "$referrer : $count erreurs 404\n";
}
```

---

## Gestion des données

### Méthode : `clear(): bool`

Efface tous les enregistrements de statistiques.

**Retour :** `bool` - `true` si succès, `false` si erreur

**Exemple :**
```php
$success = Alert404_Stats::clear();
if ($success) {
    echo "Statistiques effacées";
} else {
    error_log("Erreur lors de l'effacement");
}
```

---

### Méthode : `export_csv(): void`

Exporte tous les enregistrements au format CSV et termine l'exécution.

**Exemple :**
```php
// Dans un webhook d'export
if ($user_can_export) {
    Alert404_Stats::export_csv();
}
```

**Format d'export :**
```csv
ID,URL,IP,Referrer,User Agent,Timestamp
1,http://example.com/1,192.168.1.1,http://google.com,Mozilla/5.0,2024-01-15 10:30:45
```

---

## Gestion du cache

Le caching est **automatique** et transparent. Toutes les données de lecture sont cachées avec un TTL de 5 minutes.

### Comment fonctionne le cache

1. **Premier appel** → Hit database, cache le résultat
2. **Appels suivants (< 5 min)** → Retourne le cache
3. **Après mutation (insert/clear)** → Cache invalidé automatiquement
4. **Après 5 minutes** → Cache expiré, nouvelle query

### Invalidation manuelle

Le cache est invalidé automatiquement après :
- `record()` - Après insertion d'un nouvel événement
- `clear()` - Après suppression complète

Vous n'avez pas besoin de gérer le cache manuellement.

---

## Gestion des erreurs

Toutes les méthodes gèrent les erreurs gracieusement :

```php
// record() retourne false en cas d'erreur
if (!Alert404_Stats::record($event)) {
    // Erreur capturée et loggée
}

// Les méthodes de lecture retournent 0 ou []
$count = Alert404_Stats::get_total_count(); // 0 si erreur DB
$urls = Alert404_Stats::get_top_urls();     // [] si erreur
```

**Logging :**

Toutes les erreurs sont loggées via `Alert404_Logger::log_stats_error()` :
```
[Alert404_Stats] Record 404 failed
[Alert404_Stats] Get top URLs failed: Database error message
```

---

## Backward compatibility

L'ancienne classe `Alert404_Storage` continue de fonctionner. Toutes les méthodes délèguent à `Alert404_Stats`.

```php
// Ancien code - fonctionne toujours
Alert404_Storage::get_stats(100);
Alert404_Storage::get_total_count();
Alert404_Storage::clear_stats();

// Nouveau code - à préférer
Alert404_Stats::get_recent(100);
Alert404_Stats::get_total_count();
Alert404_Stats::clear();
```

---

## Performance et sécurité

### Performance

- ✓ Requêtes optimisées avec index
- ✓ Caching intelligent (5 min TTL)
- ✓ Limit de 1000 records auto-enforcée
- ✓ Pas de requête N+1

### Sécurité

- ✓ Prepared statements
- ✓ Sanitization de toutes les entrées
- ✓ Validation stricte des paramètres
- ✓ Pas d'injection SQL possible

---

## Exemples d'utilisation

### Dashboard personnalisé

```php
$total = Alert404_Stats::get_total_count();
$unique = Alert404_Stats::get_unique_urls_count();
$top_urls = Alert404_Stats::get_top_urls(5);
$recent = Alert404_Stats::get_recent(20);

echo "404 totales: $total";
echo "URLs uniques: $unique";
echo "Top URLs: " . count($top_urls);
```

### Widget de monitoring

```php
$today = gmdate('Y-m-d');
$today_count = Alert404_Stats::get_count_for_date($today);
$top_ips = Alert404_Stats::get_top_ips(5);

foreach ($top_ips as $ip => $count) {
    if ($count > 10) {
        // Alerter sur IP suspecte
    }
}
```

### Export mensuel

```php
if ($user_requested_export) {
    Alert404_Stats::export_csv();
}
```

---

## Dépannage

### Aucune donnée affichée

1. Vérifier que les statistiques sont activées dans les options
2. Vérifier que des 404 ont été enregistrés
   ```php
   echo Alert404_Stats::get_total_count();
   ```

### Erreurs de DB

Les erreurs de base de données sont loggées en debug.log :
```
[Alert404_Stats] Record 404 failed
[Alert404_Stats] Table creation failed
```

Vérifier les permissions de base de données.

### Cache incorrect

Le cache est invalidé automatiquement. Si un problème :
```php
// Forcer rechargement depuis DB
wp_cache_flush_group('404_alert_stats');
```

---

## Migration depuis Alert404_Storage

Si vous utilisez actuellement `Alert404_Storage` :

### Ce qui fonctionne sans changement
```php
Alert404_Storage::get_stats();        // → Alert404_Stats::get_recent()
Alert404_Storage::get_total_count();  // → Alert404_Stats::get_total_count()
Alert404_Storage::export_csv();       // → Alert404_Stats::export_csv()
```

### Mise à jour recommandée
```php
// Ancien
$stats = Alert404_Storage::get_stats(100);

// Nouveau
$stats = Alert404_Stats::get_recent(100);
```

---

## Référence complète

| Méthode | Type | Cache | Description |
|---------|------|-------|-------------|
| `record()` | Write | ✓ Invalide | Enregistre un événement |
| `get_recent()` | Read | ✓ Oui | Derniers enregistrements |
| `get_total_count()` | Read | ✓ Oui | Nombre total |
| `get_unique_urls_count()` | Read | ✓ Oui | URLs uniques |
| `get_top_urls()` | Read | ✓ Oui | URLs les plus fréquentes |
| `get_top_ips()` | Read | ✓ Oui | IPs les plus actives |
| `get_count_for_date()` | Read | ✓ Oui | Comptage par date |
| `get_count_by_referrer()` | Read | ✓ Oui | Comptage par referrer |
| `clear()` | Write | ✓ Invalide | Efface tout |
| `export_csv()` | Read | ✗ Non | Export CSV |

---

## Support et documentation

- **Refactor details** : [STATS_REFACTOR.md](STATS_REFACTOR.md)
- **Changelog** : [CHANGELOG_STATS_REFACTOR.md](CHANGELOG_STATS_REFACTOR.md)
- **Tests** : [tests/unit/Test_Alert404_Stats.php](tests/unit/Test_Alert404_Stats.php)
