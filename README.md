# Musikwunschbox für Twitch – WordPress-Plugin

Musikwünsche über den Twitch-Chat und über Kanalpunkte sammeln, verwalten und als öffentliche Warteschlange auf einer eigenen WordPress-Website anzeigen.

**Status: Vorbereitung der ersten öffentlichen Version. Noch kein freigegebenes Release.**

## Funktionen des bisherigen WordPress-Clients

- Musikwünsche aus Chat und Kanalpunktebelohnungen entgegennehmen
- Warteschlange mit Songtitel, Interpret und Cover anzeigen
- Wünsche als gespielt oder abgelehnt markieren
- Liste mit einem Schalter aktivieren oder deaktivieren
- Hinweis bei deaktivierter Liste optional an den Twitch-Chat senden
- Zuschaueransicht mit Status der Liste
- Automatische vollständige Bereinigung der Liste nach zwölf Stunden

Die Verfügbarkeit einzelner Funktionen hängt von der Verbindung zur zentralen Twitch-Anwendung, den Twitch-Berechtigungen und den Einstellungen des Streamers ab.

## Systemvoraussetzungen

- WordPress mit PHP 8.0 oder neuer
- HTTPS und funktionierende WordPress-REST-API
- Twitch-Konto mit passenden Berechtigungen
- Erreichbare Zentrale für OAuth und EventSub unter `https://musikwunsch.it-md-server.de`

**Wichtig:** Das Client-Plugin ist nicht vollständig eigenständig. Es verwendet den zentralen Twitch-Dienst des Projekts. Die Nutzung mit fremden Twitch- und WordPress-Konten ist noch nicht für die öffentliche Freigabe geprüft.

## Installation (nach Freigabe)

1. Die **ZIP-Datei eines offiziellen GitHub-Releases** herunterladen; nicht den Button „Code > Download ZIP“ verwenden.
2. In WordPress **Plugins > Installieren > Plugin hochladen** auswählen.
3. ZIP installieren und Plugin aktivieren.
4. Unter **Twitch Musikwünsche** die Verbindung einrichten und das Twitch-Konto autorisieren.
5. Für die Zuschaueransicht den Shortcode `[twitch_musikwuensche]` auf einer WordPress-Seite einfügen.

Die Anleitung wird vor Veröffentlichung anhand einer frischen WordPress-Installation überprüft.

## Veröffentlichung

Der erste Release-Kandidat basiert auf dem vorhandenen WordPress-Client **0.6.8**. Vor einem öffentlichen Download sind noch ein Funktionstest mit einem unabhängigen Twitch-Konto, die Prüfung des Zentraldienstes, ein Sicherheitsreview und die Festlegung einer Quellcodelizenz erforderlich.

**Keine Zugangsdaten in GitHub hochladen:** keine Twitch-Secrets, Access- oder Refresh-Tokens, WordPress-Konfiguration, HMAC-Geheimnisse oder Datenbanken.

Das private Projekt `twitchmodapp/musikwunsch` ist ein separates Vorhaben und gehört nicht in dieses Repository.
