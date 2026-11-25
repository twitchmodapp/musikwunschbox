# Musikwunschbox für Twitch – WordPress-Plugin

Musikwünsche über den Twitch-Chat und über Kanalpunkte sammeln, verwalten und als öffentliche Warteschlange auf einer eigenen WordPress-Website anzeigen.

**Status: Version 0.7.12 – öffentliche Beta vorbereitet. Externe Registrierung erfordert serverseitige Freischaltung. Eine installierbare ZIP-Datei ist zur separaten Bereitstellung durch den Betreiber vorgesehen.**

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

Dieses Repository stellt den Quellcode bereit. Ein installierbares Plugin-Paket soll separat über die Website des Betreibers bereitgestellt werden. Solange die öffentliche Registrierung deaktiviert ist, können nur ausdrücklich zugelassene Installationen verbunden werden.

## Lizenz

Das WordPress-Plugin steht unter der **GNU General Public License, Version 2 oder jeder späteren Version (GPL-2.0-or-later)**. Der vollständige Lizenztext ist in [`LICENSE`](LICENSE) enthalten. Die Plugin-Metadaten und die `readme.txt` nennen dieselbe Lizenz.

## Versionshinweise

Version 0.7.12 kennzeichnet die öffentliche Beta; die Client-Funktionen bleiben unverändert. Version 0.7.11 erhält die Scrollposition bei Verwaltungsaktionen und verbessert die Wiederherstellung des Tastaturfokus.

**Die automatische Registrierung ist zentral implementiert. Für die öffentliche Beta muss der Betreiber sie serverseitig aktivieren. Der vollständige externe Praxistest steht noch aus.** Die zentrale Plattform prüft HTTPS, öffentliche IPv4-Ziele und den Besitz des installationsspezifischen Schlüssels. IPv6-only-Webseiten und WordPress in Unterverzeichnissen werden derzeit nicht unterstützt. Ein Twitch-Affiliate- oder Partnerstatus ist für Kanalpunkte erforderlich; Chatwünsche sind davon unabhängig. Die bestehenden zugelassenen Installationen bleiben nutzbar.

**Keine Zugangsdaten in GitHub hochladen:** keine Twitch-Secrets, Access- oder Refresh-Tokens, WordPress-Konfiguration, HMAC-Geheimnisse oder Datenbanken.

Das private Projekt `twitchmodapp/musikwunsch` ist ein separates Vorhaben und gehört nicht in dieses Repository.
