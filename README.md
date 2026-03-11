# BrainBase-Backend 🚀

██████╗ ██████╗  █████╗ ██╗███╗   ██╗██████╗  █████╗ ███████╗███████╗
██╔══██╗██╔══██╗██╔══██╗██║████╗  ██║██╔══██╗██╔══██╗██╔════╝██╔════╝
██████╔╝██████╔╝███████║██║██╔██╗ ██║██████╔╝███████║███████╗█████╗  
██╔══██╗██╔══██╗██╔══██║██║██║╚██╗██║██╔══██╗██╔══██║╚════██║██╔══╝  
██████╔╝██║  ██║██║  ██║██║██║ ╚████║██████╔╝██║  ██║███████║███████╗
╚═════╝ ╚═╝  ╚═╝╚═╝  ╚═╝╚═╝╚═╝  ╚═══╝╚═════╝ ╚═╝  ╚═╝╚══════╝╚══════╝





Knowledge Management System — Backend (Laravel)

Tagline: Capture. Organize. Evolve knowledge. ✨

---

[![Build Status](https://github.com/OuafikMohammed/BrainBase-Backend/actions/workflows/ci.yml/badge.svg)](https://github.com/OuafikMohammed/BrainBase-Backend/actions)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-8892BF.svg)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-10.x-red.svg)](https://laravel.com/)
[![License](https://img.shields.io/github/license/OuafikMohammed/BrainBase-Backend.svg)](https://github.com/OuafikMohammed/BrainBase-Backend/blob/main/LICENSE)
[![GitHub stars](https://img.shields.io/github/stars/OuafikMohammed/BrainBase-Backend?style=social)](https://github.com/OuafikMohammed/BrainBase-Backend/stargazers)
[![Postman Collection](https://img.shields.io/badge/Postman-Collection-orange)](https://github.com/OuafikMohammed/BrainBase-Backend/blob/main/postman_collection.json)

---

Table of Contents
- [Overview](#overview)
- [Key Features](#key-features)
- [Tech Stack](#tech-stack)
- [API Documentation](#api-documentation)
- [Demo API Call (curl)](#demo-api-call-curl)
- [Installation](#installation-guide)
- [Configuration](#configuration)
- [Running the Server](#running-the-server)
- [Testing](#testing)
- [Database Schema (Simplified)](#database-schema-simplified)
- [Folder Structure](#folder-structure)
- [Contributing](#contributing-guidelines)
- [License](#license)
- [Connect / Contact](#connectcontact)
- [Support](#support)
- [Built With](#built-with)
- [Architecture Diagram](#architecture-diagram)

---

## Overview

BrainBase-Backend is the RESTful backend for BrainBase — a lightweight, versioned Knowledge Management System built with Laravel 10.x and PHP 8.1+. It provides secure JWT-authenticated APIs to manage users, knowledge articles, categories, and article versions so teams can capture and evolve organizational knowledge.

---

## Key Features ✨

- 🔐 JWT Authentication (register, login, refresh)
- 📝 CRUD for knowledge articles with version control
- 🔎 Full-text search & filters (title, content, category, tags)
- 🗂️ Categorization & tagging
- 🧾 Article versioning & history
- 📄 RESTful API (JSON)
- 🧪 Unit & feature tests
- ♻️ Soft deletes & audit-friendly design

---

## Tech Stack 🛠️

| Layer | Technology |
|---|---|
| Backend | PHP 8.1+, Laravel 10.x |
| Auth | JWT (laravel/jwt-auth or tymon/jwt-auth) |
| Database | MySQL 8.0+ |
| API | REST (JSON) |
| Testing | PHPUnit, Laravel Test Utilities |
| Other | Composer, GitHub Actions |

Built with best practices in mind: API-first, stateless auth, and clean separation of concerns.

---

## API Documentation

Base URL: `https://your-domain.com/api` (or local `http://localhost:8000/api`)

Authentication: Bearer Token (JWT) — attach `Authorization: Bearer {token}` to protected routes.

Endpoints summary:

| Method | Endpoint | Auth | Description |
|---:|---|:---:|---|
| POST | /auth/register | No | Register new user |
| POST | /auth/login | No | Login and receive JWT |
| POST | /auth/refresh | Yes | Refresh token |
| GET | /articles | No* | Get list of articles (public or filtered) |
| POST | /articles | Yes | Create an article |
| GET | /articles/{id} | No* | Get article (latest version) |
| PUT | /articles/{id} | Yes | Update article (creates new version) |
| DELETE | /articles/{id} | Yes | Soft-delete article |
| GET | /articles/{id}/versions | Yes | List article versions |
| GET | /categories | No | List categories |
| POST | /categories | Yes | Create category |
| GET | /search?q=term | No | Search articles (title/content) |

*Public access to reading may be configured — by default read endpoints are public.

Detailed example: Create article

Request:
POST /api/articles
Headers:
- Authorization: Bearer {token}
- Content-Type: application/json

Body:
```json
{
  "title": "How to run BrainBase locally",
  "content": "Step-by-step instructions...",
  "category_id": 2,
  "tags": ["setup", "local"]
}
```

Success response (201):
```json
{
  "id": 42,
  "title": "How to run BrainBase locally",
  "excerpt": "Step-by-step instructions...",
  "author_id": 1,
  "category": {
    "id": 2,
    "name": "Guides"
  },
  "tags": ["setup", "local"],
  "created_at": "2026-03-10T12:01:02Z",
  "version": 1
}
```

---

## Demo API Call (curl) 🧪

Register:
```bash
curl -X POST "http://localhost:8000/api/auth/register" \
  -H "Content-Type: application/json" \
  -d '{"name":"Alice","email":"alice@example.com","password":"secret","password_confirmation":"secret"}'
```

Login:
```bash
curl -X POST "http://localhost:8000/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"alice@example.com","password":"secret"}'
```

Get articles (public):
```bash
curl "http://localhost:8000/api/articles"
```

Create article (requires token):
```bash
curl -X POST "http://localhost:8000/api/articles" \
  -H "Authorization: Bearer <JWT_TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{"title":"Quick Tips","content":"Helpful tips...","category_id":1}'
```

---

## Postman Collection

You can import the Postman collection (if present) here:
- Postman collection: https://github.com/OuafikMohammed/BrainBase-Backend/blob/main/postman_collection.json
- (Badge above links to the file if you add the collection to the repo.)

---

## Installation Guide (Local) ⚙️

Quick start — run these commands in your terminal:

```bash
# Clone
git clone https://github.com/OuafikMohammed/BrainBase-Backend.git
cd BrainBase-Backend

# Install PHP dependencies
composer install

# Copy env and generate app key
cp .env.example .env
php artisan key:generate

# Configure .env (see Configuration section)

# Run migrations & seeders
php artisan migrate --seed

# Run dev server
php artisan serve --host=127.0.0.1 --port=8000
```

Quick start command:
```bash
composer install && cp .env.example .env && php artisan key:generate && php artisan migrate --seed && php artisan serve
```

---

## Configuration (.env) 🔧

Important environment variables (example names):

- APP_NAME=BrainBase
- APP_ENV=local
- APP_KEY=base64:...
- APP_URL=http://localhost

- DB_CONNECTION=mysql
- DB_HOST=127.0.0.1
- DB_PORT=3306
- DB_DATABASE=brainbase
- DB_USERNAME=root
- DB_PASSWORD=

- BROADCAST_DRIVER=log
- CACHE_DRIVER=file
- QUEUE_CONNECTION=sync
- SESSION_DRIVER=file

- JWT_SECRET=your_jwt_secret_here

- MAIL_MAILER=smtp (configure for notifications)

Tip: store JWT_SECRET safely and rotate when needed. Use Laravel's config caching in production: `php artisan config:cache`.

---

## Running the Server 🏃

Development server:
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Queue worker (if using queues):
```bash
php artisan queue:work
```

Scheduler (recommended for cron jobs):
Add to crontab:
```cron
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## Testing ✅

Run the test suite:

```bash
# Run Laravel/PHPUnit tests
php artisan test

# or using vendor binary
vendor/bin/phpunit
```

Write tests under `tests/Feature` and `tests/Unit`. Aim for deterministic tests and avoid hitting external services in CI.

---

## Database Schema (Simplified)

Tables and important columns:

- users
  - id, name, email (unique), password, role, created_at, updated_at

- categories
  - id, name, slug, created_at, updated_at

- articles
  - id, author_id (FK users), current_version_id, title, excerpt, visibility, created_at, updated_at, deleted_at (soft deletes)

- article_versions
  - id, article_id, version_number, content, changelog, created_by, created_at

- article_category (pivot)
  - article_id, category_id

- tags & article_tag (optional)
  - tags: id, name, slug
  - article_tag: article_id, tag_id

Indexes:
- Fulltext index on articles.title, article_versions.content for search
- FK indexes on author_id, article_id, category_id

---

## Folder Structure 📁

```text
app/
  Http/
    Controllers/
      AuthController.php
      ArticleController.php
      CategoryController.php
  Models/
    Article.php
    ArticleVersion.php
    Category.php
    User.php
  Policies/
  Requests/
config/
database/
  migrations/
  seeders/
resources/
  lang/
routes/
  api.php
tests/
  Feature/
  Unit/
.postman_collection.json
README.md
```

---

## Architecture Diagram (Mermaid) 🧭

```mermaid
flowchart LR
  Client["Client (Web/Mobile)"] -->|HTTP JSON| API[API Gateway / Laravel Controllers]
  API --> AuthService[JWT Auth (Middleware)]
  API --> ArticlesSvc[Article Service / Repositories]
  ArticlesSvc --> MySQL[(MySQL)]
  API --> Storage[(File / S3)]
  Workers[Queue Workers] --> ArticlesSvc
  AuthService --> MySQL
  note right of MySQL: Stores users, articles, versions,\ncategories, tags
```

---

## Contributing Guidelines 🤝

We're excited to have contributors! Please:

1. Fork the repo and create a branch: `feature/your-feature-name`
2. Write tests for new features/bugfixes.
3. Follow PSR-12 coding style.
4. Run `composer install` and `php artisan test` before submitting.
5. Open a PR with a clear description and link to related issues.

Code of conduct: Be respectful and constructive. We follow the standard community guidelines.

---


## Connect / Contact

Maintainer: OuafikMohammed  
GitHub: https://github.com/OuafikMohammed

Have questions or need help? Open an issue in this repo.

---

## Support ⭐

If BrainBase-Backend helps your team, please give this repo a star — it really helps with visibility and future contributions!

[Click to Star ⭐](https://github.com/OuafikMohammed/BrainBase-Backend/stargazers)

---

## Built With 🏗️

[![PHP](https://img.shields.io/badge/PHP-8.1%2B-8892BF.svg)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-10.x-red.svg)](https://laravel.com/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-blue.svg)](https://www.mysql.com/)
[![JWT](https://img.shields.io/badge/JWT-Auth-yellow.svg)](https://jwt.io/)

---

Thank you for checking out BrainBase-Backend! Contributions, feedback, and stars are welcome — let's build something that helps teams capture and evolve knowledge. 💡
