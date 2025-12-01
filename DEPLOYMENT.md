# Growdash Deployment

Ein-Kommando-Deployment (Windows PowerShell):

```pwsh
# Im Projektroot (enthält artisan)
npm run deploy
```

Optional Services mit Flags:

```pwsh
pwsh -File scripts/deploy.ps1 -Env Production -StartReverb -StartQueue
```

Voraussetzungen:
- PHP 8.3+, Composer, Node.js 18+, npm
- `.env` mit DB/Cache/Queue/Broadcasting (siehe DESIGN.md Production Abschnitt)

Enthaltene Schritte:
- Composer install (no-dev, optimized)
- npm ci + Vite build
- Laravel caches: config/route/view/event
- DB Migrationen — `php artisan migrate --force`
- Optional: `reverb:start`, `queue:work`

Troubleshooting:
- `.env` fehlt: Skript kopiert `.env.example` → `.env`
- Reverb Install fehlgeschlagen: Nutze `reverb:start` mit Host/Port aus `.env`
- Caches invalide: `php artisan optimize:clear` erneut ausführen
- Device Auth 403: Prüfe `X-Device-ID` und `X-Device-Token` Header (Plaintext vs. Hash)

Linux/macOS Hinweis:
- Entsprechendes Bash-Skript kann analog erstellt werden, oder führe die Schritte manuell aus (siehe DESIGN.md).
