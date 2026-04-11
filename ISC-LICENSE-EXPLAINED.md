# La licence ISC expliquée

## Vue d'ensemble

La **ISC License** (Internet Software Consortium License) est une licence open-source **très permissive et minimaliste** publiée par l'Internet Systems Consortium. C'est l'une des licences les plus simples et les plus "légères" du paysage open-source.

---

## Le texte complet (8 lignes)

```
ISC License (ISCL)

Copyright (c) [YEAR], [COPYRIGHT HOLDER]

Permission to use, copy, modify, and/or distribute this software for any
purpose with or without fee is hereby granted, provided that the above
copyright notice and this permission notice appear in all copies.

THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
```

---

## Décortication clause par clause

### ✅ PERMISSIONS (Ce que vous POUVEZ faire)

#### 1. **Usage commercial**
- ✅ Vous pouvez vendre du logiciel basé sur du code ISC
- ✅ Vous pouvez l'incorporer dans des produits propriétaires payants
- ✅ Aucune restriction sur l'utilisation commerciale

**Exemple :** Une startup peut prendre du code ISC public, l'améliorer, et vendre le résultat à des clients sans partager le code modifié.

#### 2. **Modification**
- ✅ Vous pouvez modifier le code comme vous le souhaitez
- ✅ Pas de "viral" - modifications n'obligent pas à rester open-source
- ✅ Vous pouvez améliorer, simplifier, optimiser librement

**Exemple :** Si vous trouvez un bug, vous le fixez pour vous-même sans obligation de partager.

#### 3. **Distribution**
- ✅ Vous pouvez redistribuer le code original
- ✅ Vous pouvez distribuer votre version modifiée
- ✅ Pas de chaîne d'attribution imposée

**Exemple :** Vous pouvez prendre du code ISC, le copier dans votre projet, le vendre, sans devoir en informer l'auteur original.

#### 4. **Usage privé**
- ✅ Vous pouvez utiliser le code en privé sans rien divulguer
- ✅ Zéro obligation de partage

---

### ⚙️ CONDITIONS (Ce que vous DEVEZ faire)

C'est la partie la plus courte. ISC n'a que **2 conditions minimales** :

#### 1. **Conserver l'avis de copyright original**
```
Copyright (c) [ANNÉE], [AUTEUR ORIGINAL]
```
- Vous DEVEZ garder cet avis dans vos fichiers sources
- Pas d'effacement ou d'altération
- C'est la seule trace légale qui dit "ceci vient de..."

#### 2. **Inclure le texte de licence dans toute copie**
```
Permission to use, copy, modify, and/or distribute this software...
```
- Si vous distribuez le logiciel (source ou binaire), inclure la licence
- Dans les sources : commentaire dans le fichier
- Dans les binaires : généralement dans un fichier LICENSE.txt ou LICENSE.md

**C'est tout.** Deux conditions. Très simple.

---

### ❌ LIMITATIONS (Ce que vous POUVEZ'T pas faire)

#### 1. **Pas de garantie (Disclaimer)**
```
THE SOFTWARE IS PROVIDED "AS IS"
```
- L'auteur ne donne AUCUNE garantie
- Pas de promesse de fonctionnement
- Si ça casse, l'auteur n'est pas responsable
- **Pas d'obligation de support**

**Implication :** Si vous intégrez du code ISC et qu'il cause des dégâts, vous ne pouvez pas poursuivre l'auteur original.

