# 🔐 Rate Limiter : Comparaison des 3 Solutions

**Contexte** : Remplacer le système de verrous cassé qui permet les race conditions.

---

## Option 1️⃣ : WordPress Options (Atomic)

### Code

```php
// Remplacer acquire_lock() par une approche atomique simple
private static function acquire_lock( string $lock_key, int $timeout ): bool {
    // WordPress update_option() a une 4ème paramètre 'autoload'
    // Mais l'atomicité n'est pas garantie entre get/set
    
    // Meilleure approche: utiliser increment_option() qui EST atomique
    $current = get_option( $lock_key, 0 );
    
    // Problème: get_option() + check n'est pas atomique non plus...
    // Donc cette approche ne fonctionne PAS vraiment mieux
}

// Vraie approche atomique avec WordPress = impossible
// WordPress n'a pas de compare-and-swap (CAS) natif
```

**Honnêtement** : Cette option est un **mirage**. WordPress Options ne sont **pas atomiques** sans helpers tiers.

### ✅ Avantages

- ✅ Zéro dépendance externe
- ✅ Utilise WordPress core uniquement
- ✅ Stockage persistant (base de données)
- ✅ Compatible avec tous les environnements
- ✅ Pas de latence réseau (pas comme Redis)

### ❌ Inconvénients

