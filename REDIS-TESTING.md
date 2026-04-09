# ✅ Checklist de Test Redis

Guide de validation de l'implémentation Redis.

---

## Phase 1 : Vérification de l'Installation

### ✅ Prérequis

```bash
# [ ] Redis est installé?
redis-cli ping
# Résultat attendu: PONG

# [ ] Extension Redis PHP est chargée?
php -m | grep redis
# Résultat attendu: redis

# [ ] Port 6379 est accessible?
netstat -tlnp | grep redis
# Résultat attendu: tcp ... 127.0.0.1:6379 LISTEN
```

### ✅ Configuration WordPress

```bash
# [ ] wp-config.php contient les constantes (optionnel)?
grep ALERT404_REDIS wp-config.php

# [ ] Ou version par défaut (localhost:6379)?
# Pas de constantes = défaut utilisé
```

---

## Phase 2 : Connexion Redis

### ✅ Via les logs WordPress

```bash
# [ ] Vérifier les logs
tail -f wp-content/debug.log

# Chercher:
# - Pas de message = ✅ Connecté avec succès
# - "redis_unavailable" = ⚠️ Non connecté (fallback OK)
# - "redis_error" = 🔴 Erreur lors de l'utilisation
```

### ✅ Via Redis CLI

```bash
redis-cli

# [ ] Vérifier que les clés 404-alert sont créées
> KEYS 404_alert*
# Résultat: (empty list or set) → OK, pas encore de 404

# [ ] Vérifier la base de données utilisée
> DBSIZE
# Résultat: entier >= 0

# [ ] Vérifier la connexion
> INFO server
# Résultat: redis_version: ...
```

---

## Phase 3 : Test du Rate Limiting par IP

### Setup

```bash
# [ ] Configurer le cooldown court pour tester
wp option update 404_alert_options '{"ip_cooldown":5,"daily_limit":10}'
# Cooldown = 5 secondes, limite = 10/jour
```

### Test 1 : Requête unique autorisée

```bash
# [ ] Accéder à une page 404
curl https://monsite.com/nonexistent-page-1

# Vérifier les logs
tail -f wp-content/debug.log | grep rate_limit
# Résultat attendu: AUCUN log (pas de rate limit)

# Vérifier Redis
redis-cli GET 404_alert_ip_abc123
# Résultat: timestamp récent (e.g., 1712686800)
```

### Test 2 : Deuxième requête bloquée (cooldown actif)

```bash
# [ ] Accéder immédiatement à une autre page 404 (même IP)
curl https://monsite.com/nonexistent-page-2

# Vérifier les logs
tail -f wp-content/debug.log | grep rate_limit_ip
# Résultat attendu: "rate_limit_ip" avec IP et cooldown

# Email NE doit pas être envoyé
grep "email_sent" wp-content/debug.log | tail -1
# Résultat: timestamp du premier (pas du second)
```

### Test 3 : Après cooldown, requête autorisée

```bash
# [ ] Attendre 6 secondes (cooldown est 5)
sleep 6

# [ ] Accéder à une autre page 404
curl https://monsite.com/nonexistent-page-3

# Vérifier les logs
tail -f wp-content/debug.log | grep email_sent
# Résultat: nouveau "email_sent" log

# Vérifier Redis - TTL réinitialisé
redis-cli TTL 404_alert_ip_abc123
# Résultat: 5 (ou proche de 5)
```

### Test 4 : IPs différentes passent

```bash
# [ ] Simuler IP différente (depuis machine différente)
# OU utiliser curl avec header X-Forwarded-For
curl -H "X-Forwarded-For: 192.168.1.200" https://monsite.com/nonexistent-ip2

# Vérifier les logs
tail -f wp-content/debug.log | grep email_sent
# Résultat: nouvel email_sent (pas de rate limit)

# Vérifier Redis
redis-cli KEYS 404_alert_ip*
# Résultat: deux clés (une par IP)
```

---

## Phase 4 : Test du Rate Limiting Quotidien

### Setup

```bash
# [ ] Configurer limite très basse
wp option update 404_alert_options '{"ip_cooldown":1,"daily_limit":3}'
# 3 emails maximum par jour
```

### Test 1 : Sous la limite

```bash
# [ ] Créer 3 404 avec IPs différentes (ou attendre cooldown)
curl -H "X-Forwarded-For: 10.0.0.1" https://monsite.com/page-404-1
sleep 2
curl -H "X-Forwarded-For: 10.0.0.2" https://monsite.com/page-404-2
sleep 2
curl -H "X-Forwarded-For: 10.0.0.3" https://monsite.com/page-404-3

# Vérifier les logs
grep "email_sent" wp-content/debug.log | wc -l
# Résultat: 3 emails

# Vérifier Redis
redis-cli GET 404_alert_global_$(date +%Y-%m-%d)
# Résultat: 3
```

