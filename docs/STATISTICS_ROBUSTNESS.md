# Robustesse du système de statistiques - v1.2.8

## Problèmes corrigés

### 1. **Table de statistiques non initialisée après mise à jour**
- **Problème** : `Alert404_Storage::init()` n'était appelé que lors de l'activation du plugin
- **Impact** : Les mises à jour ou réactivations du plugin risquaient de laisser la table inexistante
- **Correction** : Ajout d'un hook `plugins_loaded` qui vérifie la table à chaque requête

### 2. **Créations de table non tracées**
- **Problème** : Les erreurs lors de la création de la table n'étaient pas enregistrées
- **Impact** : Impossible de diagnostiquer pourquoi les statistiques ne fonctionnaient pas
- **Correction** : Vérification de `$wpdb->last_error` et logging des erreurs via `Alert404_Logger::log_stats_error()`

### 3. **Insertions silencieuses non vérifiées**
- **Problème** : Le résultat de `$wpdb->insert()` n'était pas vérifié
- **Impact** : Les enregistrements échouaient sans rapport d'erreur
- **Correction** : Capture du résultat et logging si `false === $insert_result`

### 4. **Absence de table avant l'insertion**
- **Problème** : Aucune vérification que la table existe avant d'insérer
- **Impact** : Les insertions échouaient si la table avait été supprimée manuellement
- **Correction** : Appel systématique à `ensure_storage_ready()` avant chaque insertion

## Améliorations de diagnostic

### Nouvelle méthode de logging
```php
public static function log_stats_error( string $reason, string $context = '' ): void {
    self::log(
        'stats_error',
        array(
            'reason'  => $reason,
            'context' => $context,
        )
    );
}
```

### Logs de débogage disponibles
Avec `WP_DEBUG_LOG` activé, les erreurs de statistiques seront enregistrées :
- Erreurs de création/mise à jour de table
- Erreurs d'insertion de données
- Messages d'erreur MySQL complets

## Flux d'initialisation amélioré

```
1. Plugin charge → plugins_loaded hook
2. alert404_init() appelé
3. Alert404_Storage::init() appelé
   ├─ ensure_storage_ready() [immédiat]
   └─ add_action 'plugins_loaded' → ensure_storage_ready() [à chaque requête]
4. À chaque 404 détecté
   └─ record_404() appelle ensure_storage_ready() [vérification supplémentaire]
   └─ Insertion dans la table
```

## Déploiement

Pour déployer v1.2.8 :

1. Télécharger `404-alert-v1.2.8.zip`
2. Désactiver l'ancienne version
3. Supprimer les fichiers anciens
4. Extraire la nouvelle version
5. Activer le plugin
6. Vérifier `wp-content/debug.log` pour les erreurs de statistiques (si `WP_DEBUG_LOG` est activé)

## Test de validation

Pour vérifier que les statistiques fonctionnent :

1. Activer les statistiques dans les paramètres du plugin
2. Visiter une URL qui n'existe pas (ex: `/nonexistent-page`)
3. Aller à **Admin > 404 Alert > Statistiques**
4. Vérifier que le 404 apparaît dans le tableau

Si le 404 n'apparaît pas :
1. Activer `WP_DEBUG_LOG` dans `wp-config.php`
2. Revérifier le 404
3. Examiner `wp-content/debug.log` pour les erreurs `[404-Alert] [stats_error]`

## Architecture de la classe Alert404_Storage

```
Alert404_Storage
├── init()
│   ├─ ensure_storage_ready() [immédiat]
│   └─ add_action plugins_loaded
├── ensure_storage_ready()
│   ├─ Vérifier version du schéma
│   ├─ Créer/mettre à jour la table si nécessaire
│   └─ Migrer données anciennes
├── record_404(to, subject, payload)
│   ├─ Vérifier enable_stats
│   ├─ ensure_storage_ready() [vérification supplémentaire]
│   ├─ Insérer enregistrement
│   ├─ Vérifier résultat
│   ├─ Appliquer limite de records
│   └─ Invalider le cache
└── [get_* methods]
    └─ Lectures avec cache
```

## Dépendances

- WordPress 5.0+
- PHP 8.1+
- Classe `Alert404_Logger` pour les logs d'erreur
