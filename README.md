# MesApplisHF

A Symfony 8 application skeleton powering the **HF apps portal**.

It hosts a collection of small personal apps:

- **MaCuisine** — a recipe social network where users author recipes built from a shared ingredient catalogue.
- **MonPoids** — a weight & body-measurement tracker with a BMI calculator and Chart.js trend graphs.

Each app exposes a user-facing section plus an `/admin` section restricted to `ROLE_ADMIN`.

> Status: early development.

---

## Tech stack

| Layer       | Choice                          |
| ----------- | ------------------------------- |
| Language    | PHP **8.4+**                    |
| Framework   | Symfony **8.0.\***              |
| Runtime     | `symfony/runtime`               |
| ORM         | Doctrine ORM + Migrations       |
| Frontend    | Twig, Bootstrap 5, Stimulus     |
| Charts      | Symfony UX Chartjs (Chart.js)   |
| Dependency  | Composer (managed via Flex)     |
| Autoloading | PSR-4 — `App\` → `src/`         |

## Requirements

- PHP **>= 8.4** with `ext-ctype` and `ext-iconv`
- [Composer](https://getcomposer.org/) 2.x
- (Optional) the [Symfony CLI](https://symfony.com/download) for the local web server

## Getting started

```bash
# 1. Clone
git clone <repo-url> MesApplisHF
cd MesApplisHF

# 2. Install dependencies
composer install

# 3. Configure environment
cp .env .env.local      # then edit .env.local

# 4. Run the dev server
symfony serve -d        # http://127.0.0.1:8000
# — or, without the Symfony CLI:
php -S 127.0.0.1:8000 -t public
```

## Project layout

```
.
├── bin/console        # Symfony console entry point
├── config/            # Bundles, routes, packages config
│   ├── packages/      # cache, framework, routing
│   └── routes/        # route definitions
├── public/            # Web root — index.php front controller
├── migrations/        # Doctrine migrations
├── assets/
│   ├── controllers/   # Stimulus controllers (e.g. bmi_chart_controller.js)
│   └── styles/
├── src/
│   ├── Controller/
│   │   ├── admin/     # /admin section (ROLE_ADMIN)
│   │   ├── macuisine/ # MaCuisine — Recipe, Ingredient
│   │   └── monpoids/  # MonPoids — Bmi, Measurement
│   ├── Entity/        # Doctrine entities (see "Database schema" below)
│   │   ├── macuisine/ # Recipe, Ingredient, RefRecipeIngredient
│   │   └── monpoids/  # Bmi, Measurement
│   ├── Form/          # Symfony form types
│   ├── Repository/    # Doctrine repositories
│   └── Kernel.php     # Micro-kernel
├── composer.json
└── symfony.lock
```

## Database schema

```mermaid
erDiagram
    USER ||--o{ RELATIONSHIP    : "user1 / user2"
    USER ||--o{ BMI    : has
    USER ||--o{ MEASUREMENT : has
    USER ||--o{ RECIPE          : authors
    RECIPE ||--o{ REF_RECIPE_INGREDIENT : contains
    INGREDIENT ||--o{ REF_RECIPE_INGREDIENT : "used in"

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
        float           chest "nullable"
        float           hips "nullable"
        float           thigh "nullable"
        float           waist "nullable"
        datetime_immut  createdAt
    }

    RECIPE {
        int             id PK
        int             author_id FK
        string(15)      name
        string(600)     description
        datetime_immut  createdAt
        datetime_immut  updatedAt "nullable"
    }

    INGREDIENT {
        int             id PK
        string(255)     name
        string(255)     type "nullable"
    }

    REF_RECIPE_INGREDIENT {
        int             recipe_id PK,FK
        int             ingredient_id PK,FK
        float           quantity
        string(10)      unite
    }
```

> Table and column names follow Doctrine's default snake-case mapping. The `user` table is quoted (`` `user` ``) because it's a reserved keyword on most engines.

## Common commands

```bash
php bin/console list                       # all available commands
php bin/console debug:router               # registered routes
php bin/console cache:clear                # clear the cache
php bin/console about                      # environment summary
php bin/console make:migration             # scaffold a Doctrine migration
php bin/console doctrine:migrations:migrate # apply pending migrations
```

## Adding a controller

```bash
composer require symfony/maker-bundle --dev
php bin/console make:controller HelloController
```

## License

Proprietary — all rights reserved.
