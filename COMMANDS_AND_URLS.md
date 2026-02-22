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

## Mailing (alertes dislikes)

Un email est envoyé à ADMIN_EMAIL quand un post/commentaire atteint 5 dislikes.

**Setup :**
1. **Messenger** : `docker compose exec php php bin/console messenger:setup-transports`
2. **Mailtrap** : `MAILER_DSN=smtp://USER:PASSWORD@sandbox.smtp.mailtrap.io:2525` + `MAILER_FROM=...`
3. **Brevo** (vrais emails) : `MAILER_DSN=brevo+smtp://EMAIL:CLE_SMTP@default`
4. Test : `docker compose exec php php bin/console app:test-mail` ou http://localhost:8000/forum/test-mail

## AI Assistant

Chatbot forum : cliquez sur l’icône robot sur les pages forum. Sans clé : réponses basiques ("Combien de posts ?", etc.). Avec OpenAI : ajouter `OPENAI_API_KEY=sk-...` dans `.env` pour des réponses plus complètes.

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

## Frontend (HomeSpace) – Port 8000

| Page | URL | Méthode |
|------|-----|---------|
| Accueil | http://localhost:8000/ | GET |
| Accueil (alt) | http://localhost:8000/home | GET |
| Nos Logements | http://localhost:8000/properties | GET |
| Forum (liste des posts) | http://localhost:8000/forum | GET |
| Forum (page 2) | http://localhost:8000/forum?page=2 | GET |
| Nouveau post | http://localhost:8000/forum/post/new | GET, POST |
| Forum /post (redirige) | http://localhost:8000/forum/post | GET |
| Détail d’un post + commentaires | http://localhost:8000/forum/post/1 | GET, POST |

*(Remplace 1 par l’ID du post)*

---

## Backend Admin (Gentella/AdminLTE) – Port 8000

| Page | URL | Méthode |
|------|-----|---------|
| Dashboard | http://localhost:8000/admin | GET |
| Forum (redirige vers posts) | http://localhost:8000/admin/forum | GET |

### Posts

| Action | URL | Méthode |
|--------|-----|---------|
| Liste des posts | http://localhost:8000/admin/forum/post | GET |
| Liste (page 2) | http://localhost:8000/admin/forum/post?page=2 | GET |
| Détail post | http://localhost:8000/admin/forum/post/1 | GET |
| Modifier post | http://localhost:8000/admin/forum/post/1/edit | GET, POST |
| Supprimer post | http://localhost:8000/admin/forum/post/1/delete | POST |

*Création de posts : sur le frontend (Forum > Nouveau post)*

### Commentaires

| Action | URL | Méthode |
|--------|-----|---------|
| Liste des commentaires | http://localhost:8000/admin/forum/comment | GET |
| Liste filtrée par post | http://localhost:8000/admin/forum/comment?post_id=1 | GET |
| Liste (page 2) | http://localhost:8000/admin/forum/comment?page=2 | GET |
| Voir commentaire (frontend) | http://localhost:8000/admin/forum/comment/1 | GET |
| Supprimer commentaire | http://localhost:8000/admin/forum/comment/1/delete | POST |

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

Puis ouvrir : **http://localhost:8000**
