# Sicherheit und verantwortungsvolle Meldungen

Das WordPress-Plugin kommuniziert mit einem zentral betriebenen Twitch-Dienst. Zugangsdaten, Token und andere Geheimnisse dürfen weder in dieses Repository noch in öffentliche Issues gelangen.

## Sicherheitsmeldungen

Bitte **keine** Zugangsdaten, Token oder persönlichen Daten in öffentlichen Issues posten. Verwende die privaten Kommunikationsmöglichkeiten des Repository-Betreibers oder die GitHub-Option „Report a vulnerability“, sofern aktiviert.

## Vor der ersten Freigabe zu prüfen

- WordPress-REST-Routen: Berechtigungen, Eingabevalidierung und HMAC-Verifikation
- Twitch-OAuth-Pairing mit einem neuen, unabhängigen Twitch-Konto
- Verfügbarkeit und Authentifizierung des externen Zentraldienstes
- CSRF-/Nonce-Prüfungen im WordPress-Adminbereich
- Ausgabe-Escaping, Speicherung und Bereinigung von Nutzerdaten
- Installations- und Upgrade-Test unter PHP 8 und einer aktuellen WordPress-Version
- Lizenzentscheidung vor öffentlicher Release-Verteilung

Die bisherige PHP-Syntaxprüfung ersetzt weder einen Sicherheitstest noch einen vollständigen Funktionstest.
