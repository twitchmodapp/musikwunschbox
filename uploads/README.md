# Plugin-ZIP hochladen

Für eine neue Plugin-Version die **installierbare ZIP** (`twitch-musik-client-vX.Y.Z-*.zip`) hier über **Add file → Upload files** hochladen.

GitHub Actions entpackt die ZIP, prüft die PHP-Syntax, aktualisiert den Plugin-Quellcode und erstellt ein herunterladbares GitHub-Release.

Vor einer weiteren Version die alte ZIP aus diesem Verzeichnis entfernen; gleichzeitig darf nur eine ZIP vorhanden sein. Keine Zugangsdaten, Backups oder Konfigurationsdateien hochladen.
