#!/usr/bin/env bash
#
# Idempotent Ubuntu setup helper for the Google Sheets importer.
#
# Verifies the required tooling, prepares the Laravel/Sail environment, starts
# the Docker services, runs migrations and seed data, builds the frontend,
# configures the scheduler, and prints the remaining Google credentials steps.
#
# Usage: ./setup.sh [--yes]
#   --yes, -y   never prompt; skip anything that would need confirmation

set -euo pipefail

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$REPO_DIR"

ASSUME_YES=0
for arg in "$@"; do
    case "$arg" in
        -y|--yes) ASSUME_YES=1 ;;
        -h|--help)
            sed -n '2,12p' "$0" | sed 's/^# \{0,1\}//'
            exit 0
            ;;
        *) printf 'Unknown option: %s\n' "$arg" >&2; exit 2 ;;
    esac
done

info() { printf '\n==> %s\n' "$*"; }
step() { printf '  - %s\n' "$*"; }
warn() { printf '  ! %s\n' "$*" >&2; }
fail() { printf '\nERROR: %s\n' "$*" >&2; exit 1; }

# confirm "question"  -> 0 when we should act.
# Non-interactive runs fail safe (no mutation, print instructions instead);
# --yes proceeds without prompting.
confirm() {
    if [ "$ASSUME_YES" -eq 1 ]; then
        return 0
    fi
    if [ ! -t 0 ]; then
        return 1
    fi
    read -r -p "$1 [y/N] " reply
    case "$reply" in
        y|Y|yes|YES) return 0 ;;
        *) return 1 ;;
    esac
}

command_exists() { command -v "$1" >/dev/null 2>&1; }

# ---------------------------------------------------------------------------
# 1. Docker and Docker Compose
# ---------------------------------------------------------------------------
info "Checking Docker and Docker Compose"

if ! command_exists docker; then
    warn "Docker is not installed."
    if confirm "Install Docker now using get.docker.com?"; then
        curl -fsSL https://get.docker.com | sudo sh || fail "Docker installation failed."
        sudo usermod -aG docker "${USER:-$(id -un)}" || true
        warn "Log out and back in (or run: newgrp docker) so the group change applies, then re-run this script."
        fail "Docker group membership needs a new login session."
    else
        fail "Docker is required. Install it first: https://docs.docker.com/engine/install/ubuntu/"
    fi
fi

command_exists docker || fail "docker is not on PATH."

if ! docker compose version >/dev/null 2>&1; then
    warn "Docker Compose v2 plugin is missing."
    fail "Install it with: sudo apt-get install -y docker-compose-plugin"
fi
step "Docker and Docker Compose are available."

if ! docker info >/dev/null 2>&1; then
    warn "The Docker daemon is not reachable for this user."
    fail "Start it with 'sudo systemctl start docker' and ensure your user is in the 'docker' group."
fi

# ---------------------------------------------------------------------------
# 2. Bootstrap the project where necessary
# ---------------------------------------------------------------------------
info "Preparing the Laravel project"

if [ ! -f composer.json ]; then
    fail "No composer.json found; run this script from the project root."
fi

if [ ! -f vendor/bin/sail ]; then
    if ! command_exists composer; then
        fail "Composer is missing and vendor/bin/sail is not installed. Install Composer first: https://getcomposer.org/download/"
    fi
    step "Installing Composer dependencies"
    composer install
    step "Installing Laravel Sail"
    composer require laravel/sail --dev
    [ -f vendor/bin/sail ] || fail "vendor/bin/sail was not created."
else
    step "Laravel Sail is already installed."
fi

# ---------------------------------------------------------------------------
# 3. Environment file
# ---------------------------------------------------------------------------
info "Configuring the environment file"

if [ ! -f .env ]; then
    cp .env.example .env
    step "Created .env from .env.example."