### Test 2 : Au-delà de la limite

```bash
# [ ] Créer une 4ème 404
sleep 2
curl -H "X-Forwarded-For: 10.0.0.4" https://monsite.com/page-404-4

# Vérifier les logs
grep "rate_limit_daily" wp-content/debug.log | tail -1
# Résultat: log avec "daily_limit: 3"

# Pas d'email envoyé
grep "email_sent" wp-content/debug.log | tail -1
# Résultat: toujours seulement 3

# Vérifier Redis
redis-cli GET 404_alert_global_$(date +%Y-%m-%d)
# Résultat: 4 (compteur a augmenté même si bloqué)
# C'est normal! Le compteur s'incrémente PUIS on vérifie
```

---

## Phase 5 : Test du Fallback (Sans Redis)

### Simulation

```bash
# [ ] Arrêter Redis
redis-cli SHUTDOWN
# OU
sudo systemctl stop redis-server

# [ ] Attendre quelques secondes
sleep 5

# [ ] Créer un 404
curl https://monsite.com/nonexistent-test
```

### Vérification

```bash
# [ ] Vérifier les logs
grep "redis_unavailable" wp-content/debug.log | tail -1
# Résultat: log indiquant Redis indisponible

# [ ] Rate limiting fonctionne toujours?
# Utiliser transients à la place
wp transient list | grep 404_alert
# Résultat: clés 404_alert_ip_* visibles

# [ ] Créer plusieurs 404 rapidement (même IP)
# Vérifier que le rate limit fonctionne (imparfaitement mais OK)
```

### Redémarrer Redis

```bash
# [ ] Redémarrer Redis
redis-cli
# OU
sudo systemctl start redis-server

# [ ] Attendre quelques secondes
# [ ] Créer un 404

# Vérifier les logs
grep "redis" wp-content/debug.log | tail -1
# Résultat: pas de "redis_unavailable" = ✅ Reconnecté
```

---

## Phase 6 : Test de Performance

### Mesure sans Redis (transients seuls)

```bash
# [ ] Arrêter Redis (fallback)
sudo systemctl stop redis-server

# [ ] Apache Bench pour 100 requêtes 404
ab -n 100 -c 10 https://monsite.com/nonexistent

# Noter le temps: __________ ms/requête
```

### Mesure avec Redis (local)

```bash
# [ ] Redémarrer Redis
sudo systemctl start redis-server

# [ ] Attendre que le plugin se reconnecte
sleep 5

# [ ] Même test
ab -n 100 -c 10 https://monsite.com/nonexistent

# Noter le temps: __________ ms/requête
```

### Comparaison

```
Sans Redis (transients): _______ ms/requête
Avec Redis (local):      _______ ms/requête
Gain:                    ______% plus rapide

Attendu: 5-10x plus rapide avec Redis local
```

---

## Phase 7 : Test du Configuration Personnalisée

### Test 1 : Authentification

```bash
# [ ] Configurer password Redis
redis-cli CONFIG SET requirepass "test_password_123"

# [ ] Ajouter à wp-config.php
define( 'ALERT404_REDIS_PASSWORD', 'test_password_123' );

# [ ] Créer un 404
curl https://monsite.com/page-404

# Vérifier les logs
grep "redis" wp-content/debug.log | tail -1
# Résultat: pas d'erreur = ✅ Connecté
```

### Test 2 : Port personnalisé

```bash
# [ ] Configurer Redis sur port différent
redis-server --port 6380

# [ ] Ajouter à wp-config.php
define( 'ALERT404_REDIS_PORT', 6380 );

# [ ] Créer un 404
curl https://monsite.com/page-404

# Vérifier
grep "redis" wp-content/debug.log | tail -1
# Résultat: pas d'erreur = ✅ Port correct
```

### Test 3 : Base de données différente

```bash
# [ ] Configurer DB 1 au lieu de 0
define( 'ALERT404_REDIS_DB', 1 );

# [ ] Créer un 404
curl https://monsite.com/page-404

# Vérifier
redis-cli -n 1 KEYS 404_alert*
# Résultat: clés visibles dans DB 1 (pas DB 0)
```

---

## Phase 8 : Test d'Atomicité (Critique!)

### Simulation de race condition

```bash
# [ ] Créer un script qui fait 100 requêtes simultanées
cat > test_race.sh << 'EOF'
#!/bin/bash
for i in {1..100}; do
  curl -s https://monsite.com/nonexistent-race-$i &
done
wait
EOF

chmod +x test_race.sh

# [ ] Exécuter
./test_race.sh

# Configurer pour max 5 emails/jour
wp option update 404_alert_options '{"daily_limit":5}'

# [ ] Vérifier
redis-cli GET 404_alert_global_$(date +%Y-%m-%d)
# Résultat: devrait être <= 6 (5 autorisés + 1 dû à race)
# Avec Redis: ✅ Atomique, exactement 5
# Avec transients: ⚠️ Possible dépassement à 10+
```

