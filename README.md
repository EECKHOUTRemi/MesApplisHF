# MesApplisHF

A Symfony 8 application skeleton powering the **HF apps portal**.

It hosts a collection of small personal apps. The first one shipped is **MonPoids** — a weight & body-measurement tracker with a BMI calculator.

> Status: early development — Doctrine entities and authentication model are in place; controllers are still being added.

---

## Tech stack

| Layer       | Choice                          |
| ----------- | ------------------------------- |
| Language    | PHP **8.4+**                    |
| Framework   | Symfony **8.0.\***              |
| Runtime     | `symfony/runtime`               |
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
├── src/
│   ├── Controller/    # HTTP controllers
│   ├── Entity/        # Doctrine entities (see "Database schema" below)
│   │   └── MonPoids/  # MonPoids app — Bmi, Measurement
│   ├── Repository/    # Doctrine repositories
│   └── Kernel.php     # Micro-kernel
├── composer.json
└── symfony.lock
```

## Database schema

```mermaid
erDiagram
    USER ||--o{ RELATIONSHIP    : "user1 / user2"
    USER ||--o{ MONPOIDS_BMI    : has
    USER ||--o{ MONPOIDS_MEASUREMENT : has

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

    MONPOIDS_BMI {
        int             id PK
        int             user_id FK
        float           height
        float           weight
        float           bmi
        datetime_immut  createdAt
    }

    MONPOIDS_MEASUREMENT {
        int             id PK
        int             user_id FK
        float           chest
        float           hips
        float           thigh
        float           waist
        datetime_immut  createdAt
    }
```

### Tables

#### `user` — [User.php](src/entity/User.php)

| Column        | Type                | Constraints                  |
| ------------- | ------------------- | ---------------------------- |
| `id`          | `INT`               | PK, auto-increment           |
| `email`       | `VARCHAR(180)`      | **unique** (`UNIQ_IDENTIFIER_EMAIL`) |
| `roles`       | `JSON`              | array of role strings        |
| `password`    | `VARCHAR`           | hashed                       |
| `username`    | `VARCHAR(30)`       |                              |
| `is_verified` | `BOOLEAN`           | default `false`              |
| `height`      | `DOUBLE`            | nullable (cm)                |
| `created_at`  | `DATETIME_IMMUTABLE`|                              |

Implements `UserInterface` + `PasswordAuthenticatedUserInterface`. Every user implicitly gets `ROLE_USER`.

#### `relationship` — [Relationship.php](src/entity/Relationship.php)

| Column       | Type                | Constraints                |
| ------------ | ------------------- | -------------------------- |
| `id`         | `INT`               | PK                         |
| `user1_id`   | `INT`               | FK → `user(id)`, NOT NULL  |
| `user2_id`   | `INT`               | FK → `user(id)`, NOT NULL  |
| `status`     | `VARCHAR(10)`       | e.g. `pending`, `accepted` |
| `created_at` | `DATETIME_IMMUTABLE`|                            |
| `updated_at` | `DATETIME_IMMUTABLE`| nullable                   |

Two `ManyToOne` references to `User` — models a directed link between two users.

#### `bmi` — [Bmi.php](src/entity/MonPoids/Bmi.php)

| Column       | Type                | Constraints               |
| ------------ | ------------------- | ------------------------- |
| `id`         | `INT`               | PK                        |
| `user_id`    | `INT`               | FK → `user(id)`, NOT NULL |
| `height`     | `DOUBLE`            | cm                        |
| `weight`     | `DOUBLE`            | kg                        |
| `bmi`        | `DOUBLE`            | computed: `kg / (m)²`     |
| `created_at` | `DATETIME_IMMUTABLE`|                           |

#### `measurement` — [Measurement.php](src/entity/MonPoids/Measurement.php)

| Column       | Type                | Constraints               |
| ------------ | ------------------- | ------------------------- |
| `id`         | `INT`               | PK                        |
| `user_id`    | `INT`               | FK → `user(id)`, NOT NULL |
| `chest`      | `DOUBLE`            | cm                        |
| `hips`       | `DOUBLE`            | cm                        |
| `thigh`      | `DOUBLE`            | cm                        |
| `waist`      | `DOUBLE`            | cm                        |
| `created_at` | `DATETIME_IMMUTABLE`|                           |

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
composer require symfony/maker-bundle --dev
php bin/console make:controller HelloController
```

## License

Proprietary — all rights reserved.
