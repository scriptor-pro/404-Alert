# ⚡ Redis Quick Start (5 minutes)

**TL;DR** pour mettre en place Redis rapidement.

---

## 1. Installer Redis

### Ubuntu/Debian

```bash
sudo apt-get update
sudo apt-get install redis-server php-redis

# Démarrer
sudo systemctl start redis-server
sudo systemctl enable redis-server
```

### macOS

```bash
brew install redis php@8.2-pecl-redis

# Démarrer
redis-server
```

### Windows (WSL2)

```bash
wsl
sudo apt-get install redis-server
sudo service redis-server start
```

### Vérification

```bash
redis-cli ping
# Résultat: PONG ✅
```

---

## 2. Configurer WordPress

**Aucune configuration requise!** Le plugin utilise localhost:6379 par défaut.

Optionnel (wp-config.php) :

```php
define( 'ALERT404_REDIS_HOST', 'localhost' );
define( 'ALERT404_REDIS_PORT', 6379 );
define( 'ALERT404_REDIS_PASSWORD', null );  // Si password Redis
```

---

## 3. Vérifier que ça fonctionne

```bash
# Créer une 404
curl https://monsite.local/nonexistent

# Vérifier dans Redis
redis-cli KEYS 404_alert*
# Doit afficher: 404_alert_ip_abc123, 404_alert_global_2026-04-09
```

---

## 4. Vérifier les logs

```bash
tail -f wp-content/debug.log | grep -i redis
```

**Résultats possibles** :

```
Rien = ✅ Redis connecté et fonctionnel
"redis_unavailable" = ⚠️ Redis down, fallback à transients OK
"redis_error" = 🔴 Erreur lors de l'utilisation
```

---

## 🎯 C'est tout!

Le plugin est **automatiquement connecté** à Redis.

Rate limiting est maintenant :
- ✅ **Atomique** (plus de race conditions)
- ✅ **Rapide** (< 1ms vs 5-10ms avant)
- ✅ **Fiable** (avec fallback si Redis down)

---

## 🔧 Troubleshooting (2 minutes)

### "Connection refused"

```bash
# Vérifier que Redis est running
redis-cli ping

# Si erreur, démarrer Redis
redis-server

# Ou sur Linux
sudo systemctl start redis-server
```

### "Extension not loaded"

```bash
# Vérifier
php -m | grep redis

# Si absent, installer
# Ubuntu
sudo apt-get install php-redis

# macOS
brew install php@8.2-pecl-redis
```

### "Rate limiting doesn't work"

```bash
# Vérifier que le plugin est activé
wp plugin list | grep 404-alert

# Vérifier que rate limiter est appelé
tail -f wp-content/debug.log | grep rate_limit
```

---

## 📈 Monitoring (Optional)

```bash
# Voir les clés 404-alert
redis-cli KEYS 404_alert*

# Voir la limite quotidienne
redis-cli GET 404_alert_global_$(date +%Y-%m-%d)

# Monitoring en direct
redis-cli monitor
```

---

**Prêt ?** → Créez une 404 et vérifiez les logs. C'est tout! 🚀
