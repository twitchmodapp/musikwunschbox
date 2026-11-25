=== Twitch Musik – Client ===
Contributors: twitchmodapp
Tags: twitch, musikwunsch, musik, kanalpunkte, livestream
Requires PHP: 8.0
Stable tag: 0.7.12
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

== Nutzung ==
Das ZIP-Paket wird separat vom Betreiber bereitgestellt. Die Version 0.7.12 ist für die öffentliche Beta vorgesehen. Externe Registrierungen sind erst nach serverseitiger Freischaltung möglich.
Voraussetzungen: HTTPS, öffentlich erreichbare IPv4-Adresse und WordPress im Stammverzeichnis einer Domain. Für Kanalpunkte ist Twitch-Affiliate- oder Partnerstatus erforderlich.
Installation: In WordPress unter Plugins > Neues Plugin > Plugin hochladen das ZIP-Paket auswählen, installieren, aktivieren und im Plugin mit Twitch verbinden.

== Funktionen ==
* Musikwünsche über Chat und Twitch-Kanalpunkte
* Warteschlange im WordPress-Adminbereich
* Gespielt/Ablehnen und manuelles Leeren
* Aktivieren/Deaktivieren und Hinweis im Chat
* Öffentliche Liste mit Statusanzeige
* Wahlweise vollständige Leerung im 12-Stunden-Zyklus oder dauerhafte Aufbewahrung
* Administrationsansicht mit Darkmode und automatischer Aktualisierung alle 20 Sekunden

== Datenschutz und Betrieb ==
Das Plugin stellt eine öffentliche Liste mit Twitch-Nutzernamen und
Musikwünschen bereit. Es kommuniziert mit dem zentralen Dienst sowie
Twitch und einer externen Musiksuche. Betreiber müssen ihre Datenschutzhinweise
und die Berechtigung der Twitch-App selbst prüfen.

== Changelog ==
= 0.7.12 =
* Vorbereitung der öffentlichen Beta mit automatischer Domain-Zulassung durch den Zentraldienst.
* Aktualisierte Installationshinweise und Versionskennzeichnung; Client-Funktionen unverändert.
* Vollständiger externer Praxistest steht noch aus.

= 0.7.11 =
* Scrollposition nach Verwaltungsaktionen wiederherstellen.
* Tastaturfokus nach Möglichkeit wiederherstellen.
* Scrollposition bei automatischer Aktualisierung berücksichtigen.

= 0.7.10 =
* Screenreader-Verbesserungen, automatische Aktualisierung und Zuschaueransicht im neuen Tab.

= 0.6.8 =
* Zuschauerstatus in der öffentlichen Ansicht. Originaler Plugin-Code,
  ergänzt um Installations- und Nutzungshinweise für die Distribution.
