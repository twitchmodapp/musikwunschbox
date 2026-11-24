# Musikwunschbox für Twitch – WordPress-Plugin

Musikwünsche über den Twitch-Chat und über Kanalpunkte sammeln, verwalten und als öffentliche Warteschlange auf einer eigenen WordPress-Website anzeigen.

**Status: Quellcode auf Version 0.7.11 synchronisiert. Es wird keine installierbare ZIP-Datei und kein GitHub-Release bereitgestellt.**

## Funktionen der Musikwunschbox

- Musikwünsche aus Chat und Kanalpunktebelohnungen entgegennehmen
- Warteschlange mit Songtitel, Interpret und Cover anzeigen
- Wünsche als gespielt oder abgelehnt markieren
- Liste mit einem Schalter aktivieren oder deaktivieren
- Hinweis bei deaktivierter Liste optional an den Twitch-Chat senden
- Zuschaueransicht mit Status der Liste
- Automatische Bereinigung nach zwölf Stunden oder dauerhafte Aufbewahrung (ab 0.6.9)
- In Version 0.7.10: Screenreader-Verbesserungen, 20-Sekunden-Aktualisierung, Zuschaueransicht über das Administrationsmenü in einem neuen Tab

Die Verfügbarkeit einzelner Funktionen hängt von der Verbindung zur zentralen Twitch-Anwendung, den Twitch-Berechtigungen und den Einstellungen des Streamers ab.

## Systemvoraussetzungen

- WordPress mit PHP 8.0 oder neuer
- HTTPS und funktionierende WordPress-REST-API
- Twitch-Konto mit passenden Berechtigungen
- Erreichbare Zentrale für OAuth und EventSub unter `https://musikwunsch.it-md-server.de`

**Wichtig:** Das Client-Plugin ist nicht vollständig eigenständig. Es verwendet den zentralen Twitch-Dienst des Projekts. Die Nutzung mit fremden Twitch- und WordPress-Konten ist noch nicht für die öffentliche Freigabe geprüft.

## Nutzung

Dieses Repository dient der öffentlichen Dokumentation des Quellcodes. Es wird kein installierbares Plugin-Paket angeboten. Die betriebenen Installationen werden separat verwaltet.

## Lizenz

Das WordPress-Plugin steht unter der **GNU General Public License, Version 2 oder jeder späteren Version (GPL-2.0-or-later)**. Der vollständige Lizenztext ist in [`LICENSE`](LICENSE) enthalten. Die Plugin-Metadaten und die `readme.txt` nennen dieselbe Lizenz.

## Versionshinweise

Version 0.7.11 erhält die Scrollposition bei Verwaltungsaktionen und verbessert die Wiederherstellung des Tastaturfokus.

**Die zentrale Node.js-Plattform akzeptiert derzeit nur ausdrücklich zugelassene WordPress-Domains.** Bevor beliebige Webseiten das Plugin nutzen können, muss eine sichere automatische Registrierung umgesetzt und getestet werden. Eine öffentliche Nutzung ist derzeit nicht freigegeben.

**Keine Zugangsdaten in GitHub hochladen:** keine Twitch-Secrets, Access- oder Refresh-Tokens, WordPress-Konfiguration, HMAC-Geheimnisse oder Datenbanken.

Das private Projekt `twitchmodapp/musikwunsch` ist ein separates Vorhaben und gehört nicht in dieses Repository.
