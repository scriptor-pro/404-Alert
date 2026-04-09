# 🔴 Configuration Redis pour 404 Alert

Guide complet pour configurer Redis avec le plugin 404 Alert.

---

## 📋 Prérequis

### Serveur

- **Redis 4.0+** installé et running
- **Extension PHP Redis** (`php-redis` ou `php-pecl-redis`)
- Accès au serveur Redis (localhost par défaut)

### Vérification

```bash
# Vérifier que Redis est installé
redis-cli ping
# Doit retourner: PONG

# Vérifier que l'extension PHP Redis est chargée
php -m | grep redis
# Doit afficher: redis
```

---

## 🚀 Installation Rapide

### Option 1 : Configuration par défaut (localhost)

Redis s'exécute **automatiquement** avec la configuration par défaut :

```php
// Aucune configuration requise
// Le plugin tente de se connecter à localhost:6379
```

Vérifiez les logs pour voir si Redis est connecté :

```bash
tail -f wp-content/debug.log
# Doit montrer: "[404-Alert] ... redis_unavailable" (si non connecté)
# Ou rien si connecté avec succès
```

### Option 2 : Configuration personnalisée (wp-config.php)

Ajoutez ces constantes à `wp-config.php` :

```php
<?php
// wp-config.php

// Configuration Redis pour 404 Alert
define( 'ALERT404_REDIS_HOST', 'localhost' );      // Adresse du serveur Redis
define( 'ALERT404_REDIS_PORT', 6379 );             // Port (défaut: 6379)
define( 'ALERT404_REDIS_PASSWORD', null );         // Password si requise (ou omit)
define( 'ALERT404_REDIS_DB', 0 );                  // Numéro de la base (défaut: 0)
define( 'ALERT404_REDIS_TIMEOUT', 2 );             // Timeout connexion en secondes
```

**Exemple avec authentification** :

```php
define( 'ALERT404_REDIS_HOST', 'redis.example.com' );
define( 'ALERT404_REDIS_PORT', 6380 );
define( 'ALERT404_REDIS_PASSWORD', 'your_secure_password' );
define( 'ALERT404_REDIS_DB', 1 );
define( 'ALERT404_REDIS_TIMEOUT', 3 );
```

---

## 🏢 Déploiements Spécifiques

### Redis sur le même serveur (Plus commun)

```bash
# Installation sur Ubuntu/Debian
sudo apt-get install redis-server php-redis

# Démarrer Redis
sudo systemctl start redis-server
sudo systemctl enable redis-server  # Autostart

# Configuration wp-config.php
define( 'ALERT404_REDIS_HOST', 'localhost' );
define( 'ALERT404_REDIS_PORT', 6379 );
```

### Redis sur un serveur distant

```bash
# wp-config.php
define( 'ALERT404_REDIS_HOST', '192.168.1.100' );
define( 'ALERT404_REDIS_PORT', 6379 );
define( 'ALERT404_REDIS_TIMEOUT', 5 );  # Augmenter le timeout
```

### Redis avec authentification (Redis < 6.0)

```bash
# Redis configuration (/etc/redis/redis.conf)
requirepass your_password_here

# Restart Redis
sudo systemctl restart redis-server
```

```php
// wp-config.php
define( 'ALERT404_REDIS_PASSWORD', 'your_password_here' );
```

### Redis avec authentification (Redis 6.0+)

```bash
# Redis 6.0+ supporte les utilisateurs
# Configurer dans redis.conf
user default on >password +@all ~*
user alert404 on >alert404_password +get +set +incr +expire +del ~404_alert*

# Restart
sudo systemctl restart redis-server
```

```php
// wp-config.php
// Redis 6.0+ encore pas supporté directement
// Utiliser le password de l'utilisateur default
define( 'ALERT404_REDIS_PASSWORD', 'alert404_password' );
```

### Redis sur Heroku

