# MesApplisHF

A Symfony 7.4 application powering the **HF apps portal**.

It hosts a collection of small personal apps:

- **MonPoids** — a weight & body-measurement tracker with a BMI calculator (private to each user).
- **MaCuisine** — a small social network for sharing recipes (with ingredients, ustensiles, categories).

Each sub-app exposes both a regular user area and an `/admin/...` section gated by `ROLE_ADMIN`.

📚 **[Documentation](https://eeckhoutremi.github.io/MesApplisHF)** — generated with phpDocumentor.

> Status: early development.

---

## Tech stack

| Layer       | Choice                              |
| ----------- | ----------------------------------- |
| Language    | PHP **8.4+**                        |
| Framework   | Symfony **7.4.\***                  |
| Database    | PostgreSQL via Doctrine ORM **3**   |
| Runtime     | FrankenPHP (`symfony/runtime`)      |
| Deployment  | Docker (multi-stage `Dockerfile`)   |
| Dependency  | Composer (managed via Flex)         |
| Autoloading | PSR-4 — `App\` → `src/`             |

## Requirements

- PHP **>= 8.4** with `ext-ctype`, `ext-iconv`, `intl` and `pdo_pgsql`
- **PostgreSQL** (the app uses `pdo_pgsql`)
- [Composer](https://getcomposer.org/) 2.x
- (Optional) the [Symfony CLI](https://symfony.com/download) for the local web server
- (Optional) Docker — the production image is built from the multi-stage `Dockerfile`

## Getting started

```bash
# 1. Clone
git clone <repo-url> MesApplisHF
cd MesApplisHF

# 2. Install dependencies
composer install

# 3. Configure environment
cp .env .env.local      # then set DATABASE_URL (PostgreSQL) in .env.local

# 4. Set up the database
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction

# 5. Run the dev server
symfony serve -d        # http://127.0.0.1:8000
# — or, without the Symfony CLI:
php -S 127.0.0.1:8000 -t public
```

## Project layout

```
.
├── assets/                  # JS/CSS, imported through symfony/asset-mapper
│   ├── controllers/         # Stimulus controllers
│   ├── MaCuisine/           # select-ingredient.js, select-utensil.js (Select2)
│   └── app.js
├── bin/console              # Symfony console entry point
├── config/                  # Bundles, routes, packages config
│   ├── packages/            # cache, framework, routing
│   └── routes/              # route definitions
├── public/                  # Web root — index.php front controller
├── src/
│   ├── Controller/          # HTTP controllers
│   │   ├── admin/           # admin sections (ROLE_ADMIN), incl. MaCuisine/ & MonPoids/
│   │   ├── MaCuisine/       # public MaCuisine pages (recipes, ajax)
│   │   ├── MonPoids/        # public MonPoids pages (BMI, measurements)
│   │   ├── LegalController  # /cgu, /confidentialite
│   │   └── …                # Security, Settings, Profil, Index
│   ├── Entity/              # Doctrine entities (see "Database schema" below)
│   │   ├── MaCuisine/       # Recipe, Ingredient, Utensil, Category, RefRecipeIngredient
│   │   └── MonPoids/        # Bmi, Measurement
│   ├── Form/                # Symfony form types (mirrors Entity/ subfolders)
│   ├── Handler/             # Domain-flavored services (e.g. RecipeFormHandler)
│   ├── Repository/          # Doctrine repositories
│   └── Kernel.php           # Micro-kernel
├── templates/
│   ├── MaCuisine/           # recipe feed, show, form
│   ├── MonPoids/            # BMI & measurements views
│   └── legal/               # CGU & politique de confidentialité
├── composer.json
└── symfony.lock
```

> Folder casing note: the sub-app folders use **PascalCase** (`MaCuisine`, `MonPoids`) to match the PSR-4 namespaces. URL slugs and route names stay lowercase (`/macuisine/feed`, `app_macuisine_feed`).

## Database schema

```mermaid
erDiagram
    USER ||--o{ RELATIONSHIP            : "user1 / user2"
    USER ||--o{ BMI                     : has
    USER ||--o{ MEASUREMENT             : has
    USER ||--o{ RECIPE                  : authors
    USER ||--o{ CATEGORY                : "created"
    USER ||--o{ UTENSIL                 : "created"
    CATEGORY ||--o{ RECIPE              : classifies
    RECIPE ||--o{ REF_RECIPE_INGREDIENT : contains
    INGREDIENT ||--o{ REF_RECIPE_INGREDIENT : "used in"
    RECIPE }o--o{ UTENSIL               : "uses"

    USER {
        int             id PK
        string(180)     email UK
        json            roles
        string          password
        string(30)      username
        bool            isVerified
        float           height "nullable"
        datetime_immut  createdAt
    }

    RELATIONSHIP {
        int             id PK
        int             user1_id FK
        int             user2_id FK
        string(10)      status
        datetime_immut  createdAt
        datetime_immut  updatedAt "nullable"
    }

    BMI {
        int             id PK
        int             user_id FK
        float           height
        float           weight
        float           bmi
        datetime_immut  createdAt
    }

    MEASUREMENT {
        int             id PK
        int             user_id FK
        float           chest
        float           hips
        float           thigh
        float           waist
        datetime_immut  createdAt
    }

    RECIPE {
        int             id PK
        int             author_id FK
        int             category_id FK "nullable"
        string(30)      name
        string(600)     description
        datetime_immut  createdAt
        datetime_immut  updatedAt "nullable"
    }

    INGREDIENT {
        int             id PK
        string(255)     name
    }

    REF_RECIPE_INGREDIENT {
        int             recipe_id PK,FK
        int             ingredient_id PK,FK
        float           quantity
        string(10)      unite
    }

    CATEGORY {
        int             id PK
        int             created_by_id FK
        string(32)      name
        datetime_immut  createdAt
        datetime_immut  updatedAt "nullable"
    }

    UTENSIL {
        int             id PK
        int             created_by_id FK
        string(32)      name
        datetime_immut  createdAt
        datetime_immut  updatedAt "nullable"
    }
```

> Table and column names follow Doctrine's default snake-case mapping. The `user` table is quoted (`` `user` ``) because it's a reserved keyword on most engines.

## Common commands

```bash
php bin/console list                # all available commands
php bin/console debug:router        # registered routes
php bin/console cache:clear         # clear the cache
php bin/console about               # environment summary
```

## Adding a controller

```bash
# maker-bundle is already installed as a dev dependency
php bin/console make:controller HelloController
```

## Documentation

API documentation is generated from the source PHPDoc with
[phpDocumentor](https://phpdoc.org/) and published to GitHub Pages:

**📚 <https://eeckhoutremi.github.io/MesApplisHF>**

It is rebuilt automatically on every push to `main` (the `docs` job in
`.github/workflows/deploy.yml`). To build it locally:

```bash
vendor/bin/phpdoc        # outputs to docs/.build (git-ignored)
```

## License

Proprietary — all rights reserved.
