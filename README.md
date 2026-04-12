# 404 Alert — Plugin WordPress

Un plugin léger et autonome qui envoie un email à l'administrateur chaque fois qu'un visiteur accède à une page introuvable (erreur 404).

## Caractéristiques

- 📧 **Email instantané** : notifications HTML à chaque 404 détecté
- 🛡️ **Rate limiting** : protection contre les abus (par IP + limite quotidienne)
- ⚙️ **Configuration simple** : page de réglages WordPress native
- 🚀 **Léger** : zéro dépendance externe, WordPress core uniquement
- 🔒 **Sécurisé** : sanitization, protection XSS, validation stricte

## Installation rapide

**Voir [INSTALL.md](./INSTALL.md) pour les instructions détaillées.**

Tl;dr :
1. Télécharger le ZIP ou cloner le repo
2. Placer dans `wp-content/plugins/`
3. Activer dans **Extensions**
4. Configurer dans **Réglages > 404 Alert**

## Prérequis

- **WordPress** : 5.9+
- **PHP** : 8.1+ (8.2+ recommandé)
- **Dépendances externes** : Aucune

## Documentation

- **[INSTALL.md](./INSTALL.md)** — Installation et configuration de base
- **[ARCHITECTURE.md](./ARCHITECTURE.md)** — Architecture technique et flux d'exécution
- **[CONTRIBUTING.md](./CONTRIBUTING.md)** — Contribution au développement

### Configuration avancée

- **[REDIS.md](./REDIS.md)** — Optimisation du rate limiting avec Redis (optionnel)
- **[SMTP.md](./SMTP.md)** — Configuration SMTP pour meilleure délivrabilité (optionnel)

### Déploiement

- **[CONFIGURATION-PRODUCTION.md](./CONFIGURATION-PRODUCTION.md)** — Checklist production
- **[WORDPRESS-ORG.md](./WORDPRESS-ORG.md)** — Conformité WordPress.org et publication

## Utilisation

Une fois activé, le plugin détecte automatiquement les 404 et envoie un email pour chaque occurrence (dans les limites configurées).

### Email exemple

```
Sujet: 404 sur Example Site — /page-inexistante

Corps HTML:
  404 détectée sur Example Site
  
  URL: https://example.com/page-inexistante
  Referer: https://google.com
  IP: 192.168.1.100
  User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)...
  
  [Données complètes en JSON pour débogage]
```

### Configuration de base

Réglages disponibles dans **Réglages > 404 Alert** :

| Paramètre | Défaut | Description |
|-----------|--------|-------------|
| Email | Admin email | Destinataire des notifications |
| Limite quotidienne | 500 | Max emails/jour |
| Délai par IP | 300s | Cooldown entre 2 emails même IP |

## Architecture

Structure minimale :

```
404-alert/
├── 404-alert.php           # Bootstrap & hooks
├── includes/               # Classes principales
│   ├── class-detector.php       # Détection 404
│   ├── class-rate-limiter.php   # Rate limiting
│   ├── class-mailer.php         # Envoi email
│   ├── class-settings.php       # Page réglages
│   ├── class-logger.php         # Logging
│   └── ... (autres classes)
└── templates/              # Templates (optionnel)
```

**Voir [ARCHITECTURE.md](./ARCHITECTURE.md) pour les détails.**

## Sécurité

✅ **SQL Injection** : Préparé avec `$wpdb->prepare()`  
✅ **XSS** : Échappé avec `esc_html()`, `esc_attr()`  
✅ **CSRF** : Nonces WordPress  
✅ **Authorization** : `current_user_can('manage_options')`  

Voir [WORDPRESS-ORG.md](./WORDPRESS-ORG.md) pour la conformité complète.

## Dépannage

**Les emails ne sont pas reçus ?**
1. Vérifier l'email destination dans **Réglages > 404 Alert**
2. Vérifier que la limite quotidienne n'est pas atteinte
3. Vérifier la configuration SMTP de votre site (WP Mail SMTP, etc.)

**Rate limit pas fonctionnel ?**
- Sans Redis : basé sur WordPress transients (peut perdre data en redémarrage)
- Solution : Installer Redis (voir [REDIS.md](./REDIS.md))

**Plus de questions ?**
Consulter [INSTALL.md](./INSTALL.md) section Dépannage ou ouvrir une issue.

## Développement

Voir [CONTRIBUTING.md](./CONTRIBUTING.md) pour contribuer au code.

**Tests :**
```bash
composer install  # Installer dev dependencies
vendor/bin/phpcs includes/ 404-alert.php  # Lint
vendor/bin/phpstan analyse  # Static analysis
vendor/bin/phpunit  # Tests unitaires
```

## Licence

**GPL v2 ou ultérieur**

Voir [LICENSE](./LICENSE) ou https://www.gnu.org/licenses/gpl-2.0.html

## Roadmap

- ✅ Détection 404 + Email
- ✅ Rate limiting (IP + global)
- ✅ Redis support (optionnel)
- ✅ SMTP support (optionnel)
- ✅ Tests unitaires
- ⏳ Dashboard statistiques (futur)
- ⏳ Webhook support (futur)

---

**Made with ❤️ for WordPress**
