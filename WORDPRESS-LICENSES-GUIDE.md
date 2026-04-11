# Guide complet des licences WordPress pour plugins

## Exigence WordPress.org (2026)

**WordPress.org impose : 100% GPL-compatible**. Tous les plugins hébergés sur le répertoire officiel doivent être compatibles avec GPLv2 ou version ultérieure. Cela découle du fait que **WordPress lui-même est GPLv2+**.

---

## Licences acceptées par WordPress.org

### 1. **GPLv2 (GNU General Public License v2)**

#### Description
La licence utilisée par WordPress. C'est le standard de facto pour les plugins WordPress.

#### Avantages
- ✅ **100% acceptée** sur WordPress.org
- ✅ **Encouragée par la communauté** - s'aligne avec WordPress
- ✅ **Licence copyleft forte** - force la divulgation du code source
- ✅ **Large communauté** - bien comprise et documentée
- ✅ **Compatibilité garantie** avec WordPress core
- ✅ **Support légal établi** - jurisprudence abondante

#### Inconvénients
- ❌ **Restreint l'usage commercial propriétaire** - versions modifiées doivent être GPL
- ❌ **Viral** - toutes dépendances doivent être GPL
- ❌ **Plus restrictif** que licences permissives
- ❌ **Ne s'applique que au code** - ne couvre pas les designs ou assets

#### Cas d'usage
- **Idéal pour** : plugins gratuits open-source
- **Typique pour** : plugins hébergés sur WordPress.org

---

### 2. **GPLv2+ (ou GPLv2 or later)**

#### Description
Identique à GPLv2 mais **autorise explicitement l'utilisation de termes GPLv3 ou ultérieures**.

#### Avantages
- ✅ Tous les avantages de GPLv2
- ✅ **Flexibilité future** - compatibilité avec futures versions GPL
- ✅ **Moins restrictif** - permet adoption de termes GPL plus modernes
- ✅ **Compatible avec GPLv3** si nécessaire

#### Inconvénients
- ❌ Même restrictions que GPLv2
- ❌ Ajoute une couche de complexité minimale

#### Cas d'usage
- **Recommandé** - c'est le meilleur choix pour les plugins WordPress
- **Standard moderne** pour plugins qui veulent rester à jour

---

### 3. **GPLv3**

#### Description
Version plus récente de la GPL, adoptée en 2007 avec protections anti-DRM.

#### Avantages
- ✅ **Plus moderne** - adresse des problèmes de GPLv2
- ✅ **Protection explicite contre le DRM** et jailbreaking
- ✅ **Meilleure couverture des brevets** que GPLv2
- ✅ Copyleft fort

#### Inconvénients
- ⚠️ **INCOMPATIBILITÉ GPLv2 ↔ GPLv3** - ne mélange pas bien avec WordPress
- ❌ **Problématique pour WordPress.org** - WordPress est GPLv2
- ❌ **Moins populaire** dans écosystème WordPress
- ❌ Peut causer des frictions légales si combiné à du code GPLv2

#### Cas d'usage
- ❌ **À éviter pour plugins WordPress** - crée des complications
- ✅ Acceptable pour plugins complètement indépendants, mais non recommandé

---

### 4. **LGPL v2.1 (Lesser GPL)**

#### Description
Version "allégée" de GPL - permet la liaison avec du code non-GPL via bibliothèques dynamiquement liées.

#### Avantages
- ✅ **Compatible avec GPLv2** (selon FSF)
- ✅ **Plus permissive** que GPL classique
- ✅ **Permet libraries propriétaires** liées dynamiquement
- ✅ Idéal pour **code de bibliothèque** à réutiliser

#### Inconvénients
- ⚠️ **Rare sur WordPress.org** - peu de plugins l'utilisent
- ⚠️ **Complexité légale** - interaction avec GPL est subtile
- ❌ **Peut ne pas être acceptée** par certains auditeurs WordPress.org
- ❌ **Confuse les utilisateurs** - GPL est plus familière
- ❌ Copyleft moins fort

#### Cas d'usage
- Acceptée techniquement mais **peu recommandée**
- Utile si vous créez une **bibliothèque PHP réutilisable**
- Généralement remplacée par MIT/Apache pour bibliotèques

---

### 5. **MIT License**

#### Description
Licence permissive très simple : utilisation libre avec attribution.

#### Avantages
- ✅ **GPL-compatible** (selon FSF et GNU)
- ✅ **Extrêmement permissive** - permet usage commercial propriétaire
- ✅ **Très simple et claire** - 3 clauses principales
- ✅ **Largement acceptée** - populaire en tech
- ✅ **Pas de viral** - code dérivé peut être propriétaire
- ✅ **Excellent pour réutilisabilité** - restrictions minimales

#### Inconvénients
- ⚠️ **Copyleft faible** - code propriétaire peut dériver
- ❌ **Peu encouragée culturellement** sur WordPress.org
- ❌ **Pas de garantie de liberté du code** - dépendances peuvent devenir propriétaires
- ❌ Permet à des entreprises d'en tirer profit sans contribuer en retour

#### Cas d'usage
- ✅ **Acceptable techniquement** pour WordPress.org
- ✅ Bon pour **plugins commerciaux avec source ouverte**
- ✅ Idéal pour **code partageable** qu'on veut réutiliser librement
- Utilisé par quelques plugins, mais moins que GPL

---

### 6. **ISC License** (situation actuelle du plugin)

#### Description
Licence permissive minimale - similaire à MIT mais plus courte.

