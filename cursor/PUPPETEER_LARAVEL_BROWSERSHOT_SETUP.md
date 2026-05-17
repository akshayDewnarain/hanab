# Puppeteer Server Setup — Laravel + Browsershot (Ubuntu 24.04)

This document outlines the complete server setup required to run Puppeteer (Chrome headless) with Laravel + Spatie Browsershot, using a queue worker running as `www-data`.

This configuration uses:

- `puppeteer` (not `puppeteer-core`)
- Puppeteer-managed Chromium
- Laravel queue workers
- Ubuntu 24.04 (DigitalOcean droplet)

## Prerequisites

- Ubuntu 24.04 server
- Laravel project deployed
- Queue worker configured (`php artisan queue:work`)
- Node.js installed via NodeSource
- Queue worker runs as `www-data`

## Step-by-Step Checklist

### 0) Ensure Node.js is Installed (NodeSource 20.x)

```bash
sudo apt-get update
sudo apt-get install -y nodejs
```

Verify:

```bash
node -v
npm -v
```

### 1) Install Puppeteer in the API Project

From the API root directory:

```bash
npm install puppeteer
```

### 2) Create Dedicated Puppeteer Cache Directory

Puppeteer must install its browser as the same user that runs Laravel (`www-data`).

```bash
sudo mkdir -p /var/www/.cache/puppeteer
sudo chown -R www-data:www-data /var/www/.cache
```

### 3) Install Chromium (Managed by Puppeteer)

Install the browser as `www-data`, not root:

```bash
cd /var/www/your-project/api

sudo -u www-data env HOME=/var/www PUPPETEER_CACHE_DIR=/var/www/.cache/puppeteer \
  npx puppeteer browsers install chrome

sudo -u www-data env HOME=/var/www PUPPETEER_CACHE_DIR=/var/www/.cache/puppeteer \
  npx puppeteer browsers install chrome-headless-shell
```

Confirm installation:

```bash
sudo -u www-data ls -la /var/www/.cache/puppeteer
```

You should see:

- `chrome/`
- `chrome-headless-shell/`

### 4) Install Required System Dependencies (Ubuntu 24.04)

Chromium requires shared libraries that are not installed on minimal servers.

Install:

```bash
sudo apt-get install -y \
  libatk1.0-0 \
  libatk-bridge2.0-0 \
  libcups2 \
  libdrm2 \
  libxkbcommon0 \
  libxcomposite1 \
  libxdamage1 \
  libxfixes3 \
  libxrandr2 \
  libxshmfence1 \
  libgbm1 \
  libnss3 \
  libnspr4 \
  libasound2t64 \
  libpangocairo-1.0-0 \
  libpango-1.0-0 \
  libgtk-3-0 \
  libx11-6 \
  libx11-xcb1 \
  libxcb1 \
  libxcb-dri3-0 \
  libxcb-render0 \
  libxcb-shm0 \
  libxext6 \
  libxi6 \
  libxtst6 \
  ca-certificates \
  fonts-liberation
```

Without these, Chromium fails with missing `.so` errors.

### 5) Disable Crashpad (Required on Ubuntu Server)

On minimal Ubuntu installations, Chromium fails with:

`chrome_crashpad_handler: --database is required`

To resolve this, Chromium must be launched with the following arguments:

- `--disable-crashpad-for-testing`
- `--no-zygote`
- `--no-sandbox`
- `--disable-dev-shm-usage`
- `--no-first-run`
- `--no-default-browser-check`
- `--user-data-dir=/tmp/puppeteer-user-data`

These arguments are applied through the Browsershot configuration (already committed to the repository).

### 6) Ensure Queue Worker Uses Correct Environment

The queue worker must run:

- As `www-data`
- With correct environment variables

Required environment:

- `HOME=/var/www`
- `PUPPETEER_CACHE_DIR=/var/www/.cache/puppeteer`

Manual start example:

```bash
sudo -u www-data env HOME=/var/www PUPPETEER_CACHE_DIR=/var/www/.cache/puppeteer \
  php artisan queue:work
```

If using Supervisor or systemd, define these variables in the service configuration.

## Final State

After completing the steps above:

- Puppeteer launches correctly
- Chromium runs without missing library errors
- Crashpad no longer crashes the process
- Browsershot successfully generates PDFs
- Queue workers operate reliably under `www-data`

## Important Notes

- Do not run queue workers as root.
- Do not install the Puppeteer browser as root.
- Do not mix cache directories between users.
- This configuration is stable and production-ready.