- ❌ **PAS VRAIMENT ATOMIQUE** (WordPress n'a pas de CAS natif)
  ```php
  // WordPress:
  get_option( 'key' )      // LECTURE (0.5ms)
                           // [RACE WINDOW]
  update_option( 'key' )   // ÉCRITURE (0.5ms)
  // Entre = race condition = MÊME PROBLÈME QU'AVANT
  ```

- ❌ Option size limit (255 chars en certaines versions)
- ❌ Lent si beaucoup de requêtes simultanées (I/O base de données)
- ❌ Pas de TTL/expiration automatique (doivent être gérés manuellement)
- ❌ Pollue l'espace des options WordPress
- ❌ Dépend de la base de données (si DB down = no rate limit)

### Complexité d'implémentation

```
⏱️ Temps: 30 minutes
📝 Lignes ajoutées: ~20
⚠️ Risque: Faux sentiment de sécurité
```

### Verdict

🔴 **À ÉVITER** — N'élimine pas vraiment les race conditions.

---

## Option 2️⃣ : Redis (Production)

### Code

```php
private static function acquire_lock( string $lock_key, int $timeout ): bool {
    // Utiliser une librairie Redis
    $redis = new Redis();
    $redis->connect( 'localhost', 6379 );
    
    // SET avec NX (only set if Not eXists) est ATOMIQUE
    // Retourne true si le verrou a été acquis, false sinon
    return $redis->set( 
        $lock_key, 
        wp_hash( uniqid() ),
        [ 'EX' => $timeout, 'NX' ]  // Atomic + expiration
    );
}

private static function release_lock( string $lock_key ): void {
    $redis = new Redis();
    $redis->connect( 'localhost', 6379 );
    $redis->del( $lock_key );
}
```

### ✅ Avantages

- ✅ **VRAIMENT ATOMIQUE** (Redis SET NX est une opération atomique)
  ```
  Redis traite chaque commande séquentiellement
  SET key value NX EX 5 = une seule opération, pas de race window
  ```

- ✅ **Automatiquement distribué** (si cluster Redis)
- ✅ **Très rapide** (< 1ms par opération)
- ✅ **Expiration automatique** (EX = seconds)
- ✅ **Pas de dépendance base de données**
- ✅ **Production-grade** (utilisé par des millions de sites)
- ✅ **Évite les verrous orphelins** (expiration auto)
- ✅ **Support des transactions** (si besoin complexe plus tard)
- ✅ Possibilité de cache multi-couche (Redis + transients)

### ❌ Inconvénients

- ❌ **Dépendance externe** : Redis doit être installé et running
- ❌ **Coût infrastructure** : Redis prend de la RAM (minimum 256MB)
- ❌ **Latence réseau** : ~1ms par operation (vs 0.1ms pour transients)
- ❌ **Complexité opérationnelle** : Redis à monitorer, backup, scaling
- ❌ **Configuration requise** : Ajouter les infos de connexion Redis
- ❌ **Single point of failure** : Si Redis down = plus de rate limiting
- ❌ **Incompatible avec certains hébergements** (shared hosting basic)
- ❌ **Dépend d'une librairie PHP Redis** :
  ```php
  // Dépendance supplémentaire à installer
  composer require predis/predis
  ```

### Complexité d'implémentation

```
⏱️ Temps: 4-6 heures
  - 1h: Installation Redis / vérifier disponibilité
  - 1h: Intégration librairie Predis/phpredis
  - 2h: Refactor code rate limiter
  - 1-2h: Tests + gestion erreurs (Redis down)

📝 Lignes ajoutées: ~150
  - Error handling (Redis unavailable)
  - Fallback à transients si Redis down
  - Configuration (host, port, password)

⚠️ Risque: Moyen (Redis stable, mais dépendance externe)
```

### Exemple complet

```php
class Alert404_RateLimiter {
    private static function get_redis() {
        static $redis = null;
        
        if ( $redis === null ) {
            try {
                $redis = new Predis\Client( [
                    'scheme'   => 'tcp',
                    'host'     => defined( 'REDIS_HOST' ) ? REDIS_HOST : 'localhost',
                    'port'     => defined( 'REDIS_PORT' ) ? REDIS_PORT : 6379,
                    'password' => defined( 'REDIS_PASSWORD' ) ? REDIS_PASSWORD : null,
                ] );
                $redis->ping();  // Vérifier connexion
            } catch ( Throwable $e ) {
                Alert404_Logger::log_error( 'Redis unavailable: ' . $e->getMessage() );
                return null;  // Fallback à transients
            }
        }
        
        return $redis;
    }
    
    private static function acquire_lock( string $lock_key, int $timeout ): bool {
        $redis = self::get_redis();
        
        if ( $redis === null ) {
            // Fallback à transients si Redis indisponible
            return self::acquire_lock_transient( $lock_key, $timeout );
        }
        
        // SET lock_key unique_value EX timeout NX
        // Atomique, expiration auto, only if not exists
        return (bool) $redis->set( 
            $lock_key,
            wp_hash( uniqid() ),
            'EX', $timeout,
            'NX'
        );
    }
    
    private static function release_lock( string $lock_key ): void {
        $redis = self::get_redis();
        
        if ( $redis ) {
            try {
                $redis->del( $lock_key );
            } catch ( Throwable $e ) {
                // Silencieusement échouer, verrou expirera anyway
            }
        }
    }
}
```

### Verdict

✅ **RECOMMANDÉ pour production** — Vrai atomicité, performant, robuste.

**Mais** : Nécessite infrastructure Redis (pas possible en shared hosting basic).

---

## Option 3️⃣ : Simplifier (MVP - No Locks)

### Concept

Abandonner complètement les verrous. À la place, utiliser une approche **optimiste** :

- ✅ Laisser les deux requêtes passer
- ✅ Incrémenter le compteur APRÈS
- ❌ Accepter un léger dépassement du rate limit (normal en concurrent)

```php
class Alert404_RateLimiter {
    public static function check_and_increment( string $ip ): bool {
        $options     = get_option( '404_alert_options', array() );
        $cooldown    = $options['ip_cooldown'] ?? 300;
        $daily_limit = $options['daily_limit'] ?? 500;

        // VÉRIFICATION SIMPLE (pas de verrou)
        $ip_key  = '404_alert_ip_' . wp_hash( $ip );
        $last_ip = get_transient( $ip_key );
        
        if ( $last_ip !== false && ( time() - (int) $last_ip ) < $cooldown ) {
            Alert404_Logger::log_rate_limit_ip( $ip, $cooldown );
            return false;  // Bloquer
        }

        // INCRÉMENTER SANS VÉRIFICATION ATOMIQUE
        $day_key = '404_alert_global_' . gmdate( 'Y-m-d' );
        $count   = (int) get_transient( $day_key );
        
        if ( $count >= $daily_limit ) {
            Alert404_Logger::log_rate_limit_daily( $daily_limit );
            return false;  // Bloquer
        }

        // ÉCRIRE (sans verrou, race condition OK)
        set_transient( $ip_key, time(), $cooldown );
        set_transient( $day_key, $count + 1, 86400 );
        
        return true;  // Permettre
    }
}
```

### ✅ Avantages

- ✅ **Extrêmement simple** (~50 lignes, très lisible)
- ✅ **Zéro dépendance** (WordPress transients uniquement)
- ✅ **Très rapide** (pas de verrous, pas de spin-wait)
- ✅ **Acceptable en 99% des cas** (race conditions rares en pratique)
- ✅ **Facile à tester** (pas de verrous complexes)
- ✅ **Compatible partout** (shared hosting, pas Redis)
- ✅ **Faible surface d'erreur** (moins de code = moins de bugs)
- ✅ **Tolérant à MySQL down** (pas de verrous DB)

### ❌ Inconvénients

- ❌ **Pas vraiment atomique** (race conditions possibles)
  ```
  Exemple: 10 requêtes simultanées d'une même IP
  
  Limite quotidienne = 500
  Compteur actuel = 499
  
  Requête 1: READ (499) → [RACE] → WRITE (500) ✅ Passe
  Requête 2: READ (499) → [RACE] → WRITE (500) ✅ Passe (devrait être bloquée!)
  Requête 3: READ (499) → [RACE] → WRITE (500) ✅ Passe (devrait être bloquée!)
  ...
  Requête 10: READ (499) → [RACE] → WRITE (500) ✅ Passe (devrait être bloquée!)
  
  Résultat: 501-510 emails au lieu de 500
  ```

- ❌ **Dépassement légal** : Avec 100 requêtes/sec concurrentes, peut envoyer 505-510 emails au lieu de 500
- ❌ **Pas idéal pour limite IP stricte** : Plusieurs requêtes d'une même IP peuvent passer quasi-simultanément
- ❌ **Pas de garantie de sécurité** : Dépend de la tolérance au dépassement

### Complexité d'implémentation

```
⏱️ Temps: 1-2 heures
  - 30 min: Refactor code
  - 30 min: Tests basiques
  - 30 min: Documentation du comportement "best effort"

📝 Lignes ajoutées: ~30 (suppression de ~150 lignes de verrous)
📝 Lignes supprimées: ~150 (tous les verrous)

⚠️ Risque: Faible (logique simple, facile à comprendre)
```

### Verdict

✅ **RECOMMANDÉ pour MVP/start** — Simple, performant, acceptable.

**Limitation** : Dépassement possible du rate limit en cas de forte concurrence (acceptable pour 90% des cas).

---

## 📊 Comparaison Directe

| Critère | Option 1 (WordPress) | Option 2 (Redis) | Option 3 (Simple) |
|---------|----------------------|------------------|-------------------|
| **Atomicité** | ❌ Non | ✅ Oui | ⚠️ Partielle |
| **Vraie sécurité** | ❌ Faux | ✅ Oui | ⚠️ "Best effort" |
| **Dépendances** | 0 | 1 (Redis) | 0 |
| **Latence** | 1-5ms | 1ms | 0.5-1ms |
| **Complexité code** | 20 lignes | 150 lignes | 30 lignes |
| **Complexité ops** | Minimal | Élevée (Redis) | Minimal |
| **Compatible shared hosting** | ✅ Oui | ❌ Rarement | ✅ Oui |
| **Performant** | ⚠️ Moyen | ✅ Excellent | ✅ Excellent |
| **Production ready** | ❌ Non | ✅ Oui | ⚠️ Presque |
| **Temps implémentation** | 30 min | 4-6h | 1-2h |
| **Risque bugs** | 🔴 Moyen | 🟢 Bas | 🟢 Bas |

---

## 🎯 Recommandation par Contexte

### Si c'est un MVP (< 100 visiteurs/jour)

```
Utiliser: Option 3 (Simple)

Raison:
- Pas besoin de vrai verrou atomique
- Dépassement minor du rate limit OK
- Zéro dépendance externe
- Code simple, facile à refactor plus tard
```

### Si c'est production (100-10k visiteurs/jour)

```
Utiliser: Option 2 (Redis)

Raison:
- Atomicité garantie
- Performant sous charge
- Production-grade
- Fallback à Option 3 si Redis down
```

### Si Redis NOT disponible et production requise

```
Utiliser: Option 3 (Simple) + monitoring

Raison:
- Meilleur que rien
- Ajouter alertes si rate limit dépassé
- Prévoir refactor vers Redis quand possible
```

---

## 💡 Approche Hybride Recommandée

**Combiner Option 2 + Option 3** :

```php
class Alert404_RateLimiter {
    private static function check_and_increment( string $ip ): bool {
        // Essayer Redis en priorité
        if ( self::has_redis() ) {
            return self::check_with_redis( $ip );
        }
        
        // Fallback à logique simple
        return self::check_simple( $ip );
    }
    
    private static function has_redis(): bool {
        // Vérifier que Redis est dispo
        $redis = self::get_redis();
        return $redis !== null;
    }
    
    private static function check_with_redis( string $ip ): bool {
        // Verrou atomique avec Redis
        // Vrai rate limiting garanti
    }
    
    private static function check_simple( string $ip ): bool {
        // Fallback simple sans verrou
        // Dépassement acceptable
    }
}
```

**Avantages** :
- ✅ Production-grade si Redis available
- ✅ Fallback acceptable si Redis down
- ✅ Progressive enhancement
- ✅ Pas de breaking change pour shared hosting

---

## 🏆 Mon Avis

**Pour 404-alert en 2026** :

1. **Court terme (Semaine 1-2)** : Utiliser **Option 3 (Simple)**
   - Eliminer le code de verrou cassé
   - Rendre le rate limiter "best effort"
   - Ajouter documentation sur le dépassement acceptable
   - Gain: Code 80% plus simple, même sécurité pour 90% des cas

2. **Moyen terme (Mois 2-3)** : Implémenter **Option 2 (Redis)**
   - Si le projet prend en charge
   - Faire fallback à Option 3
   - Configuration Redis optionnelle

3. **Ne pas toucher à Option 1** : WordPress Options ne sont pas atomiques, c'est une fausse route.

---

**Questions ?** Je peux écrire le code complet de n'importe quelle option.
