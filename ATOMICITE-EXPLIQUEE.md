# 🔬 L'Atomicité Expliquée en Détail

**Objectif** : Comprendre pourquoi l'atomicité est cruciale pour le rate limiting, et comment elle fonctionne.

---

## 📖 Définition Simple

**Atomique** = Une opération qui ne peut PAS être partiellement exécutée. Soit elle réussit entièrement, soit elle échoue complètement. Pas d'état intermédiaire.

```
❌ Non-atomique (risqué) :
  LECTURE → [FENÊTRE DE RACE] → VÉRIFICATION → [FENÊTRE DE RACE] → ÉCRITURE

✅ Atomique (sûr) :
  LECTURE + VÉRIFICATION + ÉCRITURE = UNE SEULE opération
```

---

## 🎯 Exemple Concret : Le Distributeur Automatique

### Scénario : Compte bancaire avec solde de $100

```
Compte: $100
Limite quotidienne: $50/jour

Jean accède au distributeur
Pierre accède au distributeur SIMULTANÉMENT
```

### ❌ NON-ATOMIQUE (Ce qui se passe en 404-alert actuellement)

```
Temps | Jean                          | Pierre
-----|-------------------------------|--------------------------------
 T1  | LIRE solde ($100)             | (attend)
 T2  | (opération lente)             | LIRE solde ($100)
 T3  | VÉRIFIER: $100 < $50/jour? OUI| (opération lente)
 T4  | ÉCRIRE solde = $50            | VÉRIFIER: $100 < $50/jour? OUI
 T5  | (retour au compte)            | ÉCRIRE solde = $50
 T6  |                               | (retour au compte)

Résultat: Les deux ont retiré $50 ! 💥
Solde final = $50 (au lieu de vider vraiment à $50 total)
```

**Pourquoi ?** Entre T2 et T4, aucun verrou n'existe vraiment. Les deux lisent la même valeur.

### ✅ ATOMIQUE (Ce qui devrait se passer)

```
Temps | Jean                                      | Pierre
-----|---------------------------------------------|------
 T1  | LIRE + VÉRIFIER + ÉCRIRE (en UNE opération) | (attend)
 T2  | ✅ Jean a retiré $50, solde = $50          | (attend)
 T3  | (distributeur verrouillé, aucun accès)     | (attend)
 T4  | (Jean sort sa carte)                       | LIRE + VÉRIFIER + ÉCRIRE
 T5  | (Pierre essaie)                            | ❌ Pierre: "Dépassé ta limite!"
 T6  |                                            | (Pierre ne peut rien retirer)

Résultat: Un seul a retiré $50. Limite respectée. ✅
```

**Pourquoi ça marche ?** Toute l'opération (lire + vérifier + écrire) se fait d'un coup, sans fenêtre de race.

---

## 🔴 Fenêtres de Race (Race Condition)

Une **fenêtre de race** est le temps entre deux opérations **non-atomiques** où deux processus peuvent interférer.

### Visualisation Timeline

```
Opération NON-ATOMIQUE:

Processus 1:  LIRE    [fenêtre]  ÉCRIRE
Processus 2:         LIRE  [fenêtre]  ÉCRIRE

              ↓
              RACE! Les deux lisent avant que l'un écrive
              Résultat imprévisible.
```

### Code réel (404-alert)

```php
// class-rate-limiter.php:57-70
$last = get_transient( $key );  // ← LECTURE (T1)
                                // [FENÊTRE DE RACE: T2-T4]
if ( $last !== false && ( time() - (int) $last ) < $cooldown ) {
    return false;  // ← VÉRIFICATION (T3)
}
                                // [FENÊTRE DE RACE: T4-T6]
set_transient( $key, time(), $cooldown );  // ← ÉCRITURE (T5)
return true;
```

### Scénario de Race Condition réelle

```
Configuration:
  - IP: 192.168.1.100
  - Cooldown: 300 secondes
  - Transient: "404_alert_ip_HASH" = false (vide)

Requête 1 (T1-T5):
  T1:  get_transient( "404_alert_ip_HASH" ) = false
  T2:  (calcul, préparation)
  T3:  Vérification: false? Oui, on continue
  T4:  (calcul, préparation)
  T5:  set_transient( "404_alert_ip_HASH", time(), 300 )
       → Transient créé avec timestamp 1000

Requête 2 (T1-T5) SIMULTANÉE:
  T1:  get_transient( "404_alert_ip_HASH" ) = false  ← AVANT T5!
  T2:  (calcul, préparation)
  T3:  Vérification: false? Oui, on continue
  T4:  (calcul, préparation)
  T5:  set_transient( "404_alert_ip_HASH", time(), 300 )
       → Transient écrasé avec timestamp 1001

Résultat:
  ✅ Requête 1 passe (email envoyé)
  ✅ Requête 2 passe (email envoyé) ← DEVRAIT ÊTRE BLOQUÉE!

Rate limit contourné! 💥
```