```bash
# Redis Heroku addon
heroku addons:create heroku-redis:premium-0

# Récupérer l'URL
heroku config | grep REDIS_URL
# Résultat: redis://x:password@host:port
```

```php
// wp-config.php
$redis_url = parse_url( getenv( 'REDIS_URL' ) );

define( 'ALERT404_REDIS_HOST', $redis_url['host'] );
define( 'ALERT404_REDIS_PORT', $redis_url['port'] );
define( 'ALERT404_REDIS_PASSWORD', $redis_url['pass'] );
```

### Redis sur AWS ElastiCache

```bash
# Obtenir les infos du cluster
aws elasticache describe-cache-clusters --cache-cluster-id my-cluster
```

```php
// wp-config.php
define( 'ALERT404_REDIS_HOST', 'my-cluster.abc123.ng.0001.use1.cache.amazonaws.com' );
define( 'ALERT404_REDIS_PORT', 6379 );
define( 'ALERT404_REDIS_TIMEOUT', 3 );
```

**Avec Encryption in-transit** :

```php
// AWS ElastiCache avec AUTH
define( 'ALERT404_REDIS_PASSWORD', 'your_auth_token' );
```

### Upstash Redis (Serverless)

```bash
# Créer une BD Redis sur https://upstash.com
# Récupérer REST URL ou Redis URL
```

```php
// wp-config.php
// Upstash fourni une URL au format:
// redis://default:password@host:port

$upstash_url = getenv( 'UPSTASH_REDIS_URL' );
$parsed = parse_url( $upstash_url );

define( 'ALERT404_REDIS_HOST', $parsed['host'] );
define( 'ALERT404_REDIS_PORT', $parsed['port'] );
define( 'ALERT404_REDIS_PASSWORD', $parsed['pass'] );
```

---

## ✅ Vérification

### Test 1 : Redis est connecté?

```bash
# Vérifier les logs
tail -f wp-content/debug.log | grep -i redis

# Doit afficher:
# - Rien (succès silencieux)
# - OU "redis_unavailable" (échec, fallback à transients)
```

### Test 2 : Rate limiting fonctionne?

```bash
# Aller sur wp-admin > Settings > 404 Alert
# Voir la page de configuration

# Status: ✅ Redis connected
# OU
# Status: ⚠️ Redis unavailable, using transients (fallback)
```

### Test 3 : Via Redis CLI

```bash
# Se connecter à Redis
redis-cli

# Vérifier les clés 404-alert
redis> KEYS 404_alert*
# Doit afficher les clés de rate limit

# Voir une valeur
redis> GET 404_alert_ip_abc123
# Doit afficher un timestamp

# Voir le TTL
redis> TTL 404_alert_ip_abc123
# Doit afficher secondes restantes
```

---

## 🔧 Dépannage

### "Redis extension not loaded"

```bash
# Installer l'extension
# Ubuntu/Debian
sudo apt-get install php-redis

# CentOS/RHEL
sudo yum install php-pecl-redis

# macOS (avec Homebrew)
brew install redis
# Et pour PHP, modifier php.ini ou utiliser PECL
pecl install redis

# Redémarrer PHP-FPM
sudo systemctl restart php-fpm

# Vérifier
php -m | grep redis
```

### "Connection refused" (localhost)

```bash
# Vérifier que Redis est running
redis-cli ping
# Doit retourner: PONG

# Si non running, démarrer Redis
sudo systemctl start redis-server

# Ou sur macOS
redis-server /usr/local/etc/redis.conf

# Vérifier le port
netstat -tlnp | grep redis
```

### "Authentication failed"

```bash
# Vérifier que le password est correct
redis-cli -h localhost -p 6379 -a your_password ping

# Si erreur, vérifier redis.conf
grep requirepass /etc/redis/redis.conf

# Mettre à jour wp-config.php avec le bon password
define( 'ALERT404_REDIS_PASSWORD', 'the_correct_password' );
```

