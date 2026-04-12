# Redis Setup & Testing — 404 Alert

Ce guide couvre l'installation et le test de Redis pour optimiser le rate limiting du plugin 404 Alert.

## Pourquoi Redis ?

Redis améliore les performances du rate limiting :
- **Sans Redis** : Rate limiting via `wp_transients` (stockage base de données)
- **Avec Redis** : Operations atomiques, 10-15x plus rapide

Redis est **optionnel**. Sans Redis, le plugin utilise les transients WordPress (plus lent mais fonctionnel).

## Installation

### Option 1 : Docker (Recommandé pour dev/test)

```bash
docker run -d --name redis -p 6379:6379 redis:7-alpine
```

Vérifier :
```bash
docker exec redis redis-cli ping
# Output: PONG
```

### Option 2 : Installation locale

**macOS (Homebrew):**
```bash
brew install redis
brew services start redis
```

**Ubuntu/Debian:**
```bash
sudo apt-get install redis-server
sudo systemctl start redis-server
```

**Windows:**
Utiliser [Redis-Windows](https://github.com/microsoftarchive/redis/releases) ou WSL2 + Docker.

### Option 3 : Services cloud

**Upstash** (Redis as a Service, free tier):
1. Aller à https://upstash.com
2. Créer un compte + DB Redis
3. Copier `UPSTASH_REDIS_REST_URL` et `UPSTASH_REDIS_REST_TOKEN`
4. Ajouter à `wp-config.php` (voir Configuration)

**AWS ElastiCache, Google Cloud Memorystore, etc.** : Similaire.

## Configuration

### Via wp-config.php

Ajouter avant `/* That's all, stop editing! */` :

```php
// Redis Configuration
define( 'ALERT404_REDIS_HOST', 'localhost' );       // Host Redis
define( 'ALERT404_REDIS_PORT', 6379 );              // Port (défaut 6379)
define( 'ALERT404_REDIS_PASSWORD', '' );            // Password (vide si aucun)
define( 'ALERT404_REDIS_DATABASE', 0 );             // DB index (0-15)
define( 'ALERT404_REDIS_TIMEOUT', 2 );              // Timeout connexion (secondes)
define( 'ALERT404_REDIS_ENABLED', true );           // Activer Redis
```

### Configurations cloud

**Upstash:**
```php
define( 'ALERT404_REDIS_HOST', 'upstash.com' );
define( 'ALERT404_REDIS_PORT', 6379 );
define( 'ALERT404_REDIS_PASSWORD', 'your-token' );  // REST token
define( 'ALERT404_REDIS_ENABLED', true );
```

**AWS ElastiCache:**
```php
define( 'ALERT404_REDIS_HOST', 'your-cluster.xxxx.ng.0001.usw2.cache.amazonaws.com' );
define( 'ALERT404_REDIS_PORT', 6379 );
define( 'ALERT404_REDIS_PASSWORD', 'your-auth-token' );  // Si auth activée
define( 'ALERT404_REDIS_ENABLED', true );
```

## Vérification

### Tester manuellement

```bash
# Tester la connexion
redis-cli -h localhost -p 6379 ping
# Output: PONG

# Vérifier les clés du plugin
redis-cli -h localhost -p 6379 KEYS "404_alert_*"
```

### Dans WordPress

1. Aller à `Réglages > 404 Alert`
2. Si Redis connecté, vous verrez un message **"Redis connected"**
3. Sinon : **"Redis unavailable - using transients fallback"**

### Via logs

Avec `WP_DEBUG_LOG` activé :

```bash
tail -f wp-content/debug.log | grep 404-Alert
```

Chercher les lignes :
```
[404-Alert] redis_connected: connected
[404-Alert] redis_connection_failed: [error details]
```

## Testing

### Test unitaire

```bash
vendor/bin/phpunit tests/unit/Test_Alert404_Redis_Handler.php
```

Output espéré :
```
Test_Alert404_Redis_Handler
✓ test_redis_connection
✓ test_redis_rate_limiting
✓ test_redis_fallback_to_transients
...
OK (15 tests)
```

### Test intégration

```bash
vendor/bin/phpunit tests/integration/Test_Alert404_E2E.php -v
```

Scénarios testés :
- 404 détecté → Email envoyé (avec Redis)
- 404 détecté → Redis indisponible → Fallback à transients
- Rate limit IP fonctionne avec Redis
- Rate limit global fonctionne avec Redis

### Test manuel

1. Activer le plugin
2. Accéder à `/page-inexistante`
3. Vérifier que :
   - Email reçu
   - `redis-cli KEYS "404_alert_*"` affiche les clés
   - Accès rapide à `/page-inexistante` (bloqué par rate limit)

## Dépannage

### "Redis unavailable"

**Vérifier :**
```bash
# Redis est-il lancé ?
redis-cli ping
# Output: PONG (oui) ou error (non)

# Vérifier les paramètres wp-config.php
define( 'ALERT404_REDIS_HOST', 'localhost' );  # Correct ?
define( 'ALERT404_REDIS_PORT', 6379 );         # Correct ?
```

**Solution :**
- Redémarrer Redis : `docker restart redis` ou `redis-cli shutdown && redis-server`
- Vérifier les logs : `tail -f wp-content/debug.log`
- Fallback automatique à transients (pas de perte de données)

### Performance lente

**Vérifier la latence Redis :**
```bash
redis-cli --latency
```

Accepté : <5ms
Problème : >50ms (vérifier la connexion réseau)

### Perte de données après redémarrage

Redis **par défaut** stocke les données en mémoire (données perdues au redémarrage).

**Solution :** Activer la persistance dans `redis.conf` :
```
save 60 1000    # Sauvegarder après 60s ou 1000 changements
appendonly yes  # Activer AOF (Append Only File)
```

Ou utiliser Upstash/AWS qui gèrent la persistance.

## Performance

### Benchmarks

**Rate limiting lookup (1000 requêtes) :**

| Méthode | Temps | Notes |
|---------|-------|-------|
| Transients | 250ms | Via BD WordPress |
| Redis local | 18ms | ~14x plus rapide |
| Redis cloud (Upstash) | 45ms | ~5x plus rapide (latence réseau) |

### Optimisations

- **Cluster Redis** : Pour haute charge, utiliser Redis Cluster
- **Connection pooling** : Réutiliser les connexions
- **Caching** : Utiliser Redis pour autres données aussi

## Production Checklist

- [ ] Redis installé et testé
- [ ] `wp-config.php` configuré avec credentials
- [ ] Persistance activée (save/AOF)
- [ ] Backups configurés
- [ ] Tests d'intégration passent
- [ ] Logs du plugin monitrés
- [ ] Failover vers transients fonctionne
- [ ] Performance benchmarkée (< 100ms rate limit)
