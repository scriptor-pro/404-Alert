# Intégration obligatoire des statistiques

> **Pour les travailleurs agentiques :** COMPÉTENCE REQUISE : Utiliser superpowers:subagent-driven-development (recommandé) ou superpowers:executing-plans pour implémenter ce plan tâche par tâche. Les étapes utilisent la syntaxe checkbox (`- [ ]`) pour le suivi.

**Objectif :** Faire passer la collecte des statistiques 404 de fonctionnalité optionnelle à fonctionnalité obligatoire, en supprimant le paramètre de configuration et en affichant toujours l'onglet Statistiques.

**Architecture :** La réforme se concentre sur trois fichiers clés. D'abord, supprimer le champ optionnel des paramètres (Settings). Ensuite, simplifier la logique d'enregistrement dans Mailer pour toujours collecter les stats. Enfin, adapter le Dashboard pour afficher l'onglet même sans données. Ces trois changements isolés rendent les statistiques obligatoires et toujours visibles.

**Tech Stack :** PHP 7.4+, WordPress Settings API, OOP

---

## Fichiers à modifier

- **Modifier :** `includes/class-alert404-settings.php` — Supprimer le champ `404_alert_enable_stats`
- **Modifier :** `includes/class-alert404-mailer.php` — Toujours enregistrer les stats (supprimer la vérification de `enable_stats`)
- **Modifier :** `includes/class-alert404-dashboard.php` — Afficher l'onglet même sans données, adapter le message
- **Modifier :** `build/404-alert/includes/class-alert404-settings.php` — Synchroniser les changements
- **Modifier :** `build/404-alert/includes/class-alert404-mailer.php` — Synchroniser les changements
- **Modifier :** `build/404-alert/includes/class-alert404-dashboard.php` — Synchroniser les changements

---

## Task 1 : Supprimer le champ de paramètre dans Settings

**Fichiers :**
- Modifier : `includes/class-alert404-settings.php:224-230` (suppression du champ)
- Modifier : `includes/class-alert404-settings.php:587-601` (suppression de la fonction de rendu)

- [ ] **Étape 1 : Supprimer l'enregistrement du champ dans `register_settings()`**

Localiser et supprimer ce bloc (lignes 224-230) :

```php
add_settings_field(
    '404_alert_enable_stats',
    'Statistics',
    array( self::class, 'render_field_enable_stats' ),
    '404_alert',
    '404_alert_section'
);
```

- [ ] **Étape 2 : Supprimer la fonction `render_field_enable_stats()`**

Localiser et supprimer cette fonction (lignes 587-601) :

```php
public static function render_field_enable_stats(): void {
    $options      = get_option( '404_alert_options', array() );
    $enable_stats = ! empty( $options['enable_stats'] );
    ?>
    <input type="checkbox" 
            name="404_alert_options[enable_stats]" 
            value="1" 
            id="404_alert_enable_stats"
            <?php checked( $enable_stats, true ); ?> />
    <label for="404_alert_enable_stats">Activer les statistiques</label>
    <p class="description" style="color: #666;">
        Enregistre les 404 pour afficher des statistiques dans le dashboard. Maximum 1000 enregistrements.
    </p>
    <?php
}
```

- [ ] **Étape 3 : Tester la page de paramètres**

Accéder à `wp-admin/admin.php?page=404_alert` et vérifier que :
- Le champ "Statistics" n'apparaît plus
- Les autres champs restent visibles (Email, Daily Limit, IP Cooldown, Force Logging)

- [ ] **Étape 4 : Commit**

```bash
git add includes/class-alert404-settings.php
git commit -m "refactor: remove optional statistics setting from admin UI"
```

---

## Task 2 : Simplifier l'enregistrement des statistiques dans Mailer

**Fichiers :**
- Modifier : `includes/class-alert404-mailer.php:69-75`

- [ ] **Étape 1 : Remplacer la logique conditionnelle par enregistrement obligatoire**

Localiser le bloc (lignes 69-75) :

```php
// Record statistics regardless of email success.
if ( class_exists( 'Alert404_Stats' ) ) {
    $options = get_option( '404_alert_options', array() );
    if ( ! empty( $options['enable_stats'] ) ) {
        Alert404_Stats::record( $payload );
    }
}
```

Le remplacer par :

```php
// Record statistics always (feature is mandatory).
if ( class_exists( 'Alert404_Stats' ) ) {
    Alert404_Stats::record( $payload );
}
```

- [ ] **Étape 2 : Vérifier la cohérence du code**

S'assurer que :
- `Alert404_Stats::record()` est appelée à chaque 404
- Aucune autre vérification de `enable_stats` n'existe dans cette fonction

- [ ] **Étape 3 : Tester l'enregistrement des stats**

Déclencher un 404 et vérifier que :
- L'enregistrement est écrit dans la table `wp_404_alert_stats` (via phpmyadmin ou logs)
- Aucun message d'erreur n'apparaît

- [ ] **Étape 4 : Commit**

```bash
git add includes/class-alert404-mailer.php
git commit -m "refactor: make statistics recording mandatory"
```

---

## Task 3 : Adapter le Dashboard pour afficher l'onglet toujours

**Fichiers :**
- Modifier : `includes/class-alert404-dashboard.php:33-65`