#### Avantages
- ✅ **GPL-compatible** (selon FSF et GNU)
- ✅ **Extrêmement simple** - plus court que MIT
- ✅ **Permissive** - libre utilisation commerciale
- ✅ **Pas de viral**

#### Inconvénients
- ⚠️ **REJETÉE par WordPress.org** - non conforme standards
- ❌ **Pas de garantie d'open-source perpetuel**
- ❌ **Ne s'aligne pas avec vision WordPress**
- ❌ **Peu familière** dans écosystème WordPress
- ❌ Permet fermeture du code par tiers

#### Cas d'usage
- ❌ **NON RECOMMANDÉE pour WordPress.org**
- Acceptable pour code non-WordPress
- ⚠️ **Problème actuel du 404-alert**

---

### 7. **Apache License 2.0**

#### Description
Licence commerciale amicale avec clauses de brevets explicites.

#### Avantages
- ✅ **Très permissive** - usage commercial libre
- ✅ **Couverture de brevets explicite** - protégé contre litigation
- ✅ **Bien structurée légalement** - utilisée par grandes entreprises
- ✅ **Flexibilité contractuelle**

#### Inconvénients
- ❌ **INCOMPATIBLE avec GPLv2** - FSF officiel
- ❌ **Rejetée par WordPress.org** - crée problèmes légaux
- ❌ Clauses de brevets conflicts avec GPL
- ❌ Viral copyleft moins clair

#### Cas d'usage
- ❌ **À éviter absolument pour plugins WordPress**
- Acceptable pour projets indépendants Apache-PHP

---

### 8. **BSD License (2-Clause, 3-Clause)**

#### Description
Licences permissives proches de MIT, avec clauses supplémentaires.

#### Avantages
- ✅ **GPL-compatible** (2-Clause et 3-Clause)
- ✅ **Permissive** - usage commercial libre
- ✅ **Bien établie** - standards industriels
- ✅ **Pas de viral**

#### Inconvénients
- ⚠️ **Rarement utilisée** sur WordPress (MIT/GPL préférées)
- ❌ **Moins familière** aux développeurs WordPress
- ❌ Avantages minimes sur MIT

#### Cas d'usage
- ✅ Techniquement acceptable
- 🤷 Peu de raison de l'utiliser vs MIT/GPL

---

### 9. **Boost Software License**

#### Description
Licence très permissive utilisée par librairies Boost C++.

#### Avantages
- ✅ **GPL-compatible**
- ✅ **Extrêmement permissive**
- ✅ **Établie et respectée**

#### Inconvénients
- ❌ **Presque jamais utilisée** pour PHP/WordPress
- ❌ **Complexe pour le contexte WordPress**

#### Cas d'usage
- ❌ Non recommandée pour WordPress

---

## Comparaison synthétique

| Licence | Acceptée WP.org | Copyleft | Commercial | Simplicité | Recommandée |
|---------|:---:|:---:|:---:|:---:|:---:|
| **GPLv2** | ✅ | Fort | Conditionnée | ✅ | **⭐⭐⭐** |
| **GPLv2+** | ✅ | Fort | Conditionnée | ✅ | **⭐⭐⭐⭐** |
| **GPLv3** | ⚠️ | Fort | Conditionnée | ⚠️ | ⭐ |
| **MIT** | ✅ | Faible | ✅ | ✅ | ⭐⭐ |
| **LGPL 2.1** | ✅ | Moyen | ✅ | ❌ | ⭐ |
| **ISC** | ❌ | Faible | ✅ | ✅ | ❌ |
| **Apache 2.0** | ❌ | Moyen | ✅ | ❌ | ❌ |
| **BSD** | ✅ | Faible | ✅ | ✅ | ⭐⭐ |

---

## 📋 Recommandation pour 404-alert

### Situation actuelle
- **ISC est REJETÉE** par WordPress.org
- Vous devez changer avant soumission

### Meilleur choix : **GPLv2 ou GPLv2+**

**Pourquoi ?**
1. ✅ Standard WordPress - 99% des plugins
2. ✅ Alignement avec vision open-source WordPress
3. ✅ Garantit que améliorations bénéficient à tous
4. ✅ Zéro friction à l'approbation WordPress.org
5. ✅ Meilleure adoption communautaire

**Alternative acceptable : MIT**
- Si vous voulez plus de liberté commerciale
- Toujours acceptable par WordPress.org
- Moins "culturellement alignée" mais possible

### Action à prendre
```
Changement recommandé : ISC → GPLv2 ou GPLv2+

Fichiers à modifier :
- 404-alert.php (header)
- readme.txt (License header)
- Ajouter LICENSE.txt au root avec texte GPL complet
```

---

## 📚 Ressources officielles

- [WordPress.org License Info](https://wordpress.org/about/license/)
- [Plugin Handbook - License](https://developer.wordpress.org/plugins/plugin-basics/including-a-software-license/)
- [GNU Licenses Compatible](https://www.gnu.org/licenses/license-list.html)
- [GPL FAQ - WordPress specifics](https://www.gnu.org/licenses/gpl-faq.html)

---

## Résumé pratique

**Pour un plugin WordPress.org : GPLv2+ est le meilleur choix.**

- Acceptée à 100%
- Standard communautaire
- Clauses claires
- Aucun doute légal
- Bénéficie aux utilisateurs

**Évitez absolument :** ISC, Apache 2.0, GPLv3 seule

**Possibles mais moins idéales :** MIT, LGPL, BSD