else
    step ".env already exists; leaving it untouched."
    if ! grep -q '^GOOGLE_SHEETS_CREDENTIALS=' .env; then
        if confirm "Add the missing GOOGLE_SHEETS_* keys to .env?"; then
            printf '\nGOOGLE_SHEETS_CREDENTIALS=\nGOOGLE_SHEETS_TIMEOUT=30\nGOOGLE_SHEETS_RETRIES=3\n' >> .env
            step "Appended GOOGLE_SHEETS_* keys."
        fi
    fi
fi

if grep -q '^APP_KEY=.\+' .env; then
    step "Application key is already set."
else
    ./vendor/bin/sail artisan key:generate
    step "Generated the application key."
fi

# ---------------------------------------------------------------------------
# 4. Frontend assets
# ---------------------------------------------------------------------------
info "Installing and building frontend assets"

if command_exists npm; then
    if [ ! -d node_modules ]; then
        step "npm install"
        npm install
    else
        step "node_modules already present."
    fi
    if [ ! -f public/build/manifest.json ]; then
        step "npm run build"
        npm run build
    else
        step "Build output already present."
    fi
else
    warn "npm is not installed. The Inertia UI will not be built until you install Node.js and run 'npm install && npm run build'."
fi

# ---------------------------------------------------------------------------
# 5. Start Sail and migrate
# ---------------------------------------------------------------------------
info "Starting the Docker environment"

./vendor/bin/sail up -d
step "Sail services are running (./vendor/bin/sail down to stop)."

info "Running migrations and seed data"
./vendor/bin/sail artisan migrate --force --seed
step "Database is migrated and seeded (test@example.com / password)."

# ---------------------------------------------------------------------------
# 6. Scheduler
# ---------------------------------------------------------------------------
info "Configuring the daily scheduler"

SCHEDULER_SCHEDULE="* * * * * cd ${REPO_DIR} && ./vendor/bin/sail artisan schedule:run >> /dev/null 2>&1"

if command_exists crontab; then
    if crontab -l 2>/dev/null | grep -qF "artisan schedule:run"; then
        step "A schedule:run cron entry already exists."
    else
        if confirm "Install a crontab entry that runs the scheduler every minute?"; then
            { crontab -l 2>/dev/null || true; echo "$SCHEDULER_SCHEDULE"; } | crontab -
            step "Cron entry installed (daily sheets:import at 02:00)."
        else
            warn "Skipped. Add this line to 'crontab -e' yourself:"
            printf '    %s\n' "$SCHEDULER_SCHEDULE" >&2
        fi
    fi
else
    warn "crontab is unavailable. Run the scheduler manually with: ./vendor/bin/sail artisan schedule:work"
fi

# ---------------------------------------------------------------------------
# 7. Queue worker
# ---------------------------------------------------------------------------
info "Queue worker"

cat <<'INSTRUCTIONS'
  Large imports run on the database queue. Start a worker with:

      ./vendor/bin/sail artisan queue:work --tries=3

  In development, 'composer run dev' starts Vite, the queue worker, the
  scheduler, and the log stream together.
INSTRUCTIONS

# ---------------------------------------------------------------------------
# 8. Remaining Google credentials steps
# ---------------------------------------------------------------------------
info "Google credentials still required"

cat <<'CHECKLIST'
  The importer reads spreadsheets through a Google service account:

    1. Create a Google Cloud project.
    2. Enable the Google Sheets API.
    3. Create a service account and download its JSON key.
    4. Store the key outside source control, readable inside the container:

           mkdir -p storage/app/google
           cp /path/to/key.json storage/app/google/credentials.json
           chmod 600 storage/app/google/credentials.json

    5. Point the app at it (in .env):

           GOOGLE_SHEETS_CREDENTIALS=/var/www/html/storage/app/google/credentials.json

    6. Share every spreadsheet you want to import with the service account
       email as a Viewer.

  Then configure sources, tabs, and column mappings in the UI, validate with
  './vendor/bin/sail artisan sheets:validate', and import with
  './vendor/bin/sail artisan sheets:import'.

  Full details: README.md
CHECKLIST

info "Setup complete."