- [ ] **Étape 1 : Modifier le message "Aucune donnée"**

Localiser le bloc de condition (lignes 55-65) :

```php
if ( 0 === $total ) {
    ?>
    <div class="wrap">
        <h1>404 Alert — Statistiques</h1>
        <div class="notice notice-info">
            <p>Aucune donnée statistique. Activez les statistiques dans les paramètres.</p>
        </div>
    </div>
    <?php
    return;
}
```

Le remplacer par :

```php
if ( 0 === $total ) {
    ?>
    <div class="wrap">
        <h1>404 Alert — Statistiques</h1>
        <div class="notice notice-info">
            <p>Aucune donnée statistique pour le moment. Les statistiques seront collectées automatiquement à partir du prochain 404.</p>
        </div>
    </div>
    <?php
    return;
}
```

- [ ] **Étape 2 : Vérifier que l'onglet Statistiques s'affiche toujours**

S'assurer que `Alert404_Dashboard::add_menu()` s'exécute correctement et que l'onglet "Statistiques" apparaît dans le menu admin même sans données.

- [ ] **Étape 3 : Tester l'affichage du Dashboard**

Accéder à `wp-admin/admin.php?page=404_alert_stats` et vérifier que :
- L'onglet s'affiche avec le nouveau message
- Les boutons "Exporter" et "Effacer les statistiques" restent visibles/fonctionnels
- Après un 404, les statistiques s'affichent correctement

- [ ] **Étape 4 : Commit**

```bash
git add includes/class-alert404-dashboard.php
git commit -m "refactor: update no-data message for mandatory statistics"
```

---

## Task 4 : Synchroniser les changements dans le build

**Fichiers :**
- Modifier : `build/404-alert/includes/class-alert404-settings.php`
- Modifier : `build/404-alert/includes/class-alert404-mailer.php`
- Modifier : `build/404-alert/includes/class-alert404-dashboard.php`

- [ ] **Étape 1 : Appliquer les mêmes changements au fichier Settings du build**

Copier les modifications de l'étape 1 et 2 (Task 1) dans `build/404-alert/includes/class-alert404-settings.php`.

- [ ] **Étape 2 : Appliquer les mêmes changements au fichier Mailer du build**

Copier les modifications de l'étape 1 (Task 2) dans `build/404-alert/includes/class-alert404-mailer.php`.

- [ ] **Étape 3 : Appliquer les mêmes changements au fichier Dashboard du build**

Copier les modifications de l'étape 1 (Task 3) dans `build/404-alert/includes/class-alert404-dashboard.php`.

- [ ] **Étape 4 : Vérifier que le build est synchronisé**

Exécuter une comparaison des fichiers pour s'assurer que les changements du source (includes/) correspondent au build (build/404-alert/includes/).

```bash
diff includes/class-alert404-settings.php build/404-alert/includes/class-alert404-settings.php
diff includes/class-alert404-mailer.php build/404-alert/includes/class-alert404-mailer.php
diff includes/class-alert404-dashboard.php build/404-alert/includes/class-alert404-dashboard.php
```

- [ ] **Étape 5 : Commit**

```bash
git add build/404-alert/includes/class-alert404-*.php
git commit -m "build: sync mandatory statistics changes"
```

---

## Task 5 : Tester l'intégration complète

**Fichiers :**
- Test : `tests/unit/Test_Alert404_Settings.php`
- Test : `tests/integration/Test_Alert404_E2E.php`

- [ ] **Étape 1 : Vérifier qu'aucun test ne référence `enable_stats`**

Rechercher les références à `enable_stats` ou `404_alert_enable_stats` dans les tests :

```bash
grep -r "enable_stats" tests/
```

Si des tests existent, les adapter ou les supprimer (car la fonctionnalité n'existe plus).

- [ ] **Étape 2 : Exécuter les tests unitaires**

```bash
npm test
```

Vérifier que :
- Tous les tests passent
- Aucune erreur liée aux paramètres manquants
- Aucune erreur liée à `Alert404_Stats`

- [ ] **Étape 3 : Tester manuellement le flux complet**

1. Accéder à `wp-admin/admin.php?page=404_alert` et vérifier l'absence du champ Statistics
2. Accéder à `wp-admin/admin.php?page=404_alert_stats` et vérifier le message "Aucune donnée"
3. Déclencher un 404 (accéder à une URL inexistante)
4. Retourner à `wp-admin/admin.php?page=404_alert_stats` et vérifier que les stats s'affichent
5. Tester l'export CSV et la suppression des statistiques

- [ ] **Étape 4 : Commit**

```bash
git add tests/
git commit -m "test: verify mandatory statistics integration"
```

---

## Checklist de vérification

Avant de clôturer :

- [ ] Aucune référence à `enable_stats` ne subsiste dans le code source (sauf commentaires historiques)
- [ ] Le champ "Statistics" a disparu de la page de paramètres
- [ ] L'onglet "Statistiques" s'affiche toujours, même sans données
- [ ] Les stats se collectent automatiquement après chaque 404
- [ ] Les tests passent tous
- [ ] Les fichiers du build sont synchronisés
- [ ] Un commit a été créé pour chaque tâche
