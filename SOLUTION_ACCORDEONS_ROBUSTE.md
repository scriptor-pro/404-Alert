# 🔧 Solution Robuste: Accordéons SMTP

**Problème Identifié:** Les accordéons ne se déplient pas correctement  
**Cause Root:** Le JavaScript dépend de variables globales qui peuvent ne pas être définies  
**Solution:** Remplacer par un système de tabs HTML/CSS pur (sans JavaScript pour l'affichage)

---

## Diagnostic du Problème

### Points Faibles Actuels

1. **Dépendance JavaScript fragile**
   - `a404Presets` doit être passé via `wp_json_encode()`
   - `a404CurrentMode` dépend du fait que `$provider_id` soit défini
   - Si le script jQuery ne charge pas → accordéons cassés

2. **État d'accordéon complexe**
   - État interne en JavaScript: `accordionState.preset`, `accordionState.custom`
   - Logique d'ouverture basée sur `loadInitialState()` au chargement
   - Risque de désynchronisation

3. **jQuery obligatoire**
   - Code dépend de `slideDown()` et `slideUp()`
   - Pas d'alternative si jQuery ne charge pas
   - Surcharge inutile pour une simple visibilité

4. **Logique de validation confuse**
   ```js
   // Problème: Dépend de l'état d'accordéon pour la validation
   if (accordionState.preset && state.preset.selected && a404Presets[...]) {
     // Stocke preset data
   } else if (accordionState.custom) {
     // Stocke custom data
   }
   // ← Si accordéon ne s'ouvre pas → aucune donnée stockée
   ```

---

## Solution Robuste Proposée: TABS HTML/CSS

### Architecture

**Au lieu de:** Accordéons avec état JavaScript  
**Utiliser:** Tabs standard HTML avec radio buttons (pattern classique WordPress)

```html
<!-- TABS SYSTEM -->
<fieldset class="404-smtp-tabs">
  <!-- TAB 1: PRESET -->
  <input type="radio" 
         id="404-tab-preset" 
         name="404_smtp_mode" 
         value="preset"
         class="404-tab-input" />
  
  <label for="404-tab-preset" class="404-tab-label">
    📌 Fournisseur connu
  </label>
  
  <div class="404-tab-content">
    <!-- Contenu du preset -->
  </div>

  <!-- TAB 2: CUSTOM -->
  <input type="radio" 
         id="404-tab-custom" 
         name="404_smtp_mode" 
         value="custom"
         class="404-tab-input" />
  
  <label for="404-tab-custom" class="404-tab-label">
    ⚙️ Configuration personnalisée
  </label>
  
  <div class="404-tab-content">
    <!-- Contenu du custom -->
  </div>
</fieldset>
```

### CSS Pure (Sans JavaScript pour l'affichage)

```css
/* Cacher tous les inputs radio */
.404-tab-input {
  display: none;
}

/* Style des labels (tabs) */
.404-tab-label {
  display: inline-block;
  padding: 12px 20px;
  background: #f6f7f7;
  border: 1px solid #c3c4c7;
  border-bottom: 3px solid #c3c4c7;
  cursor: pointer;
  font-weight: 600;
  margin-right: 0;
  transition: all 0.2s ease;
}

.404-tab-label:hover {
  background: #e8e9ea;
}

/* Cacher tout le contenu par défaut */
.404-tab-content {
  display: none;
  padding: 20px;
  background: #fff;
  border: 1px solid #c3c4c7;
  border-top: none;
}

/* Afficher le contenu du tab sélectionné */
.404-tab-input:checked + .404-tab-label {
  background: #fff;
  border-bottom-color: #2271b1;
  color: #2271b1;
}

.404-tab-input:checked ~ .404-tab-content {
  display: block;
}

/* Layout en grid pour les labels */
.404-smtp-tabs {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0;
  border: none;
  padding: 0;
  margin: 0;
}

.404-tab-label {
  grid-column: auto;
}

.404-tab-content {
  grid-column: 1 / -1;
}
```

### Avantages

✅ **Zéro JavaScript pour l'affichage/masquage**  
✅ **Fonctionne sans jQuery**  
✅ **Plus rapide** (pas d'état en mémoire JS)  
✅ **Accessible** (supporté par tous les navigateurs)  
✅ **Validation simplifiée** (le mode est le `value` du radio)  
✅ **État persistant** au rechargement (part du formulaire)

---

## Implémentation Recommandée

### 1. Remplacer le HTML des accordéons

**Actuellement:**
```html
<div style="border: 1px solid #c3c4c7; ...">
  <button type="button" class="404-accordion-toggle" ...>
    Fournisseur connu
  </button>
  <div id="404-accordion-preset" class="404-accordion-content" 
       style="display: none;">
    <!-- Contenu -->
  </div>
</div>
```

**Par:**
```html
<input type="radio" id="404-tab-preset" name="404_smtp_mode" 
       value="preset" class="404-tab-input" />
<label for="404-tab-preset" class="404-tab-label">
  📌 Fournisseur connu
</label>
<div class="404-tab-content">
  <!-- Contenu -->
</div>
```

### 2. Créer `alert404-tabs.css`

Fichier CSS dédié aux tabs (séparé de `alert404-smtp-config.css`).

### 3. Simplifier le JavaScript

**Remplacer le code complexe d'accordéon par:**
```js
jQuery(document).ready(function($) {
  // Déterminer le tab actif au chargement
  const currentMode = window.a404CurrentMode || 'custom';
  
  // Sélectionner le radio input correspondant
  $('input[name="404_smtp_mode"][value="' + currentMode + '"]')
    .prop('checked', true);
  
  // Plus besoin de setupAccordionToggles()
  // Plus besoin de openAccordion() / closeAccordion()
  // Plus besoin de accordionState
});
```

**Vs. actuellement (complexe):**
```js
setupAccordionToggles()  // Click handlers
setupPresetChangeListener()
setupCustomFieldListeners()
setupCommonFieldListeners()
loadInitialState()  // État initial
updateAllSummaries()  // Mise à jour
// + 200 lignes de gestion d'état
```

---

## Alternative Minimale: FixJavaScript (Si tu veux garder les accordéons)

Si tu insistes pour garder les accordéons, voici le fix minimal:

### Problème #1: `a404Presets` non défini

**Ligne 476 du PHP:**
```php
window.a404Presets = <?php echo wp_json_encode( $presets ); ?>;
```

**Doit être AVANT le chargement du JS:**
```php
// Dans enqueue_admin_scripts():
wp_localize_script(
  '404-alert-smtp-config',  // ← AVANT d'enqueuer le script
  'a404Data',
  array(
    'presets'     => $presets,
    'currentMode' => $provider_id ? 'preset' : 'custom',
    'ajaxurl'     => admin_url( 'admin-ajax.php' ),
  )
);

// Puis charger le script
wp_enqueue_script( '404-alert-smtp-config', ... );
```

**Et dans le JS:**
```js
/* Au lieu de: window.a404Presets */
const a404Presets = window.a404Data?.presets || {};
const a404CurrentMode = window.a404Data?.currentMode || 'custom';
```

### Problème #2: Logique d'état cassée

Remplacer:
```js
if (accordionState.preset && state.preset.selected && a404Presets[state.preset.selected]) {
  // Utilise le preset
} else if (accordionState.custom) {
  // Utilise le custom
}
```

Par:
```js
// Déterminer le mode depuis le formulaire, pas depuis l'état d'accordéon
const selectedPreset = $('#404-preset-id').val();
const customHost = $('#404-custom-host').val();

// Mode déterminé par le formulaire, pas par l'interface
const isPresetMode = selectedPreset && !customHost;
```

---

## Comparaison: Tabs vs Accordéons

| Aspect | Tabs | Accordéons |
|--------|------|-----------|
| **Complexité CSS** | Très simple | Complexe |
| **Complexité JS** | Triviale | Modérée |
| **Dépendances** | Zéro | jQuery |
| **Accessibilité** | A11y native | Besoin ARIA |
| **Performance** | Excellente | Bonne |
| **Maintenabilité** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ |
| **UX desktop** | Excellente | Bonne |
| **UX mobile** | Bonne | Très bonne |

---

## Recommandation Finale

### ✅ SOLUTION RECOMMANDÉE: Tabs HTML/CSS + JavaScript Minimaliste

**Raisons:**
1. **Robustesse:** Fonctionne même si jQuery ne charge pas
2. **Performance:** Pas d'état complexe en mémoire
3. **Maintenabilité:** Code simple et compréhensible
4. **Accessibilité:** Support natif des navigateurs modernes
5. **Sécurité:** Surface d'attaque réduite (moins de JS)

**Implémentation:**
- ⏱ 2 heures (refactor HTML + CSS + JS minimal)
- 📝 ~100 lignes de code au total
- ✅ Zéro dépendances supplémentaires

### Alternative: Garder les accordéons

Si tu veux vraiment garder les accordéons:
1. Appliquer le fix du `wp_localize_script()` (voir ci-dessus)
2. Corriger la logique d'état (utiliser le formulaire comme source de vérité)
3. Ajouter du error handling si le JS ne charge pas

---

## Prochaines Étapes

**Veux-tu que je:**

1. **Implémenter les Tabs HTML/CSS** (solution complète et robuste)
2. **Appliquer les fixes d'accordéon** (minimal, garder l'interface actuelle)
3. **Investiguer davantage** le problème exact (tester dans un navigateur)

**Quel chemin préfères-tu?**
