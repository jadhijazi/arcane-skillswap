# SkillSwap

## Local Development Setup

This repository contains a Vue 3 + Vite frontend and a small PHP Slim backend under `server/`.

### Prerequisites

- Node.js 22.18.0 or newer, or Node.js 24.12.0 or newer
- pnpm
- PHP 8.0 or newer
- Composer (for the PHP backend)

### 1. Install frontend dependencies

From the repository root:

```bash
pnpm install
```

### 2. Install backend dependencies

From the `server/` folder:

```bash
cd server
composer install
```

If the `vendor/` directory is already present, this step may already be complete.

### 3. Run the frontend

From the repository root:

```bash
pnpm dev
```

Open `http://localhost:5173` in your browser.

### 4. Run the backend

From the `server/` folder:

```bash
php -S 127.0.0.1:8080 -t public
```

Then open `http://127.0.0.1:8080` to verify the backend is running.

### 5. Useful commands

- `pnpm dev` — start the Vite development server
- `pnpm build` — build the frontend for production
- `pnpm preview` — preview the built frontend
- `pnpm lint` — run configured linters
- `pnpm format` — format frontend source files

### Optional: Android / Capacitor

This project includes Capacitor support and Android platform files. To sync web assets and open Android:

```bash
pnpm exec cap sync android
pnpm exec cap open android
```

Then build or run from Android Studio.

### Notes

- The frontend app is configured in `capacitor.config.ts` with `webDir: 'dist'`.
- The backend entry point is `server/public/index.php`.
- If you make changes to the frontend and want them in Capacitor builds, rebuild the app with `pnpm build` before syncing.
