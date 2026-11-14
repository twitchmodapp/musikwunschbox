# Sicherheitsprüfung vor der ersten öffentlichen Version

Stand: 15. November 2026. Überprüfter Umfang: WordPress-Client 0.6.8, keine praktische Installation oder OAuth-Anmeldung.

## Bereits im Code geprüft

- Die eingehende REST-Route `/incoming` prüft eine zeitbegrenzte HMAC-SHA-256-Signatur; die Prüfung erfolgt vor der Verarbeitung des Payloads.
- Schreibende WordPress-Adminaktionen prüfen `manage_options` und verwenden WordPress-Nonces.
- Der öffentliche Endpunkt `/songs` zeigt Twitch-Nutzernamen und Musikwünsche; dies muss in der Datenschutzerklärung der installierenden Website berücksichtigt werden.
- Der öffentliche Endpunkt `/proof` erzeugt eine HMAC-Antwort auf einen Challenge-Wert und sollte vor der Freigabe bezüglich seiner Rolle im Pairing-Protokoll überprüft werden.
- Es wurden in den bisher inspizierten Plugin-Dateien keine fest eingebauten Zugangstokens oder Client-Secrets gefunden. Das ist keine Garantie für einen vollständigen Geheimnis-Scan.
- Die Verbindungsfunktion erhielt eine Einschränkung der OAuth-Weiterleitungsadresse auf `https://id.twitch.tv` und `https://musikwunsch.it-md-server.de`. Kompatibilität mit dem tatsächlichen Pairing-Ablauf muss getestet werden.

## Noch offen (Release-Blocker)

1. End-to-End-Test mit unabhängiger WordPress-Installation und einem anderen Twitch-Konto.
2. Prüfen, ob der zentrale Pairing-/OAuth-/EventSub-Dienst fremde Sites annimmt und nur autorisierte Sites koppelt.
3. Prüfung des `/proof`-Endpunkts auf Missbrauch und Replay-Schutz.
4. Tests fehlerhafter und abgelaufener HMAC-Signaturen; Rate-Limits für öffentliche Routen.
5. Prüfung des Status- und Playlist-Cleanup-Verhaltens sowie der Datenlöschung.
6. Aktuelle WordPress-Version und PHP 8 unter realistischen Bedingungen testen.
7. Datenschutztexte und Dokumentation zur Übertragung an Twitch und die Musiksuche bestätigen.
8. Ersten GitHub-Release erst nach bestandenen Tests veröffentlichen.

## Ergebnis

Es gibt noch **keine Freigabeempfehlung** für den produktiven Einsatz durch fremde Streamer. Die CI-Syntax- und ZIP-Prüfungen sind Hilfsmittel, kein Ersatz für ein Sicherheitsreview.
