# Production: Nginx, Laravel Scheduler & Queue Workers

For production with **Nginx**, treat these as **two separate background processes**:

1. **Laravel scheduler** — triggers your scheduled commands (e.g. every 15 minutes).
2. **Queue worker** (via **Supervisor** or similar) — keeps `queue:work` alive.

**Nginx** only serves HTTP; it does **not** run Laravel scheduled tasks or queue workers. Laravel’s scheduler is meant to be triggered by a **single system cron** entry (`schedule:run` every minute). Queue workers are **long-lived** and should be managed by a **process monitor** (e.g. Supervisor).

---

## Application-side schedule (already in this repo)

`api/routes/console.php` registers:

```php
Schedule::command('boat-holds:dispatch-expired')
    ->everyFifteenMinutes()
    ->withoutOverlapping();
```

That is the **application-level** schedule. The server must still run `php artisan schedule:run` every minute so Laravel can fire this when due.

---

## Recommended production setup

### 1. Keep your scheduled command

The Laravel-side schedule above is correct. No change needed for “every 15 minutes” logic beyond ensuring cron runs the scheduler.

### 2. Add a server cron entry

On the server, add a cron job for the deploy user (adjust path and PHP as needed):

```cron
* * * * * cd /var/www/your-app/api && php artisan schedule:run >> /dev/null 2>&1
```

> **Note:** This repo’s Laravel app root is the `api/` directory (where `artisan` lives). Use that path in production, e.g. `cd /var/www/your-app/api`.

That runs the Laravel scheduler **every minute**; Laravel decides when the 15-minute task is due.

### 3. Run queue workers under a process manager

Do **not** rely on a terminal session running `php artisan queue:work`. Use **Supervisor** (or **systemd**) so workers restart if they crash and can be controlled after deploys. Workers do **not** automatically pick up new code until restarted.

Example worker command (adjust queues to match your app):

```bash
php artisan queue:work --queue=mail,default,boat-holds --sleep=3 --tries=3 --timeout=120
```

The `boat-holds` queue is used by `ExpireBoatHold` jobs dispatched from `boat-holds:dispatch-expired`.

### 4. Supervisor example

A minimal Supervisor program:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/your-app/api/artisan queue:work --queue=mail,default,boat-holds --sleep=3 --tries=3 --timeout=120
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/your-app/api/storage/logs/worker.log
stopwaitsecs=3600
```

Then reload Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

Laravel’s documentation recommends Supervisor for queue workers in production.

---

## Deploy flow

After each deploy, **gracefully restart** workers so they load new code:

```bash
php artisan queue:restart
```

Laravel stores a restart signal in **cache**; your production `CACHE_DRIVER` must be working for this to take effect.

---

## Architecture summary (typical Nginx + PHP-FPM)

| Piece            | Role                                      |
|-----------------|-------------------------------------------|
| **Nginx**       | Serves HTTP                               |
| **PHP-FPM**     | Runs web requests                         |
| **cron**        | Runs `schedule:run` every minute          |
| **Supervisor**  | Runs `queue:work` continuously            |
| **Deploy**      | Run `php artisan queue:restart`           |

---

## Quick checklist

- [ ] `QUEUE_CONNECTION` set correctly in production (e.g. `redis`, `database`).
- [ ] Cron installed: `* * * * * cd /path/to/api && php artisan schedule:run`.
- [ ] Supervisor (or systemd) managing `queue:work` (include `boat-holds` in `--queue` if you use that flow).
- [ ] Deploy script runs `php artisan queue:restart`.
- [ ] `boat-holds:dispatch-expired` remains scheduled every 15 minutes in `routes/console.php`.
- [ ] Logs: `storage/logs/laravel.log` and worker log file (e.g. `storage/logs/worker.log`).

---

## Related code in this repo

- Schedule: `api/routes/console.php`
- Command: `Application\Console\Commands\DispatchExpiredBoatHolds` (`boat-holds:dispatch-expired`)
- Job: `Domain\Bookings\Jobs\ExpireBoatHold` (queue: `boat-holds`)

---

## Tailoring to your server

Replace:

- `/var/www/your-app/api` — your real deploy path to the directory that contains `artisan`.
- `www-data` — your PHP / web user if different.
- `--queue=...` — list every queue your app uses in production.

If you use **Horizon** instead of raw `queue:work`, run and supervise Horizon instead; the same ideas apply (process manager + restart after deploy).
