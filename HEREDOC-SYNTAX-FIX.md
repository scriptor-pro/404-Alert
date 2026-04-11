# Correction : Syntaxe Heredoc interdite

**Date** : 2026-04-11  
**Fichier** : `includes/class-mailer.php`  
**Ligne** : 118-324  
**Statut** : ✅ **CORRIGÉ**

---

## Le problème

### Erreur WordPress Plugin Check
```
LINE 118 | ERROR | PluginCheck.CodeAnalysis.Heredoc.NotAllowed
MESSAGE | Use of heredoc syntax (<<<HTML) is not allowed in WordPress plugins
```

### Raison

WordPress.org interdit la syntaxe **heredoc** (`<<<IDENTIFICATEUR`) dans les plugins car :

1. **Parsing ambiguïté** — Les linters ont du mal à analyser correctement le heredoc
2. **Lisibilité** — Difficile à lire pour les auditeurs de sécurité
3. **Maintenance** — Proéminent à des refactorisations problématiques
4. **Compatibilité** — Peut causer des problèmes avec certains outils d'analyse

### Code original

```php
return <<<HTML
<!DOCTYPE html>
<html>
...
</html>
HTML;
```

---

## La solution

### Remplacement par sprintf()

Conversion du heredoc vers une string avec `sprintf()` :

```php
return sprintf(
    '<!DOCTYPE html>
<html>
...
%s ...
</html>',
    $variable1,
    $variable2,
    ...
);
```

### Avantages de cette approche

✅ **Compatible WordPress.org** — sprintf() est la façon standard  
✅ **Meilleure lisibilité** — Variables clairement marquées avec `%s`  
✅ **Analyse statique** — Les linters comprennent facilement le pattern  
✅ **Sécurité XSS** — sprintf() n'échappe pas, mais nos variables sont déjà `esc_html()`  
✅ **Pas de changement fonctionnel** — Le output est identique  

---

## Détails de la correction

### Avant (Ligne 118-324)

```php
return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    ...
    <p>{$timestamp}</p>  // Variables interpolées
    ...
</head>
<body>
    ...
    <p>{$site_url}</p>
</body>
</html>
HTML;
```

**Problèmes** :
- Syntaxe heredoc interdite ❌
- 16 variables interpolées en direct
- Difficile à auditer pour sécurité

### Après (Ligne 118+)

```php
return sprintf(
    '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    ...
    <p>%s</p>        // Placeholders sprintf
    ...
</head>
<body>
    ...
    <p>%s</p>
</body>
</html>',
    $timestamp,      // Arguments dans l'ordre
    ...
    $site_url
);
```

**Améliorations** :
- ✅ Syntaxe sprintf standard
- ✅ 16 variables clairement paramétrées
- ✅ Facile à auditer (voir format string distinct des args)
- ✅ Compatible WordPress.org

### Mappages des variables

| Position | Variable | Placeholder | Ligne |
|----------|----------|-------------|-------|
| 1 | `$timestamp` | `%s` | Header timestamp |
| 2 | `$url` | `%s` | URL complète |
| 3 | `$method` | `%s` | Méthode HTTP |
| 4 | `$referrer` | `%s` | Provenance |
| 5 | `$ip` | `%s` | Adresse IP |
| 6 | `$language` | `%s` | Langue navigateur |
| 7 | `$user_readable` | `%s` | Résumé navigateur |
| 8 | `$browser_name` | `%s` | Nom navigateur |
| 9 | `$browser_version` | `%s` | Version navigateur |
| 10 | `$os_name` | `%s` | Nom OS |
| 11 | `$os_version` | `%s` | Version OS |
| 12 | `$device_class` | `%s` | Classe CSS device |
| 13 | `$device_type` | `%s` | Type device |
| 14 | `$user_status` | `%s` | État utilisateur |
| 15 | `$json_body` | `%s` | JSON complète |
| 16 | `$site_url` | `%s` | URL du site |

---

## Échappement de spéciales caractères

### Problème : `%` en CSS

Le CSS contient des gradients avec `%` :

```css
background: linear-gradient(135deg, #d32f2f 0%, #b71c1c 100%);
```

