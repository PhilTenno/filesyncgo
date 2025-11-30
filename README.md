# fileSyncGo

Contao 5.3+ Extension: Extern aufrufbarer POST-Endpunkt `POST /filesyncgo/trigger`
- Auth: Header `Authorization: Bearer <token>`
- Kein Request-Body, keine Dateiuploads
- RateLimit: 24 requests / rolling 24h per token
See docs for implementation details.

Configuration — fileSyncGo
Monolog
fileSyncGo nutzt einen eigenen Monolog‑Channel contao.filesyncgo. Beispiel‑Datei: config/packages/filesyncgo_monolog.yaml.

Logger‑Service: monolog.logger.contao.filesyncgo
Logdatei: %kernel.logs_dir%/filesyncgo.log
Loglevel: info (Fehler werden als error geloggt). Keine sensitiven Daten protokollieren.
Sicherheit & Betrieb
HTTPS ist zwingend erforderlich. Der Trigger lehnt nicht‑TLS Verbindungen ab.
Token niemals in Query‑Parametern oder Logs übergeben.
Token im Backend konfigurieren (max. 32 Zeichen). Token werden als Hash gespeichert.
Rate‑Limit: 24 Requests / rolling 24h pro Token (DB‑persistiert per DCA/Doctrine). Bei Überschreitung: HTTP 429 + JSON Fehlermeldung.
CLI‑Fallback: Der Controller ruft standardmäßig interne Services auf; falls nicht vorhanden, fällt er auf den Console‑Befehl contao:files:sync zurück. Prüfe auf dem Zielsystem, ob vendor/bin/contao-console oder bin/console verfügbar ist und passe ggf. den Pfad an.