---

## Phase 9 : Test de Monitoring

### Redis INFO

```bash
# [ ] Vérifier les stats Redis
redis-cli INFO stats

# Chercher:
# - total_connections_received: __
# - total_commands_processed: __
# - instantaneous_ops_per_sec: __
```

### Mémoire Redis

```bash
# [ ] Vérifier l'utilisation mémoire
redis-cli INFO memory

# Chercher:
# - used_memory_human: __
# - used_memory_peak_human: __
# - maxmemory: __ (si configuré)

# Résultat attendu: < 10 MB pour 404-alert
```

### Clés 404-alert

```bash
# [ ] Compter les clés
redis-cli KEYS 404_alert* | wc -l

# Résultat attendu: 
# - IPs uniques du jour (< 1000 généralement)
# - 1 compteur global quotidien
```

---

## Phase 10 : Tests Négatifs (Erreurs)

### Test 1 : Connexion échouée

```bash
# [ ] Tuer Redis
redis-cli SHUTDOWN

# [ ] Créer plusieurs 404
for i in {1..5}; do
  curl https://monsite.com/nonexistent-$i
done

# Vérifier les logs
grep "redis_unavailable\|redis_error" wp-content/debug.log

# Résultat: logs d'erreur présents
# Rate limiting fonctionne toujours: ✅ Fallback OK
```

### Test 2 : Timeout

```bash
# [ ] Configurer timeout très court
define( 'ALERT404_REDIS_TIMEOUT', 0.001 );  # 1ms

# [ ] Redémarrer Redis et créer 404
redis-cli
sudo systemctl start redis-server
curl https://monsite.com/nonexistent

# Vérifier
grep "redis_unavailable" wp-content/debug.log
# Résultat: timeout message → fallback ✅
```

### Test 3 : Mot de passe incorrect

```bash
# [ ] Configurer password incorrect
define( 'ALERT404_REDIS_PASSWORD', 'wrong_password' );

# [ ] Créer 404
curl https://monsite.com/nonexistent

# Vérifier
grep "redis_unavailable" wp-content/debug.log | grep -i "auth"
# Résultat: authentification échouée → fallback ✅
```

---

## ✅ Validation Finale

### Checklist Complète

```
Phase 1: Installation
  [ ] Redis installé
  [ ] Extension Redis chargée
  [ ] Port accessible

Phase 2: Connexion
  [ ] Logs OK ou fallback OK
  [ ] Redis CLI répond

Phase 3: Rate Limiting IP
  [ ] Requête 1 autorisée
  [ ] Requête 2 bloquée (cooldown)
  [ ] Requête 3 autorisée (après cooldown)
  [ ] IPs différentes passent

Phase 4: Rate Limiting Quotidien
  [ ] Sous limite: autorisé
  [ ] Au-delà limite: bloqué
  [ ] Compteur incrémente correctement

Phase 5: Fallback
  [ ] Sans Redis, fonctionne toujours
  [ ] Avec Redis, performances meilleures

Phase 6: Performance
  [ ] Avec Redis: < 5ms/requête
  [ ] 5-10x plus rapide qu'avant

Phase 7: Configuration
  [ ] Password: ✅
  [ ] Port personnalisé: ✅
  [ ] DB personnalisée: ✅

Phase 8: Atomicité
  [ ] Race condition mitigée
  [ ] Limite respectée sous charge

Phase 9: Monitoring
  [ ] Clés visibles en Redis CLI
  [ ] Mémoire < 10MB
  [ ] Stats accessibles

Phase 10: Erreurs
  [ ] Fallback en cas d'erreur
  [ ] Logs précis pour debugging
```

---

## 🎯 Résumé des Résultats

```
Date: ___________
Testeur: ___________

Redis disponible: [ ] Oui  [ ] Non
Fallback testé: [ ] Oui  [ ] Non

Rate Limiting IP: [ ] ✅ OK  [ ] ⚠️ Partiel  [ ] 🔴 Échoué
Rate Limiting Quotidien: [ ] ✅ OK  [ ] ⚠️ Partiel  [ ] 🔴 Échoué
Performance: [ ] ✅ Amélioré  [ ] ⚠️ Similaire  [ ] 🔴 Dégradé
Atomicité: [ ] ✅ Garantie  [ ] ⚠️ Partielle  [ ] 🔴 Échouée

Problèmes trouvés:
1. ___________
2. ___________
3. ___________

Verdict final: [ ] ✅ PRÊT POUR PRODUCTION
              [ ] ⚠️  À REVOIR
              [ ] 🔴 BLOQUER
```

---

**Tous les tests passent ?** → Prêt pour production! 🚀
