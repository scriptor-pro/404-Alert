# Comparaison : WordPress Local Docker vs Direct

## Tableau comparatif

| Aspect | Docker | Direct (sans Docker) |
|---|---|---|
| **Temps d'installation** | 5-10 min | 15-30 min |
| **Complexité initiale** | Faible (1 fichier YAML) | Moyenne (plusieurs étapes) |
| **Compétences requises** | Docker basic | PHP, MySQL, serveur web |
| **Espace disque** | ~2-3 GB (images) | ~500 MB |
| **Mémoire RAM utilisée** | ~500-800 MB | ~200-400 MB |
| **Isolation de l'OS** | ✅ Oui (conteneur) | ❌ Partage l'OS |
| **Reproductibilité** | ✅ Identique partout | ⚠️ Dépend de ta config |
| **Facilité de cleanup** | ✅ `docker-compose down -v` | ⚠️ Suppression manuelle |
| **Risque de conflits système** | ❌ Aucun | ⚠️ Ports/services en conflit |
| **Performances** | ~95% des performances natives | 100% (natif) |
| **Accès à la base de données** | Via MySQL CLI ou phpMyAdmin | Direct sur le système |
| **Logs visibles** | ✅ `docker-compose logs` | Via fichiers système |
| **Répliquabilité** | ✅ Facile (partager le YAML) | ❌ Difficile |
| **Courbe d'apprentissage** | 🟢 Courte | 🟠 Moyenne-longue |
| **Support des hotfixes** | ✅ Facile (rebuild image) | ⚠️ Manuel |

---

## Détail des avantages et inconvénients

### Docker ✅

#### ✅ Avantages
1. **Zéro dépendance système** — Pas besoin d'installer PHP, MySQL, Apache en local
2. **Isolation complète** — WordPress en Docker n'affecte pas ton système
3. **Cleanup parfait** — `docker-compose down -v` = tout est effacé proprement
4. **Reproductibilité** — Même setup sur Mac, Linux, Windows
5. **Facile à partager** — Envoyer le `docker-compose.yml` et c'est bon
6. **Pas de port en conflit** — MySQL Docker n'interfère pas avec ton MySQL système
7. **Temps d'installation** — 5 min chrono
8. **Versions contrôlées** — Tu choisis exactement quelle version de PHP, MySQL, WordPress
9. **Logs centralisés** — Tout visible via `docker-compose logs`
10. **Snapshots faciles** — Sauvegarder l'état et le restaurer

#### ❌ Inconvénients
1. **Prérequis Docker** — Faut installer et configurer Docker
2. **Espace disque** — ~2-3 GB pour les images
3. **Apprentissage Docker** — Courbe initiale (mais courte)
4. **Légèrement plus lent** — ~5% de overhead vs natif
5. **Virtualisation** — Pas idéal si ta machine est déjà sous-alimentée en RAM

#### 🎯 Idéal pour
- Tests rapides et isolés
- Déploiement reproductible
- Prototyper le plugin avant production
- Équipe qui partage la même config

---

### Direct (sans Docker) ✅

#### ✅ Avantages
1. **Zéro overhead** — Vrai WordPress sur vrai serveur web
2. **Performance maximale** — Aucune virtualisation
3. **Pas de prérequis spécialisé** — Juste PHP, MySQL, Apache (outils standards)
4. **Persistance** — Une fois installé, tu touches plus à rien
5. **Moins d'espace disque** — ~500 MB (vs 2-3 GB Docker)
6. **Moins de RAM** — ~200-400 MB (vs 500-800 MB Docker)
7. **Debugging natif** — Xdebug, erreurs PHP = intégration totale
8. **Flexibilité** — Tu peux modifier Apache, PHP, MySQL facilement
9. **Production-like** — Setup très proche d'un vrai serveur web

#### ❌ Inconvénients
1. **Temps d'installation** — 15-30 min (plusieurs étapes)
2. **Dépendances système** — Faut avoir PHP, MySQL, Apache/Nginx
3. **Pas d'isolation** — WordPress touche ton système
4. **Risque de conflits** — Si tu as déjà Apache/MySQL qui tournent
5. **Cleanup manuel** — Faut supprimer manuellement base de données, fichiers, virtualhost
6. **Non-reproductible** — L'autre dev a une config différente
7. **Difficile à partager** — Explications compliquées pour reproduire
8. **Logs éparpillés** — Fichiers système, MySQL logs, Apache logs
9. **Maintenance** — Faut mettre à jour PHP, MySQL, Apache toi-même

