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

| Layer                  | Choice                                      |
| ---------------------- | ------------------------------------------- |
| Language               | PHP **8.4+**                                |
| Framework              | Symfony **7.4.\***                          |
| Runtime                | `symfony/runtime`                           |
| Dependency             | Composer (managed via Flex)                 |
| Autoloading            | PSR-4 — `App\` → `src/`                    |
| Transactional e-mail   | [Resend](https://resend.com) via `symfony/resend-mailer` |

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

## Mailer

Transactional e-mails (account confirmation, password reset) are sent via **[Resend](https://resend.com)** using the `symfony/resend-mailer` bridge.

### DSN format

```
MAILER_DSN=resend+api://<API_KEY>@default
```

### Environment setup

| Environment | Recommended value |
| ----------- | ----------------- |
| Local dev   | `resend+api://<key>@default` in `.env.dev.local` — or `null://null` to discard all mail |
| Test        | `null://null` (set in `.env.test` — e-mails are discarded, never sent) |
| Production  | Set `MAILER_DSN` as a Docker / CI secret — never commit the real key |

> The default `.env` contains `resend+api://API_KEY@default` as a template.
> Always override it with a real key in an uncommitted `.env.*.local` file or a runtime environment variable.

### Sender address

All e-mails are sent from `register@mesapplishf.fr` (configured in `src/Security/EmailVerifier.php`).
Make sure the domain `mesapplishf.fr` is **verified** in the [Resend dashboard](https://resend.com/domains) before going live.

### Currently sent e-mails

| Trigger | Template |
| ------- | -------- |
| User registration | `templates/registration/confirmation_email.html.twig` |

## Realtime (Mercure)

The friends chat pushes new messages over [Mercure](https://mercure.rocks) (SSE): the
message bubble, the conversation list and the navbar unread badge all update without a
reload.

Each user subscribes to a single private topic, `/friends/chat/user/<id>` — see
`src/Mercure/ChatTopics.php`. The authorization cookie is signed on every HTML page by
`src/EventSubscriber/MercureCookieSubscriber.php`, so the connection opened in
`templates/_chat_realtime.html.twig` works site-wide.

### Environment variables

| Variable | Meaning |
| -------- | ------- |
| `MERCURE_URL` | Where **the app** publishes. Inside Docker this is `http://mercure/.well-known/mercure` (the hub's service name on the stack's private network). |
| `MERCURE_PUBLIC_URL` | Where **the browser** subscribes. Must share a host with the site, otherwise the authorization cookie cannot be issued. |
| `MERCURE_JWT_SECRET` | Signs the publish/subscribe JWTs. The app and the hub must use the same value. |
| `MERCURE_CORS_ORIGIN` | Hub-side only. The exact origin the app is served from, scheme included, no trailing slash. `localhost` and `127.0.0.1` are **not** interchangeable. |

### Local development

**A hub is optional.** Without one the app runs normally — messages send, persist and
show up on reload; only the live push is missing. A failed publish is logged, not fatal
(`src/EventSubscriber/MercureCookieSubscriber.php`). The test suite never needs a hub
either: `config/services_test.yaml` mocks it.

The Docker stacks (`bin/deploy-docker.sh`) already run a `mercure` service. With
`symfony serve` you need a hub of your own — the defaults in `.env` expect it on port
3000, bound to loopback:

```bash
docker run -d --name mahf-mercure -p 127.0.0.1:3000:80 \
  -e SERVER_NAME=':80' \
  -e MERCURE_PUBLISHER_JWT_KEY='!ChangeThisMercureHubJWTSecretKey!' \
  -e MERCURE_SUBSCRIBER_JWT_KEY='!ChangeThisMercureHubJWTSecretKey!' \
  -e MERCURE_EXTRA_DIRECTIVES='cors_origins http://127.0.0.1:8000' \
  dunglas/mercure
```

Prefer no Docker at all? The project ships standalone binaries for every platform,
Windows included, on its [releases page](https://github.com/dunglas/mercure/releases).
They are Caddy builds driven by the `Caddyfile` in the archive, plus the same environment
variables as above.

Whichever you pick, browse the app on `http://127.0.0.1:8000` — **not**
`http://localhost:8000`, which is a different origin and a different cookie host, so the
hub would reject the subscription. If you have run `symfony server:ca:install`, the local
server serves **https**, and `cors_origins` has to match that scheme too.

> Running the full Docker stack instead? The app image has no bind mounts, so every code
> change needs `up -d --build`, and that stack's database and mailer are separate from
> your local ones (`SYMFONY_MAILER_DSN=null://null` discards all mail, so account
> verification e-mails never arrive).

### Reverse proxy

Each deployed stack publishes its hub on loopback only, so the host proxy has to forward
`/.well-known/mercure` to it:

| Stack | App port | Hub port |
| ----- | -------- | -------- |
| prod (`mesapplishf.fr`) | 8080 | 3080 |
| test (`test.mesapplishf.fr`) | 8081 | 3081 |
| dev (`dev.mesapplishf.fr`) | 8082 | 3082 |

SSE is a long-lived, unbuffered response — the defaults of both nginx and Caddy will
break it, so the flushing directives below are mandatory:

```nginx
# nginx — inside the server block, before the catch-all location
location /.well-known/mercure {
    proxy_pass http://127.0.0.1:3080;   # 3081 / 3082 for the other stacks

    proxy_http_version 1.1;
    proxy_set_header Host              $host;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_set_header X-Forwarded-For   $proxy_add_x_forwarded_for;
    proxy_set_header Connection        "";

    # Without these the events pile up in a buffer and the stream dies on timeout.
    proxy_buffering           off;
    proxy_cache               off;
    proxy_read_timeout        24h;
    chunked_transfer_encoding off;
}
```

```caddy
# Caddy — flush_interval -1 disables response buffering
handle /.well-known/mercure* {
    reverse_proxy 127.0.0.1:3080 {
        flush_interval -1
    }
}
```

### Tests

Functional tests never reach a hub: `config/services_test.yaml` swaps the default hub for
`MockHub`, backed by `tests/Mercure/CollectingPublisher.php`, which records the published
updates so they can be asserted on. Nothing needs to be running.

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
