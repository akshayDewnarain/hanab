# Supervisor setup — Laravel queue + scheduler (dev template)

## 1) Install Supervisor

```bash
sudo apt-get update
sudo apt-get install -y supervisor
sudo systemctl enable supervisor
sudo systemctl start supervisor
```

## 2) Create queue worker config

```bash
sudo nano /etc/supervisor/conf.d/dev-queue.conf
```

Add:

```ini
[program:dev-queue]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php artisan queue:work --queue=mail,boat-holds,default --sleep=3 --tries=3 --timeout=120
directory=/var/www/YOUR_PROJECT/api
user=www-data
numprocs=1
autostart=true
autorestart=true
stopwaitsecs=120
redirect_stderr=true
stdout_logfile=/var/log/supervisor/dev-queue.log
environment=HOME="/var/www",PUPPETEER_CACHE_DIR="/var/www/.cache/puppeteer"
```

Replace `/var/www/YOUR_PROJECT/api` with your actual project path.

## 3) Create scheduler config

```bash
sudo nano /etc/supervisor/conf.d/dev-schedule.conf
```

Add:

```ini
[program:dev-schedule]
command=/usr/bin/php artisan schedule:work
directory=/var/www/YOUR_PROJECT/api
user=www-data
numprocs=1
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/supervisor/dev-schedule.log
environment=HOME="/var/www",PUPPETEER_CACHE_DIR="/var/www/.cache/puppeteer"
```

Replace `/var/www/YOUR_PROJECT/api` with your actual project path.

## 4) Load Supervisor configs

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start dev-queue:*
sudo supervisorctl start dev-schedule
```

## 5) Check status

```bash
sudo supervisorctl status
```

Expected:

```text
dev-queue:dev-queue_00   RUNNING
dev-schedule             RUNNING
```

## 6) Useful commands

Restart after deploy:

```bash
php artisan queue:restart
sudo supervisorctl restart dev-schedule
```

Hard restart both:

```bash
sudo supervisorctl restart dev-queue:*
sudo supervisorctl restart dev-schedule
```

Logs:

```bash
sudo tail -f /var/log/supervisor/dev-queue.log
sudo tail -f /var/log/supervisor/dev-schedule.log
```

Failed jobs:

```bash
php artisan queue:failed
```

Retry failed jobs:

```bash
php artisan queue:retry all
```