#### 2. **Aucune responsabilité (Liability)**
```
IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT, 
INDIRECT, OR CONSEQUENTIAL DAMAGES...
```
- L'auteur n'est pas responsable de :
  - **Dégâts directs** (crash, perte de données)
  - **Dégâts indirects** (perte d'affaires, interruption service)
  - **Dégâts spéciaux** (tout ce qui se passe à cause du code)
  - **Tout dégât résultant** de l'usage du software

**Implication :** Vous êtes responsable du code une fois que vous l'utilisez.

---

## Comparaison avec MIT et BSD

| Aspect | ISC | MIT | BSD 3-Clause |
|--------|-----|-----|---|
| **Taille** | 8 lignes | 11 lignes | 15 lignes |
| **Permissions** | Identiques | Identiques | Identiques |
| **Conditions** | Copyright + Licence | Copyright + Licence | Copyright + Licence + No endorsement |
| **Complexité** | Minimaliste | Minimaliste | Un peu plus complex |
| **Différence** | Supprime "language inutile" post-Berne Convention | Plus traditionnel | Interdit utiliser nom pour endorsement |
| **Popularité** | Modérée (Node.js, Vue.js) | Très haute (npm default) | Moyenne |

### Pourquoi ISC < MIT/BSD ?

ISC est **techniquement presque identique** à MIT/BSD mais avec **moins de langage légal**. Selon ISC :
- La Berne Convention couvre automatiquement certaines protections
- MIT/BSD ont du langage "redondant" selon la convention
- ISC simplifie en supprimant la redondance

**En pratique :** zéro différence légale réelle. ISC simplifie juste les mots.

---

## Caractéristiques clés

### ✅ Points forts
1. **Minimaliste** - 8 lignes, ultra-simple
2. **Permissive** - presque pas de restrictions
3. **Usage commercial libre** - pas de conditions
4. **Modifications libres** - pas de "viral"
5. **Bien établie** - reconnue par OSI et FSF
6. **Compatible GPL** - peut se combiner avec code GPL
7. **Default npm** - licence par défaut de npm init

### ❌ Points faibles
1. **Peu d'attribution** - aucune obligation d'attribution en cascade
2. **"Fermeture" possible** - code dérivé peut devenir propriétaire
3. **Pas de copyleft** - ne force pas le partage d'améliorations
4. **Moins idéologique** - ne s'aligne à aucune vision particulière
5. **Pas de garantie** - auteur zéro responsabilité (comme MIT/BSD)
6. **Rare pour plugins** - peu utilisée dans écosystèmes WordPress

---

## Quand utiliser ISC

### ✅ Bons cas d'usage

1. **Bibliothèques JavaScript/Node.js**
   - C'est la licence npm par défaut
   - Largement utilisée pour packages Node

2. **Code fortement réutilisable**
   - Vous voulez maximiser la réutilisation
   - Peu de restrictions = adoption plus facile

3. **Projets nécessitant commercialisation**
   - Vous voulez permettre usage propriétaire
   - Consultants, agences, startups

4. **Contributions à OpenBSD**
   - ISC est la licence préférée d'OpenBSD
   - Standard pour projets BSD

5. **Code sans prétentions idéologiques**
   - Vous voulez simplement partager du code
   - Pas de manifeste open-source

### ❌ Mauvais cas d'usage

1. **Plugins WordPress** ❌
   - WordPress.org refuse ISC
   - Exige GPL-compatible
   - **Cas du 404-alert : EXACTEMENT ce problème**

2. **Projets avec vision open-source**
   - GPL ou MIT mieux alignés
   - ISC "trop permissive" pour ideologues

3. **Code critique de sécurité**
   - Vous voulez des garanties
   - ISC = zéro responsabilité auteur

4. **Écosystèmes non-JS**
   - PHP, Python, Ruby : MIT/GPL/Apache norm
   - ISC peu compris hors JS/Node

5. **Projets commerciaux avec base communautaire**
   - Vous voulez que les améliorations reviennent
   - GPL "viral" mieux

---

## Cas réel : 404-alert

### Le problème
```
Licence déclarée : ISC
Hebergement visé : WordPress.org
Résultat : REJET AUTOMATIQUE ❌
```

### Pourquoi WordPress.org refuse
WordPress.org a une **politique stricte : GPL-compatible uniquement**. Raison :
- WordPress lui-même est GPLv2+
- Tous les plugins hébergés doivent être GPL-compatible
- ISC est techniquement GPL-compatible légalement ✓
- **MAIS WordPress.org refuse explicitement ISC de facto** ❌

La raison culturelle/politique :
- WordPress veut tous les plugins "open-source perpetuellement"
- GPL-copyleft force cela
- ISC ne force rien ("fermeture possible")
- WordPress.org préfère culture GPL

### La solution
Changer ISC → **GPLv2+**
```
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
```

**Effet :** acceptation automatique, zéro problèmes.

---

## Texte complet de ISC (pour référence)

```
ISC License (ISCL)

Copyright (c) [ANNÉE], [TITULAIRE DU COPYRIGHT]

Permission to use, copy, modify, and/or distribute this software for any
purpose with or without fee is hereby granted, provided that the above
copyright notice and this permission notice appear in all copies.

THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
```

---

## Ressources officielles

- [ISC License - Open Source Initiative](https://opensource.org/licenses/ISC)
- [ISC Licenses Page](https://www.isc.org/licenses/)
- [ISC License on TLDRLegal](https://www.tldrlegal.com/license/isc-license)
- [FOSSA Blog - ISC License](https://fossa.com/blog/open-source-software-licenses-101-isc-license/)
- [Wikipedia - ISC License](https://en.wikipedia.org/wiki/ISC_license)

---

## Résumé court

**ISC** = MIT/BSD mais avec moins de langage légal (8 lignes au lieu de 11+)

| Aspect | Résumé |
|--------|--------|
| **Permissivité** | Maximale - "fais ce que tu veux" |
| **Copyleft** | Aucun - aucune obligation de partage |
| **Commercial** | ✅ Libre et encouragé |
| **Conditions** | Minimales : copyright + licence notice |
| **Garantie** | Aucune - "as is" |
| **Responsabilité** | Zéro pour auteur |
| **Popularité** | Modérée (surtout JavaScript/Node.js) |
| **WordPress.org** | ❌ **REJETÉE** |

**Verdict pour 404-alert :** ISC n'est **PAS acceptée par WordPress.org**. Changer en **GPLv2+** obligatoire.