---

## 🔒 Comment Obtenir l'Atomicité

### 1️⃣ Au Niveau du Hardware (CPU)

Les processeurs modernes ont des instructions **atomiques** :

```assembly
; Pseudo-code x86
; CMPXCHG = Compare And Swap
; Atomique = une seule instruction CPU, pas d'interruption possible

cmpxchg [adresse_memoire], nouveaux_données
; SI [adresse_memoire] == valeur_attendue
;   ALORS: écrire nouveaux_données (et retourner true)
;   SINON: ne rien faire (et retourner false)
; Tout d'un coup, sans interruption
```

**Avantage** : Vrai atomicité au niveau matériel.
**Inconvénient** : Non disponible en PHP (PHP s'exécute via le système d'exploitation, qui peut l'interrompre).

### 2️⃣ Au Niveau de la Base de Données

Les bases de données offrent des **transactions atomiques** :

```sql
-- MySQL: Transaction ACID
BEGIN;
  SELECT * FROM rate_limit WHERE ip = '192.168.1.100' FOR UPDATE;
  -- ^^ Verrou exclusif, autres transactions attendent
  
  IF count < 500 THEN
    UPDATE rate_limit SET count = count + 1 WHERE ip = '192.168.1.100';
  ELSE
    ROLLBACK;  -- Annuler tout
  END IF;
COMMIT;
-- ^^ Tout succeed ou tout échoue, pas d'intermédiaire
```

**Avantage** : Vraie atomicité via transactions.
**Inconvénient** : Lent (I/O réseau + disque).

### 3️⃣ Au Niveau du Magasin de Clé-Valeur (Redis)

Redis offre des **opérations atomiques** :

```
Redis est **single-threaded**.
Chaque commande est exécutée du début à la fin avant la suivante.
```

#### Commande non-atomique (Redis):

```bash
# Deux commandes = deux opérations distinctes
GET key              # Opération 1
SET key newvalue     # Opération 2

# Entre = fenêtre de race
GET key              # Client A: lit "old"
GET key              # Client B: lit "old"
SET key newA         # Client A: écrit "newA"
SET key newB         # Client B: écrit "newB"
# B a écrasé A!
```

#### Commande atomique (Redis):

```bash
# UNE SEULE commande = UNE SEULE opération
SET key newvalue EX 300 NX

# Cela signifie:
#   SET: écrire la valeur
#   EX 300: expiration en 300 secondes
#   NX: only if Not eXists
# Tout d'un coup = atomique
```

**Pourquoi atomique** : Redis exécute chaque commande entièrement avant la suivante. Aucun entrelacement possible.

```
Client A:  SET key valueA NX   [opération atomique complète]
Client B:              SET key valueB NX   [attend]
           ↓                    ↓
      Opération A réussit  Opération B échoue (NX)
```

---

## 📊 Comparaison : Atomicité vs Non-Atomique

### Scenario : Rate limit = 5 emails/jour

```
Jour 1: 4 emails envoyés
        Compteur = 4

Jour 2: 00:00:00 UTC - Nouveau jour
        Compteur reset = 0
        
        50 requêtes simultanées arrivent

SANS ATOMICITÉ (Option 3 - Simple):
  Requête 1: LIRE (0) → ÉCRIRE (1)
  Requête 2: LIRE (0) → ÉCRIRE (1)
  Requête 3: LIRE (0) → ÉCRIRE (1)
  Requête 4: LIRE (0) → ÉCRIRE (1)
  Requête 5: LIRE (0) → ÉCRIRE (1)
  Requête 6: LIRE (1) → ÉCRIRE (2)
  ... etc ...
  Requête 50: LIRE (48) → ÉCRIRE (49)
  
  Résultat: ~50 emails au lieu de 5! ❌
  Dépassement: 1000% (inacceptable)

AVEC ATOMICITÉ (Option 2 - Redis):
  Requête 1: GET + SET (NX) = SUCCESS, compteur = 1
  Requête 2: GET + SET (NX) = FAIL, compteur = 2
  Requête 3: GET + SET (NX) = FAIL, compteur = 3
  Requête 4: GET + SET (NX) = FAIL, compteur = 4
  Requête 5: GET + SET (NX) = FAIL, compteur = 5
  Requête 6: GET + SET (NX) = FAIL, compteur = 6 (reject)
  ... toutes les autres = FAIL ...
  
  Résultat: 5 emails exactement ✅
  Respect du limit: 100%
```

---

## 🎬 Simulation en Détail : Requête Simultanée

### Configuration

```
Rate limit: 2 emails par minute par IP
Transient key: "404_alert_ip_hash123"
Cooldown: 60 secondes
```

### Timeline Complète (3 requêtes simultanées)

```
Temps (ms) | Requête 1              | Requête 2              | Requête 3
-----------|------------------------|------------------------|------------------------
0          | LIRE transient         |                        |
1          |                        | LIRE transient         |
2          |                        |                        | LIRE transient
3          | (get_transient=false)  |                        |
4          |                        | (get_transient=false)  |
5          |                        |                        | (get_transient=false)
10         | VÉRIF: false → PASS    |                        |
11         |                        | VÉRIF: false → PASS    |
12         |                        |                        | VÉRIF: false → PASS
15         | ÉCRIRE transient=1000  |                        |
16         |                        | ÉCRIRE transient=1001  |
17         |                        |                        | ÉCRIRE transient=1002

RÉSULTAT: 3 emails envoyés (pas de rate limit!) ❌
```

**Pourquoi ?** Chaque requête a lu la même valeur (false) avant qu'aucune n'écrive. Elles se sont croisées.

---

## ✅ Avec Atomicité (Redis)

```
Temps (ms) | Redis SET NX
-----------|--------------------------------------------
0          | Requête 1: SET key value NX EX 60
1          | ✅ SUCCESS (clé crée)
2          | Requête 2: SET key value NX EX 60
3          | ❌ FAIL (clé existe déjà) ← Atomique!
4          | Requête 3: SET key value NX EX 60
5          | ❌ FAIL (clé existe déjà)

RÉSULTAT: 1 email envoyé (rate limit respecté) ✅
```

**Pourquoi ça marche** : La commande Redis `SET ... NX` est atomique. Elle lit ET écrit d'un coup, sans fenêtre de race.

---

## 🧠 Pourquoi WordPress Options ne Sont PAS Atomiques

```php
// Approche WordPress (non-atomique)
$current = get_option( 'counter' );      // ← LECTURE
$new_value = $current + 1;
update_option( 'counter', $new_value );  // ← ÉCRITURE

// Processus 1 (T1-T3):
//   T1: get_option() = 5
//   T2: calcul: 5 + 1 = 6
//   T3: update_option( 6 )

// Processus 2 (T1-T3) SIMULTANÉ:
//   T1: get_option() = 5      ← LIT LA MÊME VALEUR!
//   T2: calcul: 5 + 1 = 6
//   T3: update_option( 6 )    ← ÉCRASE la valeur de P1

// Résultat: compteur = 6 (au lieu de 7)
// Une incrmentation a été perdue!
```

### Pourquoi ?

```
WordPress n'a PAS de fonction atomique de type:
  - compare_and_swap()
  - increment_with_check()
  - atomic_set_if_not_exists()

Les seules options sont:
  - get_option() + update_option() = non-atomique
  - update_option() seul = écrase sans vérifier
  - delete_option() + add_option() = toujours non-atomique
```

**Conclusion** : WordPress stocke les données en base MySQL, qui peut traiter plusieurs requêtes en parallèle. Pas de garantie d'atomicité sans transactions explícites.

---

## 🔑 Redis: Pourquoi c'EST Atomique

```
Architecture Redis:
┌─────────────────────────────────┐
│      Client 1                   │
│   SET key1 value1 NX EX 60      │
│          ↓                       │
│    ┌──────────────────┐        │
│    │  REDIS SERVER    │        │
│    │ (single-thread)  │        │
│    │                  │        │
│    │ Queue:           │        │
│    │ 1. SET ... (en cours)     │
│    │ 2. GET ...  (attend)      │
│    │ 3. SET ... (attend)       │
│    │                  │        │
│    └──────────────────┘        │
│          ↓                       │
│    ┏━━━━━━━━━━━━━━━━┓         │
│    ┃ Exécution #1: ┃         │
│    ┃ - Lire clé    ┃         │
│    ┃ - Vérifier NX ┃         │
│    ┃ - Écrire      ┃         │
│    ┃ - Set expiry  ┃         │
│    ┃ (ATOMIQUE!)   ┃         │
│    ┗━━━━━━━━━━━━━━━━┛         │
│          ↓                       │
│   Réponse: true/false           │
└─────────────────────────────────┘

Redis traite chaque commande COMPLÈTEMENT
avant de passer à la suivante.
Aucun entrelacement = atomique.
```

**Clé** : Redis est **single-threaded**. Une seule commande à la fois. Pas de parallélisme.

Comparé à MySQL:

```
MySQL (multi-threaded):
┌──────────┐
│ Thread 1 │ GET key
│          ├─── Exécution entrelacée ──────┐
│ Thread 2 │ SET key                        │ Race condition!
│          └─── fenêtres de race ────────────┘
└──────────┘

Redis (single-threaded):
┌──────────┐
│ Command  │ SET key NX EX 60 (COMPLÈTE)
│    1     │        ↓ (réponse)
│          │ GET key (MAINTENANT)
│    2     │        ↓ (réponse)
└──────────┘
Séquentiel = atomique par design
```

---

## 🎓 Cas Concret: Vérrou dans 404-alert

### Code actuel (CASSÉ)

```php
private static function acquire_lock( string $lock_key, int $timeout ): bool {
    while ( time() - $start < $timeout ) {
        $lock = get_transient( $lock_key );  // ← LECTURE non-atomique
        
        if ( $lock === false ) {
            set_transient( $lock_key, $lock_value, ... );  // ← ÉCRITURE
            
            $new_lock = get_transient( $lock_key );  // ← RE-LECTURE (vérification)
            if ( $new_lock === $lock_value ) {
                return true;  // Vérrou acquis
            }
        }
        usleep( 10000 );
    }
    return false;
}
```

**Race condition** :

```
T1: Requête A: get_transient() = false
T2: Requête B: get_transient() = false ← A n'a pas encore écrit!

T3: Requête A: set_transient( lockA )
T4: Requête B: set_transient( lockB ) ← Écrase A!

T5: Requête A: get_transient() → lockB (pas lockA!)
    A croit que le verrou N'a pas été acquis → racquiert le verrou

T6: Requête B: get_transient() → lockB (correct)
    B croit que le verrou a été acquis

RÉSULTAT: Les deux requêtes pensent avoir le verrou! ❌
```

### Avec Redis (CORRECT)

```php
private static function acquire_lock( string $lock_key, int $timeout ): bool {
    $redis = self::get_redis();
    
    // SET key value NX EX timeout
    // Atomique: vérifie + écrit d'un coup
    return (bool) $redis->set(
        $lock_key,
        wp_hash( uniqid() ),
        'EX', $timeout,
        'NX'  // Only if Not eXists
    );
}
```

**Pas de race condition** :

```
T1: Requête A: SET lock valueA NX EX 60
    ✅ SUCCESS (verrou acquis)

T2: Requête B: SET lock valueB NX EX 60
    ❌ FAIL (clé existe) ← Atomique!

RÉSULTAT: Une seule requête a le verrou ✅
```

Redis garantit que la vérification (NX) et l'écriture (SET) sont une seule opération indivisible.

---

## 📈 Résumé Visual

```
┌─────────────────┬──────────────┬──────────────┬─────────────────────┐
│ Approche        │ Atomique?    │ Race cond?   │ Performance         │
├─────────────────┼──────────────┼──────────────┼─────────────────────┤
│ WordPress Opts  │ ❌ Non       │ ✅ Probable  │ Lent (I/O DB)       │
│ MySQL Trans     │ ✅ Oui       │ ✅ Non       │ Lent (locks DB)     │
│ Redis SET NX    │ ✅ Oui       │ ✅ Non       │ Rapide (< 1ms)      │
│ Simple (MVP)    │ ❌ Non       │ ✅ Probable  │ Très rapide         │
└─────────────────┴──────────────┴──────────────┴─────────────────────┘
```

---

## 🎯 Conclusion

**Atomicité** = Impossible d'interrompre l'opération au milieu.

Pour **race conditions** :
- ❌ WordPress Options = faux sentiment de sécurité
- ✅ Redis SET NX = vrai atomicité
- ⚠️ Transients simples = dépassement acceptable

Choisis en fonction de tes besoins de sécurité et d'infrastructure.
