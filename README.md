<h1 align="center">Gym Weight Tracker · API</h1>

<p align="center">
  The Laravel backend of <a href="https://github.com/rmsddd/gym-weight-tracker-frontend">Gym Weight Tracker</a>,
  a workout log built for logging sets on a phone between reps.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white" alt="Laravel 12">
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/MySQL-4479A1?logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/Docker-Render-2496ED?logo=docker&logoColor=white" alt="Docker on Render">
</p>

<p align="center">
  <img src="https://raw.githubusercontent.com/rmsddd/gym-weight-tracker-frontend/main/docs/screenshots/dashboard.png" width="220" alt="Dashboard">
  &nbsp;
  <img src="https://raw.githubusercontent.com/rmsddd/gym-weight-tracker-frontend/main/docs/screenshots/active-workout.png" width="220" alt="Workout in progress">
  &nbsp;
  <img src="https://raw.githubusercontent.com/rmsddd/gym-weight-tracker-frontend/main/docs/screenshots/history.png" width="220" alt="History">
</p>

<p align="center"><sub>More screenshots and the feature list are in the <a href="https://github.com/rmsddd/gym-weight-tracker-frontend">frontend README</a>.</sub></p>

## What's in here

- **Auth** with Laravel Sanctum: session login for the SPA, access and refresh tokens,
  rate-limited auth routes.
- **Email confirmation**: new accounts must open a signed link before using the app. At sign-up
  the email domain must have an MX record, which catches typos like `gmial.com`.
- **Password reset** by email. Mail goes through Brevo's HTTP API in production.
- **Workouts → exercises → sets**, with a timer (start, stop, resume, finish), templates and
  the sets from the previous time each exercise was done.
- **Stats**: personal records, workouts per month, weekly muscle groups, profile totals.
- **Exercise catalog** seeded on deploy, re-seeded only when its seeders change.
- **Roles and permissions** with [spatie/laravel-permission](https://github.com/spatie/laravel-permission):
  every action is guarded by a permission, every account starts as `sportiv` (all of them ticked),
  and the `root` account manages roles from an admin page. The permissions are listed in
  `app/Support/PermissionCatalog.php`.

## API overview

All routes are under `/api`. Apart from the public ones, everything needs a logged-in user with
a confirmed email.

| Area | Routes |
| --- | --- |
| Auth (public) | `POST register`, `login`, `refresh`, `forgot-password`, `reset-password` |
| Account | `GET user`, `POST logout`, `PATCH user/avatar`, `POST email/verification-notification` |
| Catalog | `categories`, `exercises` (CRUD) |
| Workouts | `workouts` (CRUD), `POST workouts/{id}/finish`, `resume`, `complete`, `template` |
| Exercises & sets | `workouts/{id}/exercises`, `workouts/{id}/exercises/{id}/sets` (CRUD) |
| Last time | `GET workouts/{id}/previous-sets` |
| Templates | `workout-templates` (index, store, show, destroy) |
| Stats | `GET personal-records`, `monthly-stats`, `muscle-stats`, `profile-stats` |
| Admin (root only) | `GET admin/permissions`, `admin/roles` (index, store, update, destroy), `PUT admin/roles/{id}/permissions`, `GET admin/users`, `PUT admin/users/{id}/role` |

`php artisan route:list --path=api` prints the full list.

## Running it locally

You need PHP 8.2+, Composer and MySQL.

```sh
composer install
cp .env.example .env
php artisan key:generate
# set DB_DATABASE, DB_USERNAME and DB_PASSWORD in .env
php artisan migrate
php artisan catalog:seed
php artisan serve
```

The migrations also create the permissions and the `root` and `sportiv` roles
(`php artisan permissions:sync` does it again after the catalog changes). To make your own account
root, from `php artisan tinker`:

```php
App\Models\User::where('email', 'you@example.com')->first()->syncRoles('root');
```

The API runs on `http://localhost:8000`. Start the
[frontend](https://github.com/rmsddd/gym-weight-tracker-frontend) with `npm run dev`; it expects
the API on that address. `FRONTEND_URL` in `.env` (default `http://localhost:5173`) is where the
links in emails point.

Locally `MAIL_MAILER=log` writes emails to `storage/logs/laravel.log`, so you can open the
confirmation and reset links from there.

## Tests

```sh
php artisan test
```

## Deployment

The `Dockerfile` builds a single image for [Render](https://render.com):

1. Clones the frontend repo and runs `npm run build`.
2. Installs Laravel on PHP 8.3 + Apache and copies the Vue build into `public/`.
3. On start, `docker/start.sh` runs the migrations, syncs the permissions, seeds the catalog if
   needed and caches config and routes.

Push the frontend first and the backend second: the backend build picks up whatever is on the
frontend's `main` at that moment.

Production settings: `MAIL_MAILER=brevo`, `BREVO_API_KEY`, a `MAIL_FROM_ADDRESS` confirmed in
Brevo, and the database credentials. `/up` runs a real database query, so an uptime pinger
keeps the database awake.