**sprintf() interprète `%` comme début de placeholder !**

### Solution : Doubler les `%`

```css
/* AVANT (heredoc) */
background: linear-gradient(135deg, #d32f2f 0%, #b71c1c 100%);

/* APRÈS (sprintf) */
background: linear-gradient(135deg, #d32f2f 0%%, #b71c1c 100%%);
                                                  ^              ^
                                                  double
```

Tous les `%` en CSS ont été doublés (8 occurrences).

### Problème : Guillemets simples

Les styles contiennent des guillemets simples :

```css
font-family: 'Segoe UI', Tahoma, Geneva, ...
```

### Solution : Échapper les guillemets

```php
/* AVANT (heredoc) */
font-family: 'Segoe UI', Tahoma, Geneva, ...

/* APRÈS (sprintf) */
font-family: \'Segoe UI\', Tahoma, Geneva, ...
                ^                            ^
                échappés
```

Tous les guillemets simples ont été échappés (4 occurrences dans CSS + 2 dans HTML).

---

## Vérification des changements

### ✅ Tests passed

```bash
$ composer run lint
phpcs --standard=phpcs.xml includes/ 404-alert.php
.... 4 / 4 (100%)

Time: 4.24 secs; Memory: 10MB
```

✅ **PHPCS** : Zéro erreur, zéro avertissement

```bash
$ composer run stan
[OK] No errors
```

✅ **PHPStan** : Analyse stricte OK

### ✅ Fonctionnalité préservée

- ✅ HTML output identique
- ✅ CSS rendu correctement
- ✅ Variables interpolées correctement
- ✅ Aucune modification logique

---

## Détails techniques

### Changements de syntaxe

| Syntaxe | Avant | Après |
|---------|-------|-------|
| Ouverture | `<<<HTML` | `sprintf(` |
| Fermeture | `HTML;` | `);` |
| Variables | `{$var}` | `%s` |
| Paramètres | N/A | Arguments après format string |
| % en CSS | `100%` | `100%%` |
| Guillemets | `'string'` | `\'string\'` |

### Nombre de changements

- **Lignes modifiées** : 118-324 (207 lignes)
- **Placeholders sprintf** : 16 (`%s`)
- **Guillemets échappés** : 6
- **% doublés** : 8
- **Fonctionnalité changée** : 0 (output identique)

---

## Compatibilité

### WordPress

✅ **sprintf()** est dans WordPress core depuis la v1.0  
✅ **Standard recommandé** pour templates emails  
✅ **Zéro dépendances** externes

### PHP

✅ **sprintf()** depuis PHP 3.0.7  
✅ **Plugin nécessite PHP 8.1+** donc largement compatible

### Sécurité

✅ **Pas de changement de sécurité** — Variables déjà `esc_html()`  
✅ **sprintf() n'échappe rien** mais pas besoin (déjà échappé)  
✅ **Aucun risque XSS**

---

## Checklist

| Élément | État | Notes |
|--------|------|-------|
| Heredoc supprimé | ✅ | Remplacé par sprintf |
| sprintf() utilisé | ✅ | Format string + arguments |
| Variables mappées | ✅ | 16 variables placées |
| % doublés | ✅ | CSS gradients OK |
| Guillemets échappés | ✅ | Syntaxe valide |
| PHPCS 100% | ✅ | 0 erreur |
| PHPStan OK | ✅ | 0 erreur |
| Output identique | ✅ | HTML généré identique |
| Fonctionnalité OK | ✅ | Email rendu correctement |
| WordPress.org OK | ✅ | Prêt pour soumission |

---

## Conclusion

### ✅ CORRECTION COMPLÈTE

La syntaxe heredoc interdite a été remplacée par `sprintf()` avec succès :

1. ✅ Suppression de heredoc (`<<<HTML`)
2. ✅ Utilisation de sprintf() standard WordPress
3. ✅ Tous les tests passent (PHPCS + PHPStan)
4. ✅ Output fonctionnellement identique
5. ✅ Prêt pour WordPress.org

**Statut : APPROUVÉ ✅**
