# ⚡ Ce Qui Reste à Régler - Résumé Rapide

**Status** : 🟡 70% Production Ready  
**Temps restant** : 14-16 heures

---

## 🔴 4 Bloqueurs Critiques (6 heures)

### 1. **PHPCS: 1 Erreur Manuelle** (15 min)
```
File: class-redis-handler.php, Line 287
Problem: Empty CATCH statement detected
Solution: Ajouter un commentaire dans le catch vide
```

### 2. **Tests Redis** (4-5 heures)
- Créer: `tests/unit/Test_Alert404_Redis_Handler.php` (15 tests)
- Créer: `tests/unit/Test_Alert404_RateLimiter_Redis.php` (10 tests)
- Cible: > 90% coverage

### 3. **SMTP Configuration UI** (30 min)
- **Choix A** : Ajouter champs SMTP dans Settings page ⭐ Recommandé
- **Choix B** : Documenter configuration wp-config.php (déjà fait)

### 4. **SMTP Documentation** (1 heure)
- Créer: `SMTP-SETUP.md` (200 lignes)
- Contenu: Gmail, Office365, Sendgrid, troubleshooting

---

## 🟡 3 Problèmes Importants (8 heures)

### 5. **Test Coverage Complète** (6-7 heures)
- Améliorer tests Rate Limiter
- Tests Settings (10 tests)
- Tests SMTP Handler (10 tests)
- **Cible** : > 80% couverture totale

### 6. **Integration E2E Tests** (2-3 heures)
- 404 → Email envoyé
- Redis ↔ Fallback
- SMTP ↔ wp_mail

### 7. **Logging Complet** (1 heure)
- SMTP: connexion/erreur/succès
- Rate limiter: tous les blocs
- Redis: reconnexion

---

## 🟠 7 Problèmes Secondaires (Avant Prod)

| # | Tâche | Temps |
|---|-------|-------|
| 8 | Dashboard Widget | 2h |
| 9 | Troubleshooting.md | 1h |
| 10 | SMTP Credentials Security | 30 min |
| 11 | Clarify Feature Scope | 15 min |
| 12 | Release Planning (tag/version) | 30 min |
| 13 | Performance Baselines Doc | 1h |
| 14 | PHPStan 2.x Upgrade | 30 min |

---

## ✅ Déjà Fait

- ✅ Redis implementation (atomique)
- ✅ Rate limiter refactorisé
- ✅ PHPCS: 13/14 errors fixées (auto)
- ✅ PHPStan: 0 errors
- ✅ Documentation: 1600 lignes
- ✅ Fallback: automatique
- ✅ 100% backward compatible

---

## 🚀 Plan Minimum pour Production

### Week 1 (Lundi-Mardi): 6 heures
```
☐ Fixer PHPCS error 1 (15 min)
☐ Ajouter SMTP config UI (30 min)
☐ Écrire SMTP-SETUP.md (1h)
☐ Ajouter logging manquant (1h)
☐ Clarify scope README (15 min)
☐ Release planning (30 min)
```

### Week 1 (Mercredi-Vendredi): 8 heures
```
☐ Tests Redis Handler (2h)
☐ Tests RateLimiter improvements (1h)
☐ Tests SMTP (1.5h)
☐ Tests Settings (1.5h)
☐ Integration E2E (2h)
```

**Total** : 14 heures → **PRODUCTION READY**

---

## 📊 Risque si Skip

| Tâche | Risque Skip |
|-------|------------|
| PHPCS fix | 🟢 Faible (style only) |
| Tests Redis | 🔴 **CRITIQUE** (bugs hidden) |
| SMTP Config UI | 🟡 Moyen (users confused) |
| SMTP Doc | 🟡 Moyen (no setup guide) |
| Test coverage | 🔴 **HAUTE** (regressions) |
| Logging | 🟡 Moyen (hard to debug) |

---

## 🎯 Verdict Final

**Production Ready?** Non, reste ~14h.

**Ready for Staging?** Oui, après PHPCS + SMTP UI.

**Status** : **70% → 100% en 2 semaines**

👉 **Commencez par PHPCS fix + SMTP UI = 45 minutes**
