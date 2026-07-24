# Veyra ERP

Multi-tenant ERP platform with an integrated **Landing Page CMS** for managing the public marketing site from the Super Admin console.

---

## Overview

Veyra combines core ERP capabilities with a modular CMS that powers the marketing landing page. Admins manage section chrome (badges, titles, CTAs), repeatable content cards, and media through a dedicated admin UI — without editing Blade templates by hand.

---

## Features

- **Landing Page CMS** — eight content areas under one admin dropdown:
  - Settings (Hero, Privacy, Terms, Footer social links)
  - Problems, Solutions, Offerings, Modules, AI Features, Features, Testimonials
- **Key/value settings** — privacy policy, terms of service, and up to five footer social media buttons
- **Polymorphic images** — central `images` table with `HasImages` (`icon`, `avatar`, `logo` collections)
- **Flash notifications** — SweetAlert-style success / info / warning / error feedback
- **Role-based admin** — Spatie permissions for the Super Admin console
- **Livewire-ready UI** — Alpine.js for lightweight client interactivity

---

## Tech Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.3+ |
| Framework | Laravel 13 |
| Database | MySQL |
| Frontend | Tailwind CSS 4, Vite 8, Alpine.js |
| Real-time UI | Livewire 3 |
| Auth / roles | Spatie Laravel Permission |
| Testing | Pest 4 / PHPUnit |

---

## Requirements

- PHP 8.3+ with common Laravel extensions
- Composer 2
- Node.js 20+ and npm
- MySQL 8+

---

## Installation

```bash
# Clone the repository
git clone https://github.com/abdallahubeid/veyra-erp.git
cd veyra-erp

# Install PHP dependencies
composer install

# Environment
cp .env.example .env
php artisan key:generate

# Configure database credentials in .env
# DB_DATABASE=veyra
# DB_USERNAME=root
# DB_PASSWORD=

# Migrate (and optionally seed)
php artisan migrate
# php artisan db:seed

# Frontend assets
npm install
npm run build
# or during development: npm run dev

# Serve the app
php artisan serve
```

Open the app at the URL shown by `php artisan serve` (typically `http://127.0.0.1:8000`).

---

## Testing

The suite covers landing CMS resources, settings, polymorphic images, and related admin flows.

```bash
php artisan test
```

**116 tests** are expected to pass.

Filter a single file or name when iterating:

```bash
php artisan test --compact --filter=LandingSettings
```

---

## Landing CMS (quick map)

| Concern | Location |
|---|---|
| Admin routes | `routes/web.php` (`admin.landing.*`, resource routes) |
| Controllers | `app/Http/Controllers/Admin/` |
| Settings UI | `resources/views/admin/landing/settings/` |
| Section CRUD views | `resources/views/admin/landing/{entity}/` |
| Settings model | `app/Models/Setting` (`landingKeys()`) |
| Images | `app/Models/Image` + `HasImages` trait |
| Implementation notes | `docs/LANDING_CMS_IMPLEMENTATION.md` |

Settings admin route: `GET/PUT /admin/landing/settings` → `admin.landing.settings.edit` / `admin.landing.settings.update`.

---

## Project structure (high level)

```
app/
  Http/Controllers/Admin/   # Landing CMS & platform admin
  Models/                   # Setting, Image, Problem, …
  Models/Concerns/          # HasImages
docs/                       # Architecture & CMS notes
resources/views/admin/      # Admin Blade UI
tests/                      # Pest feature & unit tests
```

---

## License

Proprietary — all rights reserved unless otherwise stated by the project owners.
