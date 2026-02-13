# Forum Module - Dockerized Symfony 6 CRUD

## Overview

This project includes a forum module with two related entities: **Post** and **Comment**, with server-side validation constraints. The frontend uses the HomeSpace Bootstrap template (blog section), and the backend CRUD uses the Gentella/AdminLTE admin template style.

## Entities & Constraints

### Post
- **title** (required, 3-255 chars)
- **content** (required, min 10 chars)
- **excerpt** (optional, max 500 chars)
- **author** (required, max 180 chars)
- **isPublished** (boolean)
- **createdAt**, **updatedAt**

### Comment
- **content** (required, 3-2000 chars)
- **author** (required, max 180 chars)
- **post** (ManyToOne → Post, cascade delete)

## Docker Setup

```bash
# Build and start
docker compose up -d

# Install dependencies (run from host if composer available)
composer install

# Or run inside PHP container
docker compose exec php composer install

# Create database schema
docker compose exec php php bin/console doctrine:migrations:diff
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction

# Or create schema directly
docker compose exec php php bin/console doctrine:schema:create
```

Access:
- **Frontend**: http://localhost:8080
- **Forum**: http://localhost:8080/forum
- **Admin**: http://localhost:8080/admin
- **Posts CRUD**: http://localhost:8080/admin/forum/post
- **Comments CRUD**: http://localhost:8080/admin/forum/comment

## Without Docker

Ensure MySQL is running and update `.env`:

```
DATABASE_URL="mysql://root:@127.0.0.1:3306/pidev_db?serverVersion=8.0&charset=utf8mb4"
```

Then run:
```bash
composer install
php bin/console doctrine:schema:create
symfony server:start  # or php -S localhost:8000 -t public
```

## Routes

| Route | Description |
|-------|-------------|
| `/forum` | Forum index (published posts) |
| `/forum/post/{id}` | Post detail + comments |
| `/admin` | Admin dashboard |
| `/admin/forum/post` | Posts CRUD |
| `/admin/forum/comment` | Comments CRUD |

## Templates

- **Frontend**: `templates/frontOffice/forum/` (HomeSpace blog section styles)
- **Backend**: `templates/backOffice/forum/` (Gentella/AdminLTE table style)
