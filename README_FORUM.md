# Forum Module - Dockerized Symfony 6 CRUD

## Overview

This project includes a forum module with two related entities: **Post** and **Comment**, with server-side validation constraints. The frontend uses the HomeSpace Bootstrap template (blog section), and the backend CRUD uses the Gentella/AdminLTE admin template style.

**Features:**
- Create posts from the frontend
- Like/dislike (avis) on posts and comments
- Nested replies on comments
- Edit and delete comments (authors only, via session)
- AI chatbot assistant on forum pages
- Backend: manage posts (edit, delete), view and delete comments

## Entities & Constraints

### Post
- **title** (required, 3-255 chars)
- **content** (required, min 10 chars)
- **excerpt** (optional, max 500 chars)
- **author** (required, max 180 chars)
- **isPublished** (boolean)
- **avisCount** (likes), **dislikeCount**
- **createdAt**, **updatedAt**

### Comment
- **content** (required, 3-2000 chars)
- **author** (required, max 180 chars)
- **post** (ManyToOne → Post, cascade delete)
- **parent** (ManyToOne → Comment, for replies)
- **avisCount** (likes), **dislikeCount**
- **createdAt**

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

### Frontend

| Route | Description |
|-------|-------------|
| `/forum` | Forum index (published posts) |
| `/forum/post/new` | Create new post |
| `/forum/post/{id}` | Post detail + comments |
| `/forum/comment/{id}/edit` | Edit comment (author only) |
| `/forum/post/{id}/avis` | Like post (POST) |
| `/forum/post/{id}/dislike` | Dislike post (POST) |
| `/forum/comment/{id}/avis` | Like comment (POST) |
| `/forum/comment/{id}/dislike` | Dislike comment (POST) |
| `/forum/chat` | AI chatbot API (POST) |

### Backend

| Route | Description |
|-------|-------------|
| `/admin` | Admin dashboard |
| `/admin/forum/post` | Posts list (edit, delete) |
| `/admin/forum/post/{id}` | Post detail |
| `/admin/forum/comment` | Comments list (filter by post, view, delete) |

## Templates

- **Frontend**: `templates/frontOffice/forum/` (index, show, post_new, comment_edit, _chatbot)
- **Backend**: `templates/backOffice/forum/` (post: index, show, edit; comment: index)
