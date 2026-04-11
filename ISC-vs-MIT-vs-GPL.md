# ISC vs MIT vs GPL : Comparaison visuelle

## Les trois grandes familles de licences

### 1️⃣ **Licences PERMISSIVES** (MIT, ISC, BSD)
- "Fais ce que tu veux"
- Aucun copyleft
- Usage commercial OK
- Modifications OK
- Pas de retour obligatoire

### 2️⃣ **Licences COPYLEFT** (GPL)
- "Fais ce que tu veux, mais partage les améliorations"
- Copyleft viral
- Usage commercial OK (sous GPL)
- Modifications OK
- Modifications doivent rester GPL

### 3️⃣ **Licences HYBRIDES** (LGPL)
- Entre permissive et copyleft
- Permet linking non-GPL
- Copyleft léger

---

## Matrice comparative détaillée

```
┌─────────────────────────┬──────────────────┬──────────────────┬──────────────────┐
│ Critère                 │ ISC              │ MIT              │ GPL v2           │
├─────────────────────────┼──────────────────┼──────────────────┼──────────────────┤
│ Longueur                │ 8 lignes ✨      │ 11 lignes        │ ~450 lignes      │
│ Lisibilité              │ ⭐⭐⭐⭐⭐      │ ⭐⭐⭐⭐       │ ⭐⭐            │
│ Complexité légale       │ Minimale         │ Minimale         │ Extrême          │
│ Permissivité            │ Maximale ✨      │ Maximale         │ Conditionnelle   │
│ Usage commercial        │ ✅ OUI           │ ✅ OUI           │ ✅ OUI (GPL)     │
│ Modifications           │ ✅ OUI           │ ✅ OUI           │ ✅ OUI (GPL)     │
│ Vente de dérivé         │ ✅ OUI           │ ✅ OUI           │ ✅ OUI (GPL)     │
│ Cacher le code          │ ✅ OUI           │ ✅ OUI           │ ❌ NON (viral)   │
│ Fermer le code dérivé   │ ✅ OUI           │ ✅ OUI           │ ❌ NON           │
│ Obligation retour       │ ❌ Non           │ ❌ Non           │ ✅ OUI (viral)   │
│ Copyleft                │ ❌ Non           │ ❌ Non           │ ✅ Fort (viral)  │
│ Attribution obligatoire │ ⚠️ Copyright     │ ⚠️ Copyright     │ ⚠️ Copyright     │
│ Garantie                │ ❌ Non           │ ❌ Non           │ ❌ Non           │
│ Responsabilité          │ ❌ Non           │ ❌ Non           │ ❌ Non           │
└─────────────────────────┴──────────────────┴──────────────────┴──────────────────┘
```

---

## Scénarios pratiques

### Scénario 1 : Votre code devient une app payante propriétaire

```
Code original : ISC
├─ Vous modifiez le code
├─ Vous le vendez à des clients
├─ Vous gardez le code fermé (propriétaire)
└─ ✅ ISC autorise cela (permissive)

Code original : MIT
├─ Vous modifiez le code
├─ Vous le vendez à des clients
├─ Vous gardez le code fermé
└─ ✅ MIT autorise cela

Code original : GPL
├─ Vous modifiez le code
├─ Vous le vendez à des clients
├─ Vous gardez le code fermé
└─ ❌ GPL interdit cela (viral)
   → Vous DEVEZ révéler et rester GPL
```

**Verdict :** ISC/MIT = maximum liberté commerciale. GPL = obligation de partage.

---

### Scénario 2 : Bug fix critical d'un contributeur

```
Code original : ISC (par Alice)
├─ Bob trouve un bug critique
├─ Bob fixe le bug localement
├─ Bob ne partage pas sa fix
├─ Alice ne voit jamais la correction
└─ ⚠️ ISC autorise cela (pas de copyleft)

Code original : GPL (par Alice)
├─ Bob trouve un bug critique
├─ Bob fixe le bug et le distribue
├─ Bob DOIT partager sa fix en GPL
├─ Alice et tous les autres en bénéficient
└─ ✅ GPL force cela (copyleft viral)
```

**Verdict :** GPL = force le partage des améliorations (meilleur pour communauté). ISC/MIT = zéro obligation.

---

### Scénario 3 : Code utilisé dans WordPress

```
Code original : ISC
├─ WordPress (GPLv2+) l'intègre
├─ Conflit philosophique : ISC ne force pas GPL
├─ WordPress.org dit "non, nous voulons GPL"
└─ ❌ REJETÉ par WordPress.org

Code original : MIT
├─ WordPress (GPLv2+) l'intègre
├─ MIT est permissive, compatible GPL
├─ Pas de conflit philosophique
└─ ✅ Accepté mais pas idéal

Code original : GPL
├─ WordPress (GPLv2+) l'intègre
├─ Alignement parfait
├─ Mêmes valeurs et obligations
└─ ✅ Accepté et encouragé par WordPress.org
```

**Verdict :** Pour WordPress = GPL > MIT > ISC

---

## Les 5 différences clés

### 1. **Taille et Complexité**

