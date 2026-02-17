# PiDev Stayzy – Commandes & URLs

## Docker

```bash
# Démarrer les conteneurs
docker compose up -d

# Arrêter les conteneurs
docker compose down

# Arrêter et supprimer les volumes (réinitialiser la BDD)
docker compose down -v

# Voir les logs
docker compose logs -f
docker compose logs database
docker compose logs php
docker compose logs nginx
```

## Composer & Symfony

```bash
# Installer les dépendances
docker compose exec php composer install

# Mettre à jour les paquets
docker compose exec php composer update

# Ajouter un paquet
docker compose exec php composer require nom/paquet
```

## Base de données

```bash
# Créer le schéma
docker compose exec php php bin/console doctrine:schema:create

# Supprimer le schéma (avec --full-database pour tout)
docker compose exec php php bin/console doctrine:schema:drop --full-database --force

# Migrations
docker compose exec php php bin/console doctrine:migrations:diff
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec php php bin/console doctrine:migrations:status
```

## Cache & Débogage

```bash
# Vider le cache
docker compose exec php php bin/console cache:clear

# Lister les routes
docker compose exec php php bin/console debug:router

# Infos Symfony
docker compose exec php php bin/console about
```

---

# URLs de l’application

## Frontend (HomeSpace) – Port 8080

| Page | URL | Méthode |
|------|-----|---------|
| Accueil | http://localhost:8080/ | GET |
| Accueil (alt) | http://localhost:8080/home | GET |
| Nos Logements | http://localhost:8080/properties | GET |
| Forum (liste des posts) | http://localhost:8080/forum | GET |
| Forum (page 2) | http://localhost:8080/forum?page=2 | GET |
| **Nouvel article** | http://localhost:8080/forum/post/new | GET, POST |
| Forum /post (redirige) | http://localhost:8080/forum/post | GET |
| Détail d’un post + commentaires | http://localhost:8080/forum/post/1 | GET, POST |
| Modifier un commentaire | http://localhost:8080/forum/post/1/comment/2/edit | GET, POST |
| Supprimer un commentaire | http://localhost:8080/forum/post/1/comment/2/delete | POST |

*(Remplace 1 par l’ID du post, 2 par l’ID du commentaire)*

---

## Backend Admin (Gentella/AdminLTE) – Port 8080

| Page | URL | Méthode |
|------|-----|---------|
| Dashboard | http://localhost:8080/admin | GET |
| Forum (redirige vers posts) | http://localhost:8080/admin/forum | GET |

### Posts

| Action | URL | Méthode |
|--------|-----|---------|
| Liste des posts | http://localhost:8080/admin/forum/post | GET |
| Liste (page 2) | http://localhost:8080/admin/forum/post?page=2 | GET |
| Nouveau post | http://localhost:8080/admin/forum/post/new | GET, POST |
| Détail post | http://localhost:8080/admin/forum/post/1 | GET |
| Modifier post | http://localhost:8080/admin/forum/post/1/edit | GET, POST |
| Supprimer post | http://localhost:8080/admin/forum/post/1/delete | POST |

### Commentaires

| Action | URL | Méthode |
|--------|-----|---------|
| Liste des commentaires | http://localhost:8080/admin/forum/comment | GET |
| Commentaires d’un post (via link) | http://localhost:8080/admin/forum/comment?post=1 | GET |
| Liste (page 2) | http://localhost:8080/admin/forum/comment?page=2 | GET |
| Modifier commentaire | http://localhost:8080/admin/forum/comment/1/edit | GET, POST |
| Supprimer commentaire | http://localhost:8080/admin/forum/comment/1/delete | POST |

*(Remplace 1 par l’ID correspondant)*

---

## MySQL

| Connexion | Valeur |
|-----------|--------|
| Host | localhost (ou `database` depuis les conteneurs) |
| Port | 3306 |
| Base | pidev_db |
| User | app |
| Password | !ChangeMe! |
| Root password | root |

---

## Démarrer le projet

```bash
cd /Users/mayas/Desktop/dev/piDev_Stayzy
docker compose up -d
docker compose exec php composer install
docker compose exec php php bin/console doctrine:schema:create   # si besoin
```

Puis ouvrir : **http://localhost:8080**
