# Sheet Flow

Laravel + Vue SPA that reads Google Sheets with different structures, auto-provisions tabs/columns/tables, and upserts rows into MySQL on a schedule or on demand.

## Features

### Auto onboarding
- Paste a Google Sheets URL or spreadsheet id in the SPA.
- The app discovers tabs and header rows, infers column types, creates destination tables, and queues the first sync.
- Sample CSVs in [`samples/`](samples/) can be imported as sheet tabs for demos.

### Sources CRUD
- List sources with pagination (`15` / `25` / `50` per page).
- Edit name, active flag, and spreadsheet URL/id.
- Changing the spreadsheet id re-provisions tabs and mappings on the same source.
- Delete a source (cascades related config).

### Mapping & sync engine
- Active sources → tabs → table mappings → column mappings.
- Creates/alters destination tables safely (protected Laravel tables are blocked).
- Upserts by unique columns; repeated sync updates existing rows.
- New sheet headers discovered on sync are added to config and the DB schema.
- Advanced UI on source detail: add tabs, destination tables, and column renames manually.

### Import runs
- Manual sync from SPA or `php artisan sheets:sync`.
- Daily scheduled sync at `SHEETS_SYNC_TIME` (default `02:00`, app timezone).
- Run history with per-mapping stats (read / written / skipped / errors).
- Paginated runs list; poll refreshes the current page every 5s.

### Auth SPA
- Vue 3 app at `/app` (login, register, sources, source detail, runs).
- Session cookie API under `/api/*`.

---

## Requirements

| Tool | Version / notes |
| --- | --- |
| PHP | 8.3+ with `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath` |
| Composer | 2.x |
| Node.js | 22.x (LTS) |
| npm | 10+ |
| MySQL | 8.x |
| Docker + Docker Compose | optional, for Laravel Sail |
| Google Cloud project | Sheets API enabled + service account JSON |

---

## Quick start (Laravel Sail)

From the project root:

```bash
cp .env.example .env

# Generate app key (works even before Sail if PHP is available)
php artisan key:generate
# or after Sail is up:
./vendor/bin/sail artisan key:generate
```

Install PHP deps and start containers:

```bash
composer install
./vendor/bin/sail up -d
```

Configure `.env` for Sail MySQL (typical local values):

```env
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password

QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=database

GOOGLE_SERVICE_ACCOUNT_JSON=
SHEETS_SYNC_TIME=02:00
SHEETS_HTTP_TIMEOUT=30
```

If `GOOGLE_SERVICE_ACCOUNT_JSON` is empty, credentials default to:

`storage/app/google/service-account.json`

Migrate, build frontend, then open the app:

```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm install
./vendor/bin/sail npm run build

# optional hot reload
./vendor/bin/sail npm run dev
```

Run a queue worker (required for sync jobs):

```bash
./vendor/bin/sail artisan queue:work
```

Daily schedule (in a second terminal, or via cron/supervisor in production):

```bash
./vendor/bin/sail artisan schedule:work
```

App URLs:

- Welcome: `http://localhost/`
- SPA: `http://localhost/app/login` → register → `/app/sources`
- API: `http://localhost/api/...`

---

## Google Sheets API credentials (service account)

This project uses a **Google Cloud service account** (JSON key), not end-user OAuth tokens. The server reads spreadsheets shared with the service account email. No Google login popup is required in the UI.

### 1. Create / select a Google Cloud project

