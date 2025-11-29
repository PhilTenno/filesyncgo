# fileSyncGo

Contao 5.3+ Extension: Extern aufrufbarer POST-Endpunkt `POST /filesyncgo/trigger`
- Auth: Header `Authorization: Bearer <token>`
- Kein Request-Body, keine Dateiuploads
- RateLimit: 24 requests / rolling 24h per token
See docs for implementation details.