```
ISC (8 lignes)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

MIT (11 lignes)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

GPL v2 (~450 lignes)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

**ISC** est délibérément minimaliste. MIT aussi. GPL est exhaustive.

### 2. **Copyleft (le point le plus important)**

```
┌─ ISC
│  Vous modifiez → Code fermé OK → Pas d'obligation retour
│
├─ MIT
│  Vous modifiez → Code fermé OK → Pas d'obligation retour
│
└─ GPL
   Vous modifiez → Code fermé OK SEULEMENT SI pas distribué
   Si vous distribuez → DOIT être GPL → Obligation retour
```

**ISC/MIT = liberté maximale. GPL = obligation retour si distribué.**

### 3. **Intention philosophique**

```
ISC
"Maximiser la réutilisation du code"
└─ Restrictions minimales = adoption maximale

MIT  
"Liberté pour l'utilisateur"
└─ Aucune restriction = liberté totale

GPL
"Liberté POUR LA COMMUNAUTÉ"
└─ Force le partage = bénéfice collectif
```

### 4. **Attribution**

```
ISC : "Conservez l'avis copyright"
MIT : "Conservez l'avis copyright"
GPL : "Conservez l'avis copyright" + "Divulguer le code source complet"
```

Tous trois exigent l'attribution minimale.

### 5. **Acceptation WordPress.org**

```
ISC  → ❌ REJETÉ ("pas GPL-aligned")
MIT  → ✅ Accepté (GPL-compatible, permissive)
GPL  → ✅✅ Accepté ET encouragé (standard de facto)
```

**Pourquoi WordPress refuse ISC** (bien que techniquement GPL-compatible) :
- WordPress veut des **valeurs alignées**
- ISC est trop "neutre philosophiquement"
- GPL impose partage des améliorations
- ISC permet fermeture future
- WordPress préfère la culture GPL

---

## Choix par cas d'usage

### Si vous voulez **MAXIMUM LIBERTÉ** pour utilisateurs/développeurs
```
ISC ou MIT ✅
└─ "Fais ce que tu veux, pas d'obligations"
```

### Si vous voulez **BÉNÉFICES COMMUNAUTAIRES**
```
GPL ✅
└─ "Améliorations retournent à la communauté"
```

### Si vous voulez **COMPATIBILITÉ WORDPRESS.ORG**
```
GPL v2+ (meilleur) ou MIT (acceptable) ✅
ISC ❌ REFUSÉ
```

### Si vous voulez **SIMPLICITÉ LÉGALE**
```
ISC > MIT > GPL
(8 lignes vs 11 vs 450)
```

### Si vous voulez **IMPACT MAXIMAL EN JAVASCRIPT/NODE.JS**
```
ISC (c'est la default npm) ✅
MIT aussi populaire
```

### Si vous voulez **SOUTIEN ENTERPRISE**
```
MIT > GPL > ISC
(MIT = standard entreprise pour libs)
```

---

## La décision pour 404-alert

### Situation
```
Licence actuelle : ISC ❌
Objectif : WordPress.org
Requis : GPL-compatible et acceptée
```

### Options

| Option | Pros | Cons | Verdict |
|--------|------|------|---------|
| **Rester ISC** | • Minimaliste<br>• Maximal liberté | • WordPress.org refuse<br>• Pas de copyleft | ❌ Bloquée |
| **Changer MIT** | • Acceptable WP.org<br>• Très permissive | • Moins idéal culturellement | ⚠️ Possible |
| **Changer GPL v2+** | • Gold standard WP.org<br>• Copyleft viral<br>• Valeurs alignées | • Plus complexe<br>• Force partage | ✅ MEILLEUR |

### Recommandation finale
**Changez en GPL v2 ou GPLv2+**

Raisons :
1. ✅ Acceptation garantie WordPress.org
2. ✅ 99% des plugins WordPress
3. ✅ Aligne avec valeurs WordPress
4. ✅ Force amélioration du code pour tous
5. ✅ Aucun doute légal

---

## Ressources visuelles

### Copyleft visualisé

```
                    PERMISSIVE SIDE
                     (Liberté max)
        ISC    MIT         BSD
         •      •           •
  ◄─────┴──────┴───────────┴─────────────────────►
                              LGPL        GPL
                               •           •
                              (Copyleft hybrid) (Copyleft fort)
                             COPYLEFT SIDE
                            (Obligation partage)
```

### Arbre de décision

```
Est-ce pour WordPress.org?
│
├─ OUI → Utiliser GPL v2 ou GPL v2+ ✅
│
└─ NON → Utiliser ISC ou MIT (votre choix philosophique)
         │
         ├─ Voulez-vous obligation de partage?
         │  ├─ OUI → GPL (même hors WordPress)
         │  └─ NON → ISC ou MIT
         │
         └─ Quelle complexité légale tolérez-vous?
            ├─ Minimum absolu → ISC
            ├─ Simple → MIT
            └─ Exhaustif → GPL
```

---

## Conclusion

| Licence | Est | Parfait pour |
|---------|-----|-------------|
| **ISC** | "Trop permissive pour WordPress" | JavaScript/Node.js, OpenBSD, libs |
| **MIT** | "Accepté mais neutre" | Projets génériques, libs commerciales |
| **GPL** | "Gold standard WordPress" | WordPress.org, projets open-source |

**Pour 404-alert : GPL v2+ est le choix stratégiquement meilleur.**
