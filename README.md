# PromptCMS

A prompt-driven CMS where you describe your website in natural language and AI builds it for you. Built with Laravel 13, Filament 5, and Tailwind CSS v4.

## Features

- **AI-Powered Content Creation** — Describe what you want in natural language, the AI creates pages, menus, headers, footers, and styles
- **Visual Admin Panel** — Chat interface similar to ChatGPT/Claude, built with Filament 5
- **Full Design Control** — The AI generates complete HTML with Tailwind CSS, including responsive layouts, animations, and modern design patterns
- **Media Library** — Upload images and let the AI use them in your content (auto-generates thumbnails in multiple sizes)
- **Version Control** — Every AI change creates a revision with full rollback capability
- **Plugin System** — Extensible via shortcodes from a curated plugin registry
- **SEO** — Automatic sitemap.xml, robots.txt, meta descriptions, canonical URLs
- **MCP Server** — Control the CMS from external AI tools via Model Context Protocol

## Requirements

- PHP 8.4+
- Node.js 20+
- Composer 2
- An OpenAI API key (or compatible provider)

## Quick Start (Local Development)

```bash
# Clone the repository
git clone https://github.com/your-org/promptcms.git
cd promptcms

# Install dependencies and set up the project
composer setup

# Set your API key
# Edit .env and set OPENAI_API_KEY=your-key-here

# Start development server
composer dev
```

This starts the PHP server, queue worker, log watcher, and Vite dev server concurrently.

**Default admin login:** `admin@example.com` / `password`

Open `http://localhost:8000/admin` to access the AI chat.

## Docker Deployment (Production)

PromptCMS ships with a production-ready Docker setup using **FrankenPHP** (PHP + Caddy in one binary) with automatic HTTPS.

### Quick Start with Docker

```bash
# 1. Clone the repo
git clone https://github.com/your-org/promptcms.git
cd promptcms

# 2. Create your .env
cp .env.example .env

# 3. Set required values in .env:
#    OPENAI_API_KEY=sk-your-key-here
#    SERVER_NAME=your-domain.com    (for automatic SSL)
#    APP_URL=https://your-domain.com

# 4. Build and start
docker compose up -d

# 5. Open https://your-domain.com/admin
#    Login: admin@example.com / password
```

### Docker with Custom Domain + SSL

Caddy (built into FrankenPHP) handles SSL certificates automatically via Let's Encrypt.

```bash
# .env
SERVER_NAME=cms.example.com
APP_URL=https://cms.example.com
APP_ENV=production
APP_DEBUG=false
OPENAI_API_KEY=sk-your-key-here

# Start
docker compose up -d
```

Make sure your DNS A record points to the server's IP. Caddy will obtain the certificate automatically on first request.

### Local Docker (without SSL)

For testing Docker locally:

```bash
docker compose -f docker-compose.dev.yml up -d --build
# Open https://localhost/admin (self-signed certificate)
```

### Docker Architecture

```
                    ┌──────────────────────────┐
                    │     FrankenPHP + Caddy    │
    :80/:443  ────> │  ┌─────────┐ ┌────────┐  │
                    │  │  Caddy   │ │  PHP   │  │
                    │  │ (reverse │ │ 8.4    │  │
                    │  │  proxy + │ │        │  │
                    │  │  SSL)    │ │        │  │
                    │  └─────────┘ └────────┘  │
                    └────────────┬─────────────┘
                                 │
              ┌──────────────────┼──────────────────┐
              │                  │                   │
        ┌─────┴──────┐  ┌──────┴───────┐  ┌───────┴───────┐
        │  SQLite DB  │  │   Storage    │  │   Plugins     │
        │  (volume)   │  │  (volume)    │  │   (volume)    │
        └────────────┘  └──────────────┘  └───────────────┘
```

### Environment Variables