### "Connection timeout"

```bash
# Redis est peut-être sur un autre serveur
# Vérifier la connectivité
nc -zv redis_host 6379
# Doit afficher: Connection succeeded

# Augmenter le timeout
define( 'ALERT404_REDIS_TIMEOUT', 5 );  # Au lieu de 2
```

### Redis n'est pas utilisé (mode transients)

```bash
# Vérifier dans les logs
tail -f wp-content/debug.log | grep redis_unavailable

# Vérifier que l'extension Redis est chargée
php -i | grep redis

# Vérifier la configuration
wp option get 404_alert_options
# Doit montrer: redis_enabled => 1
```

---

## 📊 Monitoring Redis

### Affichage de l'utilisation

```bash
redis-cli info stats
# Affiche statistiques complètes
```

### Affichage des clés 404-alert

```bash
redis-cli
> KEYS 404_alert*
> DBSIZE
> MEMORY STATS
```

### Monitor en temps réel

```bash
redis-cli monitor
# Affiche toutes les commandes en temps réel
```

---

## 🔐 Sécurité

### Disable external access (important!)

```bash
# Dans /etc/redis/redis.conf
bind 127.0.0.1          # Seulement localhost
port 6379
protected-mode yes      # Activer la protection

# Redémarrer
sudo systemctl restart redis-server
```

### Utiliser un password fort

```bash
# Générer un password
openssl rand -hex 32

# Dans /etc/redis/redis.conf
requirepass your_generated_password_here

# Redémarrer
sudo systemctl restart redis-server
```

### Chiffrer la connexion (Redis 6+)

```bash
# redis.conf
tls-port 6380
tls-cert-file /path/to/redis.crt
tls-key-file /path/to/redis.key

# Redémarrer
sudo systemctl restart redis-server
```

```php
// wp-config.php
define( 'ALERT404_REDIS_PORT', 6380 );
define( 'ALERT404_REDIS_PASSWORD', 'password' );
// Note: l'extension Redis PHP doit supporter TLS
```

---

## 📈 Performance

### Cache warming

Le plugin n'a pas besoin de "warming". Les clés sont créées à la demande.

### TTL/Expiration

Les clés sont **automatiquement expirées** :

- **IP cooldown** : Durée du cooldown configuré (défaut: 300s)
- **Limite quotidienne** : Jusqu'à minuit UTC (48-86400s)

Aucune nettoyage manuel requis.

### Mémoire

Une 404 par seconde pendant 1 jour :

```
86,400 requêtes × ~50 bytes par clé = 4.3 MB
Acceptable même sur Redis petit
```

---

## ⏸️ Désactiver Redis (Fallback à transients)

Si Redis cause des problèmes, simplement **ne pas configurer** les constantes Redis.

Le plugin **fallback automatiquement** à transients WordPress :

```php
// Pas de ALERT404_REDIS_* définis
// → Redis non utilisé
// → Rate limiting via transients
// → Performance dégradée mais fonctionnel
```

---

## 🎯 Résumé Recommandé

### Production

```php
// wp-config.php
define( 'ALERT404_REDIS_HOST', 'localhost' );
define( 'ALERT404_REDIS_PORT', 6379 );
define( 'ALERT404_REDIS_PASSWORD', 'your_strong_password' );
define( 'ALERT404_REDIS_DB', 1 );  # Isoler 404-alert
define( 'ALERT404_REDIS_TIMEOUT', 2 );

// Vérifier dans les logs
// tail -f wp-content/debug.log | grep -i redis
```

### Développement local

```php
// Pas de configuration = localhost:6379 par défaut
// Redis optionnel, fallback à transients OK
```

### Hébergement partagé (Pas de Redis)

```php
// Pas de configuration Redis
// Utiliser transients simples (acceptable)
// Performance: -5% vs Redis
```

---

**Questions ?** Consulter les logs WordPress pour le diagnostic complet.
