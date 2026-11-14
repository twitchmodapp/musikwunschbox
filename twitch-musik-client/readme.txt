=== Twitch Musik – Client ===
Contributors: twitchmodapp
Tags: twitch, musikwunsch, musik, kanalpunkte, livestream
Requires PHP: 8.0
Stable tag: 0.6.8
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

WordPress-Plugin für Musikwünsche über Twitch-Chat und Twitch-Kanalpunkte.

== Beschreibung ==
Twitch Musik – Client zeigt eine öffentliche Musikwunschliste an und bietet eine
Verwaltung für Streamer. Musikwünsche kommen über Twitch-Chat bzw. Kanalpunkte,
nicht über ein öffentliches Wunschformular.

WICHTIG: Das Plugin benötigt den separat betriebenen Twitch-Musik-Zentraldienst
unter https://musikwunsch.it-md-server.de/ mit aktiven OAuth- und EventSub-
Funktionen. Es funktioniert nicht als vollständig unabhängiger Twitch-Bot.

== Installation ==
1. ZIP-Datei in WordPress unter Plugins > Installieren > Plugin hochladen wählen.
2. Plugin aktivieren (PHP mindestens 8.0 erforderlich).
3. Im WordPress-Adminmenü „Twitch Musikwünsche“ öffnen.
4. Verbindung zur Zentrale herstellen und Twitch-Freigaben prüfen.
5. Shortcode [twitch_musikwuensche] in eine WordPress-Seite einfügen.
6. Chatbefehl, Kanalpunkte-Belohnung und Einstellungen im Plugin verwalten.

== Funktionen ==
* Musikwünsche über Chat und Twitch-Kanalpunkte
* Warteschlange im WordPress-Adminbereich
* Gespielt/Ablehnen und manuelles Leeren
* Aktivieren/Deaktivieren und Hinweis im Chat
* Öffentliche Liste mit Statusanzeige
* Automatische vollständige Leerung im 12-Stunden-Zyklus

== Datenschutz und Betrieb ==
Das Plugin stellt eine öffentliche Liste mit Twitch-Nutzernamen und
Musikwünschen bereit. Es kommuniziert mit dem zentralen Dienst sowie
Twitch und einer externen Musiksuche. Betreiber müssen ihre Datenschutzhinweise
und die Berechtigung der Twitch-App selbst prüfen.

== Changelog ==
= 0.6.8 =
* Zuschauerstatus in der öffentlichen Ansicht. Originaler Plugin-Code,
  ergänzt um Installations- und Nutzungshinweise für die Distribution.
