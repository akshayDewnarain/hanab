# Supervisor Setup — Laravel Queue + Scheduler (Ubuntu)

Use Supervisor for both:

- `php artisan queue:work --queue=mail,boat-holds,default`
- `php artisan schedule:work`

## 1) Install Supervisor

```bash
sudo apt-get update
sudo apt-get install -y supervisor
sudo systemctl enable supervisor
sudo systemctl start supervisor
```

## 2) Create queue worker config

Create:

```bash
sudo nano /etc/supervisor/conf.d/bbq-queue.conf
```

Add:

```ini
[program:bbq-queue]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php artisan queue:work --queue=mail,boat-holds,default --sleep=3 --tries=3 --timeout=120
directory=/var/www/bbq-betuwe-reworked/api
user=www-data
numprocs=1
autostart=true
autorestart=true
stopwaitsecs=3600
redirect_stderr=true
stdout_logfile=/var/log/supervisor/bbq-queue.log
environment=HOME="/var/www",PUPPETEER_CACHE_DIR="/var/www/.cache/puppeteer"
```

## 3) Create schedule worker config

Create:

```bash
sudo nano /etc/supervisor/conf.d/bbq-schedule.conf
```

Add:

```ini
[program:bbq-schedule]
command=/usr/bin/php artisan schedule:work
directory=/var/www/bbq-betuwe-reworked/api
user=www-data
numprocs=1
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/supervisor/bbq-schedule.log
environment=HOME="/var/www",PUPPETEER_CACHE_DIR="/var/www/.cache/puppeteer"
```

## 4) Load Supervisor configs

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start bbq-queue:*
sudo supervisorctl start bbq-schedule
```

Check status:

```bash
sudo supervisorctl status
```

## 5) Useful commands

Restart after deploy:

```bash
sudo supervisorctl restart bbq-queue:*
sudo supervisorctl restart bbq-schedule
```

View logs:

```bash
sudo tail -f /var/log/supervisor/bbq-queue.log
sudo tail -f /var/log/supervisor/bbq-schedule.log
```

## Important deploy command

After each deploy, restart workers so they load new code:

```bash
php artisan queue:restart
sudo supervisorctl restart bbq-schedule
```

`queue:restart` gracefully restarts queue workers; `schedule:work` should be restarted manually because it is also a long-running process.
