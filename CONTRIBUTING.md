# Contribution au projet 404 Alert

Merci de votre intérêt pour contribuer à 404 Alert ! Ce document explique comment contribuer efficacement.

## Code de conduite

En participant à ce projet, vous acceptez de traiter tous les contributeurs avec respect et bienveillance. Nous appliquons zéro tolérance à l'égard du harcèlement ou des commentaires discriminatoires.

## Comment contribuer

### Signaler des bugs

Avant de signaler un bug :

1. Vérifiez que le bug n'a pas déjà été signalé
2. Testez avec `WP_DEBUG` activé pour voir les erreurs
3. Incluez les logs de `/wp-content/debug.log`

**Titre du bug** : Décrivez le problème en une phrase

```
[BUG] Les emails ne sont pas envoyés si le domaine du site contient un tiret
```

**Description** :

- Étapes pour reproduire
- Comportement observé
- Comportement attendu
- Environnement (PHP version, WP version)
- Logs d'erreur

### Proposer des améliorations

Pour les demandes de nouvelles fonctionnalités :

**Titre** : Soyez spécifique

```
[FEATURE] Ajouter un webhook Slack pour les 404 suspectes
```

**Description** :

- Quel est le problème que cela résout ?
- Proposez-vous une solution ?
- Cas d'utilisation concrets

### Soumettre du code

1. **Forker et cloner** le dépôt

   ```bash
   git clone https://github.com/votre-username/404-alert.git
   cd 404-alert
   ```

2. **Créer une branche** avec un nom descriptif

   ```bash
   git checkout -b feature/slack-integration
   # ou
   git checkout -b fix/email-encoding-issue
   ```

3. **Installer les dépendances dev**

   ```bash
   composer install
   ```

4. **Vérifier la qualité du code**

   ```bash
   # Linting
   composer run lint

   # Analyse statique
   composer run stan

   # Fixer automatiquement
   composer run lint-fix
   ```

5. **Écrire des tests** (voir Tests section)

6. **Commiter avec des messages clairs**

   ```bash
   git commit -m "feat: ajouter intégration Slack pour notifications

   - Ajouter un nouveau champ de configuration pour le webhook URL
   - Implémenter Alert404_SlackNotifier
   - Tests unitaires pour la classe
   - Documentation de l'utilisation dans README"
   ```

7. **Push et créer une Pull Request**
   ```bash
   git push origin feature/slack-integration
   ```

## Style de code

Le projet utilise [WordPress PHP Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/) avec PHPCS.

### Résumé des conventions

- **Indentation** : TAB (pas d'espaces)
- **Noms de classes** : `Alert404_Fonction` (préfixe `Alert404_`)
- **Noms de variables** : `$snake_case`
- **Constantes** : `ALERT404_CONSTANT`
- **Noms de hooks** : `404_alert_hook_name`
- **PHPDoc** : Sur toutes les méthodes publiques et privées

### Exemple de méthode bien formée

```php
/**
 * Valide une adresse IP
 * Supporte les proxies avec HTTP_X_FORWARDED_FOR
 *
 * @param string $ip_raw IP brute à valider
 * @return bool True si l'IP est valide
 */
private static function is_valid_ip(string $ip_raw): bool {
	return filter_var($ip_raw, FILTER_VALIDATE_IP) !== false;
}
```

## Tests

Le projet utilise PHPUnit pour les tests unitaires.

### Exécuter les tests

```bash
composer test
```

### Écrire un test

```php
<?php
class Test_Rate_Limiter extends WP_UnitTestCase {
	public function test_ip_cooldown_blocks_second_request() {
		// Première requête : autorisée
		$result1 = Alert404_RateLimiter::check_and_increment('192.168.1.1');
		$this->assertTrue($result1);

		// Deuxième requête immédiate : bloquée
		$result2 = Alert404_RateLimiter::check_and_increment('192.168.1.1');
		$this->assertFalse($result2);
	}
}
```

## Documentation

Toutes les changes doivent mettre à jour la documentation pertinente :

- **README.md** : Vue d'ensemble et installation
- **ARCHITECTURE.md** : Si vous modifiez l'architecture
- **CHANGELOG.md** : Chaque changement notable
- **PHPDoc** : Dans le code source

## Processus de review

1. **Checks automatiques** : Doivent passer (lint, tests, analysis)
2. **Code review** : Au moins une approbation d'un mainteneur
3. **Tests** : Coverage > 70% pour les nouvelles fonctionnalités
4. **Documentation** : Tous les fichiers doivent être à jour
5. **Merge** : Après approbation et passage des checks

## Branche et releases

- **main** : Code stable, production-ready
- **develop** : Branche de développement (si applicable)
- **feature/** : Nouvelles fonctionnalités
- **fix/** : Correctifs de bugs
- **chore/** : Tâches de maintenance

## Questions ?

Ouvrez une issue avec le label `[QUESTION]` ou contactez les mainteneurs.

## Licence

En contribuant, vous acceptez que votre code soit sous licence GPL-2.0-or-later, identique au projet.

---

**Merci de contribuer à 404 Alert ! 🎉**