| Variable | Default | Description |
|---|---|---|
| `SERVER_NAME` | `localhost` | Domain for Caddy (auto-SSL if real domain) |
| `APP_URL` | `https://localhost` | Full application URL |
| `OPENAI_API_KEY` | — | **Required.** Your OpenAI API key |
| `APP_ENV` | `production` | `local` or `production` |
| `APP_DEBUG` | `false` | Enable debug mode |
| `DB_CONNECTION` | `sqlite` | Database driver (sqlite, mysql, pgsql) |
| `APP_PORT` | `80` | Host port for HTTP |
| `APP_SSL_PORT` | `443` | Host port for HTTPS |

### Volumes

| Volume | Path | Purpose |
|---|---|---|
| `db-data` | `/app/database` | SQLite database file |
| `storage-data` | `/app/storage/app` | Uploaded media files |
| `plugins-data` | `/app/plugins` | Installed plugins |
| `caddy-data` | `/data` | SSL certificates |
| `caddy-config` | `/config` | Caddy configuration state |

### Updating

```bash
git pull
docker compose build
docker compose up -d
```

Migrations run automatically on container start.

## ZIP Installation (Shared Hosting / No Terminal Required)

The simplest method — no Docker, no Git, no terminal required.

### Prerequisites

- Web hosting with PHP 8.4+ and the extensions: `pdo_sqlite`, `mbstring`, `openssl`, `gd`, `fileinfo`
- Document root must point to the `public/` directory
- Frontend assets (`public/build/`) must be included in the ZIP (pre-built during release)

### Steps

1. **Download the ZIP** and extract it on the server
2. **Set the document root** of the web server to the `public/` directory
3. **Open the website in your browser** — the installer starts automatically
4. **Fill out the form**: name, email, password, website name
5. **Click "Install"** — done!

The installer automatically handles:
- Creating the `.env` file
- Generating the `APP_KEY`
- Setting up the directory structure (`storage/`, `database/`, etc.)
- Creating the SQLite database
- Running migrations
- Creating the storage symlink (`public/storage` → `storage/app/public`)
- Creating the admin user and initial CMS data

### Apache (.htaccess)

Works out of the box — the `.htaccess` in the `public/` directory is already configured.

### Nginx

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/promptcms/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known) {
        deny all;
    }
}
```

### Note: Storage Symlink

The installer automatically creates a symlink `public/storage` → `storage/app/public` so that uploaded media is accessible via the browser. If your hosting does not support symlinks, manually copy the contents of `storage/app/public/` to `public/storage/`.

## Developer Installation (with Terminal)

```bash
# Prerequisites: PHP 8.4+, Composer, Node.js 20+

# 1. Clone and install
git clone https://github.com/your-org/promptcms.git
cd promptcms
composer install
npm install

# 2. Build frontend
npm run build

# 3. Start development server
composer dev
# Opens http://localhost:8000 — the installer runs automatically

# Or manual setup:
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan serve
```

## Configuration

### API Key

Set your OpenAI API key either via:
- `.env` file: `OPENAI_API_KEY=sk-...`
- Admin panel: **Settings** (System Settings page)

### Changing the AI Model

In `.env` or via the admin settings page, you can change the model used for content generation.

### Database

SQLite is the default and works great for small to medium sites. For larger deployments, switch to MySQL or PostgreSQL by updating `DB_CONNECTION` and related variables in `.env`.

## Project Structure

```
app/
  Ai/
    Agents/CmsAgent.php         # Main AI agent with instructions and tools
    Tools/                      # AI tool definitions (CreatePage, UpdatePage, etc.)
  Filament/Pages/               # Admin panel pages (AiChat, Settings, etc.)
  Http/Controllers/             # Frontend page rendering
  Models/                       # Node, NodeMeta, Setting, AiSession, etc.
  Services/
    CmsToolService.php          # Database operations for all CMS tools
    CssBuildService.php         # On-demand Tailwind CSS compilation
    ContextPruningService.php   # Conversation context management
    PluginService.php           # Plugin installation and management
    ShortcodeRenderer.php       # Plugin shortcode processing
plugins/                        # Installed plugins directory
resources/views/
  layouts/                      # Frontend layouts
  templates/                    # Page templates
  filament/pages/               # Admin panel views
```

## License

MIT