#### 🎯 Idéal pour
- Dev stable et long terme (tu veux garder WordPress en local)
- Performance maximale
- Si tu maîtrises déjà PHP/MySQL/Apache
- Si tu as peu de RAM ou peu d'espace disque
- Setup très proche de ta production

---

## Cas d'usage

### Utilise Docker si...
- ✅ Tu veux tester le plugin en 5 minutes
- ✅ Tu aimes l'ordre et le cleanup facile
- ✅ Tu veux éviter de casser ton système
- ✅ Tu dois partager le setup avec une équipe
- ✅ Tu fais des tests rapides et répétés
- ✅ Tu n'as jamais touché à PHP/MySQL

### Utilise Direct si...
- ✅ Tu as déjà PHP/MySQL/Apache installés
- ✅ Tu maîtrises la configuration des serveurs
- ✅ Tu veux de la performance maximale
- ✅ Tu veux garder WordPress en local longtemps
- ✅ Tu fais du dev PHP standard (pas juste WordPress)
- ✅ Tu as peu de RAM/disque

---

## Recommandation pour tester 404 Alert

### 🥇 **Si tu n'as jamais fait ça : Docker**

**Pourquoi :**
- T'auras WordPress en 5 min
- Aucun risque de casser ton système
- Cleanup ultra-facile après
- Tu peux tester le plugin et l'oublier sans laisser de traces

### 🥈 **Si tu dev PHP régulièrement : Direct**

**Pourquoi :**
- Tu as sûrement déjà la stack installée
- Plus simple à long terme
- Performance parfaite pour tester

### 🥉 **Si tu hésites : Docker**

La courbe d'apprentissage Docker est courte et très utile en général.

---

## Commandes rapides

### Docker
```bash
# Installation
mkdir wordpress-test && cd wordpress-test
cat > docker-compose.yml << 'EOF'
version: '3.8'
services:
  wordpress:
    image: wordpress:latest
    ports:
      - "8080:80"
    environment:
      WORDPRESS_DB_HOST: db
      WORDPRESS_DB_NAME: wordpress
      WORDPRESS_DB_USER: wordpress
      WORDPRESS_DB_PASSWORD: wordpress123
    volumes:
      - ./wp-content/plugins:/var/www/html/wp-content/plugins
    depends_on:
      - db
  db:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: wordpress
      MYSQL_ROOT_PASSWORD: root123
      MYSQL_USER: wordpress
      MYSQL_PASSWORD: wordpress123
    volumes:
      - db_data:/var/lib/mysql
volumes:
  db_data:
EOF

# Démarrer
docker-compose up -d

# Accéder
# http://localhost:8080

# Copier le plugin
cp -r /home/Baudouin/Documents/Projets/404-alert wp-content/plugins/

# Arrêter
docker-compose down -v
```

### Direct
```bash
# Installation
cd ~/Documents/Projets
wget https://wordpress.org/latest.zip && unzip latest.zip
cd wordpress

# Créer la BD
mysql -u root -p << 'EOF'
CREATE DATABASE wordpress_local;
CREATE USER 'wordpress'@'localhost' IDENTIFIED BY 'wordpress123';
GRANT ALL PRIVILEGES ON wordpress_local.* TO 'wordpress'@'localhost';
FLUSH PRIVILEGES;
EOF

# Configurer
cp wp-config-sample.php wp-config.php
nano wp-config.php
# Remplacer les credentials

# Démarrer
php -S localhost:8080

# Accéder
# http://localhost:8080

# Copier le plugin
cp -r /home/Baudouin/Documents/Projets/404-alert wp-content/plugins/

# Arrêter
# Ctrl+C
```

---

## Conclusion

| | Docker | Direct |
|---|---|---|
| **Pour démarrer vite** | 🏆 | |
| **Pour long terme** | | 🏆 |
| **Pour l'ordre** | 🏆 | |
| **Pour la perf** | | 🏆 |
| **Pour apprendre** | 🏆 | |
| **Pour le pragmatisme** | | 🏆 |

**Choix du jour :** Quelle option préfères-tu ?