1. Open [Google Cloud Console](https://console.cloud.google.com/).
2. Create a project (or pick an existing one).
3. Note the project name for later.

### 2. Enable Google Sheets API

1. Go to **APIs & Services → Library**.
2. Search for **Google Sheets API**.
3. Click **Enable**.

Without this step, reads fail with API-disabled / access errors.

### 3. Create a service account

1. Go to **APIs & Services → Credentials**.
2. **Create credentials → Service account**.
3. Name it (e.g. `sheet-flow-reader`) and finish creation.
4. Open the service account → **Keys** → **Add key → Create new key → JSON**.
5. Download the JSON file. Keep it private; never commit it.

The JSON looks like:

```json
{
  "type": "service_account",
  "project_id": "...",
  "private_key_id": "...",
  "private_key": "-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n",
  "client_email": "sheet-flow@your-project.iam.gserviceaccount.com",
  "client_id": "...",
  "token_uri": "https://oauth2.googleapis.com/token"
}
```

The important field for sharing sheets is **`client_email`**.

### 4. Install the key in the project

**Option A — default path**

```bash
mkdir -p storage/app/google
cp /path/to/downloaded-key.json storage/app/google/service-account.json
chmod 600 storage/app/google/service-account.json
```

Leave `GOOGLE_SERVICE_ACCOUNT_JSON` empty in `.env`.

**Option B — custom path**

```env
GOOGLE_SERVICE_ACCOUNT_JSON=/absolute/path/to/service-account.json
```

`storage/app/google/` ignores everything except `.gitignore`, so keys stay out of git.

### 5. Share each Google Sheet with the service account

1. Open the spreadsheet in Google Sheets.
2. **Share** → paste the `client_email` from the JSON.
3. Role: **Viewer** is enough (the app uses read-only Sheets scope).
4. Uncheck “Notify people” if you prefer, then Share.

If the sheet is not shared, connect/sync returns a permission error (HTTP 422 in the SPA).

### 6. Prepare sheet structure

- Row 1 = headers (or set `header_row` later in advanced config).
- One logical dataset per tab (tab name becomes the default destination table slug).
- You can import files from [`samples/`](samples/) as tabs:

| File | Suggested tab name |
| --- | --- |
| `sales-sample.csv` | Sales |
| `inventory-sample.csv` | Inventory |
| `employees-sample.csv` | Employees |
| `orders-sample.csv` | Orders |
| `customers-sample.csv` | Customers |
| `events-sample.csv` | Events |

### 7. Smoke-test credentials

After the app is running and the key is in place:

1. Register at `/app/register`.
2. On **Sources**, paste the spreadsheet URL.
3. Click **Connect sheet** — tabs/mappings should appear and a sync run should be queued.
4. Ensure `queue:work` is running so the job finishes.
5. Check **Runs** and destination tables in MySQL.

---

## Local setup without Sail

```bash
cp .env.example .env
composer install
php artisan key:generate
```

Point `.env` at a local MySQL:

```env
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sheet_flow
DB_USERNAME=root
DB_PASSWORD=secret

QUEUE_CONNECTION=database
```

```bash
php artisan migrate
npm install
npm run build

php artisan serve
php artisan queue:work
php artisan schedule:work   # optional, for daily sync
```

For Vite HMR during UI work: `npm run dev` alongside `php artisan serve`.

---

## Ubuntu setup (Linux)

These steps target a fresh **Ubuntu 22.04 / 24.04** server or VM.

### 1. System packages

```bash
sudo apt update
sudo apt install -y \
  git curl unzip ca-certificates gnupg \
  mysql-server nginx \
  supervisor
```

### 2. PHP 8.3

```bash
sudo apt install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
sudo apt install -y \
  php8.3-cli php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml \
  php8.3-curl php8.3-zip php8.3-bcmath php8.3-intl php8.3-sqlite3
```

### 3. Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

### 4. Node.js 22

```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs
node -v
npm -v
```

### 5. MySQL database

```bash
sudo mysql -e "CREATE DATABASE sheet_flow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'sheet_flow'@'localhost' IDENTIFIED BY 'change-me-strong';"
sudo mysql -e "GRANT ALL PRIVILEGES ON sheet_flow.* TO 'sheet_flow'@'localhost'; FLUSH PRIVILEGES;"
```

### 6. Deploy the app

```bash
sudo mkdir -p /var/www
sudo chown "$USER":"$USER" /var/www
cd /var/www
git clone <YOUR_REPO_URL> sheet-flow
cd sheet-flow

cp .env.example .env
# edit .env: APP_URL, DB_*, QUEUE_CONNECTION=database, Google path
nano .env

composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force

npm ci
npm run build

mkdir -p storage/app/google
# copy service-account.json here (or set GOOGLE_SERVICE_ACCOUNT_JSON)
chmod -R ug+rwx storage bootstrap/cache
```

Example production `.env` fragments:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sheets.example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sheet_flow
DB_USERNAME=sheet_flow
DB_PASSWORD=change-me-strong

QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=database

GOOGLE_SERVICE_ACCOUNT_JSON=/var/www/sheet-flow/storage/app/google/service-account.json
SHEETS_SYNC_TIME=02:00
```

### 7. Nginx + PHP-FPM

`/etc/nginx/sites-available/sheet-flow`:

```nginx
server {
    listen 80;
    server_name sheets.example.com;
    root /var/www/sheet-flow/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/sheet-flow /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

Add TLS with Certbot when ready (`sudo apt install certbot python3-certbot-nginx`).

### 8. Supervisor: queue worker

`/etc/supervisor/conf.d/sheet-flow-worker.conf`:

```ini
[program:sheet-flow-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/sheet-flow/artisan queue:work --sleep=1 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/sheet-flow/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo chown -R www-data:www-data /var/www/sheet-flow/storage /var/www/sheet-flow/bootstrap/cache
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start sheet-flow-worker:*
```

### 9. Cron: Laravel scheduler

```bash
sudo crontab -u www-data -e
```

Add:

```cron
* * * * * cd /var/www/sheet-flow && php artisan schedule:run >> /dev/null 2>&1
```

This triggers `sheets:sync --scheduled` daily at `SHEETS_SYNC_TIME`.

### 10. Permissions reminder

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod 600 storage/app/google/service-account.json
```

---

## How the product works

```text
Google Sheet (shared with SA email)
        │
        ▼
 Discover / Provision  →  sheet_sources, sheet_tabs, table_mappings, column_mappings
        │
        ▼
 SyncSheetsJob (queue) → read rows → ensure schema → upsert destination tables
        │
        ▼
 import_runs + import_run_entries  (SPA Runs view)
```

### Connect a sheet
1. User submits URL/id → `ProvisionSheetSource` discovers structure.
2. Destination table names are slugged from tab names.
3. Column types are inferred from sample values (string, integer, decimal, boolean, date, datetime, text).
4. A pending import run is created and `SyncSheetsJob` is dispatched.

### Sync behaviour
- Only **active** sources / tabs / mappings are processed.
- Unique columns drive upserts (default: all discovered columns until refined in Advanced).
- Changing spreadsheet id on update re-runs provision on the **same** source record.
- Protected table names (`users`, `jobs`, mapping tables, etc.) cannot be used as destinations.

### SPA pages
| Path | Purpose |
| --- | --- |
| `/app/login`, `/app/register` | Auth |
| `/app/sources` | Connect sheet, edit/delete, paginate, run sync |
| `/app/sources/:id` | Overview, edit name/active, delete, advanced mapping |
| `/app/runs` | Import history + live poll |

### Useful Artisan commands

```bash
php artisan sheets:sync              # queue a manual run
php artisan sheets:sync --scheduled  # same, trigger=schedule
php artisan queue:work               # process jobs
php artisan schedule:work            # local scheduler loop
php artisan test                     # PHPUnit
```

### API (authenticated session)

| Method | Path | Notes |
| --- | --- | --- |
| `GET/POST` | `/api/sources` | List (paginated) / create+provision |
| `GET/PUT/DELETE` | `/api/sources/{id}` | Show / update / destroy |
| `POST` | `/api/sync` | Queue import run |
| `GET` | `/api/import-runs` | Paginated runs (`page`, `per_page`) |
| `GET` | `/api/import-runs/{id}` | Run detail with entries |

Nested resources for tabs, mappings, and columns are available for advanced configuration.

Pagination query: `?page=1&per_page=15` — allowed `per_page` values: `15`, `25`, `50` (others fall back to `15`).

---

## Environment variables (Sheets-related)

| Variable | Default | Meaning |
| --- | --- | --- |
| `GOOGLE_SERVICE_ACCOUNT_JSON` | empty → `storage/app/google/service-account.json` | Absolute path to SA JSON |
| `SHEETS_SYNC_TIME` | `02:00` | Daily schedule time (`H:i`, app timezone) |
| `SHEETS_HTTP_TIMEOUT` | `30` | Google HTTP timeout (seconds) |
| `QUEUE_CONNECTION` | `database` | Must not be `sync` in real use if you want async jobs |

---

## Troubleshooting

| Symptom | Likely cause | Fix |
| --- | --- | --- |
| Run stays `pending` | No queue worker | Start `php artisan queue:work` / Supervisor |
| Run `completed` but empty tables | Sheet has no readable tabs/headers | Ensure header row + data; share sheet |
| Permission / “caller does not have permission” | Sheet not shared with SA email | Share Viewer with `client_email` |
| Sheets API errors | API not enabled | Enable Google Sheets API in Cloud Console |
| Missing credentials file | JSON not installed | Place key or set `GOOGLE_SERVICE_ACCOUNT_JSON` |
| Frontend 404 on `/app/...` | Assets not built / wrong `APP_URL` | `npm run build`, check Nginx root = `public` |
| 422 on connect | Invalid URL / fetch failure | Check URL, sharing, API, and `storage/logs/laravel.log` |

---

## Development checklist

```bash
composer install
cp .env.example .env && php artisan key:generate
# configure DB + Google key
php artisan migrate
npm install && npm run build
php artisan serve          # or Sail
php artisan queue:work
php artisan test
```

Node **22+** is required for the current Vite toolchain.
